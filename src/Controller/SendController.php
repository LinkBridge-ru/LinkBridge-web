<?php

declare(strict_types=1);

namespace App\Controller;

use Throwable;
use App\Service\LinkBridgeService;
use App\Repository\LinkBridgeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SendController extends AbstractController
{

	public function __construct(
		private readonly LinkBridgeService    $LinkBridge,
		private readonly LinkBridgeRepository $LinkBridgeRepo,
	)
	{
	}

	#[Route(path: "/send", name: "send", methods: "GET")]
	public function latest(Request $request): Response
	{
		$pin = $request->get("pin");
		if (empty($pin)) {
			$this->addFlash("danger", "Invalid Recipient PIN");
			return $this->redirectToRoute("home");
		}

		$url = $request->get("url");
		$pin = $this->LinkBridge->sanitizePINCode($pin);
		if (empty($url)) return $this->render("send/index.twig", ["PIN" => $pin]);

		try {
			# Записываем ссылку для получателя.
			$url = $this->LinkBridge->sanitizeUrl($url);
			$this->LinkBridgeRepo->writeLinkForRecipient($pin, $url);
			$this->addFlash("success", "The link was sent successfully");
		} catch (Throwable $exception) {
			$this->addFlash("danger", $exception->getMessage());
		}

		return $this->redirectToRoute("home");
	}

}
