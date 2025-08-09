<?php

declare(strict_types=1);

namespace App\Controller;

use Throwable;
use Exception;
use App\Service\LinkBridgeService;
use App\Repository\LinkBridgeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ApiController extends AbstractController
{

	public function __construct(
		private readonly LinkBridgeService    $LinkBridge,
		private readonly LinkBridgeRepository $LinkBridgeRepo,
	)
	{
	}

	#[Route("/api/_get", name: "_get", methods: "GET", env: "dev")]
	public function get(): Response
	{
		try {
			$result = $this->LinkBridgeRepo->getAllRecipientList();
			$message = "OK";
			$status = Response::HTTP_OK;
		} catch (Throwable $e) {
			$result = null;
			$code = $e->getCode();
			$message = $e->getMessage();
			$status = is_int($code) && $code >= 100 && $code < 600 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
		}

		return $this->json(
			data: [
				"status" => $status,
				"message" => $message,
				"data" => $result,
			],
			status: $status,
		);
	}

	#[Route("/api/clear", name: "clear", methods: "DELETE")]
	public function clear(): Response
	{
		try {
			$affected = $this->LinkBridgeRepo->cleanupOldSessions();
			$message = "OK";
			$status = Response::HTTP_OK;
		} catch (Throwable $e) {
			$code = $e->getCode();
			$message = $e->getMessage();
			$status = is_int($code) && $code >= 100 && $code < 600 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
		}

		return $this->json(
			data: [
				"status" => $status,
				"message" => $message,
				"data" => $affected ?? null,
			],
			status: $status,
		);
	}

	#[Route(path: ["/api/check", "/check"], name: "check", methods: "GET")]
	public function check(Request $request): Response
	{
		$status = Response::HTTP_OK;
		$message = "Link Found";
		$data = [];

		try {
			# Подготавливаем PIN.
			$pin = $this->LinkBridge->sanitizePinCode(pin: $request->get("pin"));

			# Если не найден получатель.
			if (empty($pin)) throw new Exception(
				message: "`pin` param cannot be empty",
				code: Response::HTTP_BAD_REQUEST,
			);

			# Ищем получателя с его ссылкой.
			$LinkBridge = $this->LinkBridgeRepo->findOneBy(["pin" => $pin]);
			if (empty($LinkBridge)) throw new Exception(
				message: "Recipient not found",
				code: Response::HTTP_NOT_FOUND,
			);

			# Если всё ещё ждём ссылку.
			$url = $LinkBridge->getUrl();
			if (empty($url)) {
				$this->LinkBridgeRepo->updatePinRequest($LinkBridge);
				$status = Response::HTTP_ACCEPTED;
				$message = "Waiting for the link";
				$data = ["pin" => $pin];
			} else {
				# Удаляем ссылку после получения.
				$this->LinkBridgeRepo->removeReceivedLink($LinkBridge);
				$data = [
					"pin" => $pin,
					"url" => $url,
				];
			}
		} catch (Throwable $e) {
			$code = $e->getCode();
			$message = $e->getMessage();
			$status = is_int($code) && $code >= 100 && $code < 600 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
			if (!empty($pin)) $data = ["pin" => $pin];
		}

		return $this->json(
			data: [
				"status" => $status,
				"message" => $message,
				"data" => $data ?: null,
			],
			status: $status,
		);
	}

	#[Route("/api/send/2", name: "api_send_2", methods: ["POST"])]
	public function api_send_2(Request $request): Response
	{
		$pin = $request->get(key: "pin");
		$url = $request->get(key: "url");

		try {
			$pin = $this->LinkBridge->sanitizePinCode($pin);
			$url = $this->LinkBridge->sanitizeUrl($url);

			if (empty($pin) || empty($url)) throw new Exception(
				message: "`pin` and `url` params cannot be empty",
				code: Response::HTTP_BAD_REQUEST,
			);

			$this->LinkBridgeRepo->writeLinkForRecipient($pin, $url);
			$status = Response::HTTP_ACCEPTED;
			$message = "The link was sent successfully";
		} catch (Exception $e) {
			$code = $e->getCode();
			$message = $e->getMessage();
			$status = is_int($code) && $code >= 100 && $code < 600 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
		}

		return $this->json(
			data: [
				"message" => $message,
				"status" => $status,
				"data" => [
					"pin" => $pin,
					"url" => $url,
				],
			],
			status: $status,
		);
	}

}
