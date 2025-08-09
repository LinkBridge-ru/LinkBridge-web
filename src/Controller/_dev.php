<?php

declare(strict_types=1);

namespace App\Controller;

use Exception;
use DateTimeImmutable;
use App\Entity\LinkBridge;
use App\Service\LinkBridgeService;
use App\Repository\LinkBridgeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route("/_dev", name: "_dev_", env: "dev")]
class _dev extends AbstractController
{

	public function __construct(
		private readonly LinkBridgeService    $LinkBridgeService,
		private readonly LinkBridgeRepository $LinkBridgeRepo,
	)
	{
	}


	#[Route("/", name: "read")]
	public function read(Request $request): Response
	{
		$repository = $this->LinkBridgeRepo;
		$records = $repository->findAll();

		if ($request->isMethod("POST")) {
			$allData = $request->request->all();
			$data = $allData["records"] ?? [];

			foreach ($data as $id => $fields) {
				/** @var LinkBridge $record */
				$record = $repository->find($id);
				if (!$record) continue;

				if (isset($fields["pin"]) && $fields["pin"] !== $record->getPin()) $record->setPin($fields["pin"]);
				if (isset($fields["url"]) && $fields["url"] !== $record->getUrl()) $record->setUrl($fields["url"]);
				if (isset($fields["sessionId"]) && $fields["sessionId"] !== $record->getSessionId()) $record->setSessionId($fields["sessionId"]);
			}

			$this->LinkBridgeRepo->getEntityManagerInterface()->flush();
			$this->addFlash("success", "Changes Saved!");
		}

		return $this->render("/_dev/read.twig", ["records" => $records]);
	}

	#[Route("/write", name: "write")]
	public function write(Request $request, SessionInterface $session): Response
	{
		if ($request->isMethod("POST")) {
			if (!$session->isStarted()) $session->start();
			$ssid = $session->getId();
			$pin = $request->request->get("pin");
			$url = $request->request->get("url");
			$pin = $this->LinkBridgeService->sanitizePinCode($pin);
			$url = $this->LinkBridgeService->sanitizeUrl($url);

			try {
				$link = new LinkBridge();
				$link->setPin($pin);
				$link->setUrl($url);
				$link->setSessionId($ssid);
				$link->setRegisteredAt(new DateTimeImmutable());

				$emi = $this->LinkBridgeRepo->getEntityManagerInterface();
				$emi->persist($link);
				$emi->flush();
				$emi->commit();
				$this->addFlash("success", "Write Successfully!");
			} catch (Exception $e) {
				$this->addFlash("danger", "Write Failed: " . $e->getMessage());
			}
		}

		return $this->render("/_dev/write.twig");
	}

	#[Route("/wipe", name: "wipe")]
	public function wipe(): Response
	{
		$this->LinkBridgeRepo->getEntityManagerInterface()->createQuery(dql: "DELETE FROM " . LinkBridge::class)->execute();

		$response = $this->json(
			data: [
				"status" => "HTTP_OK",
				"message" => "Successfully wiped",
			],
			status: Response::HTTP_OK,
		);

		$response->headers->set("Refresh", "1.5; url=" . $this->generateUrl("App\Controller\_dev::read"));
		return $response;
	}

}
