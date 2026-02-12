<?php

declare(strict_types=1);

namespace App\Controller;

use Throwable;
use Exception;
use OpenApi\Attributes as OA;
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

	#[OA\Get(
		path: "/api/_get",
		summary: "Get recipients list. Only in dev mode.",
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 200),
						new OA\Property(property: "message", type: "string", example: "OK"),
						new OA\Property(property: "data", type: "array",
							items: new OA\Items(
								properties: [
									new OA\Property(property: "id", type: "integer", example: 123),
									new OA\Property(property: "pin", type: "string", example: "123456"),
									new OA\Property(property: "timestamp", type: "integer", example: 1754000000),
								],
								type: "object",
							),
						),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 500,
				description: "Default API Error",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 500),
						new OA\Property(property: "message", type: "string", example: "Throws an exception message"),
						new OA\Property(property: "data", type: "null", example: null),
					],
					type: "object",
				),
			),
		],
	)]
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

	#[OA\Delete(
		path: "/api/clear",
		summary: "Clearing old sessions",
		responses: [
			new OA\Response(
				response: 200,
				description: "OK",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 200),
						new OA\Property(property: "message", type: "string", example: "OK"),
						new OA\Property(
							property: "data",
							description: "Affected rows",
							type: "integer",
							example: 5,
							nullable: true,
						),
					],
					type: "object"
				),
			),
			new OA\Response(
				response: 500,
				description: "Default API Error",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 500),
						new OA\Property(property: "message", type: "string", example: "Throws an exception message"),
						new OA\Property(property: "data", type: "null", example: null),
					],
					type: "object",
				),
			),
		],
	)]
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

	#[OA\Get(
		path: "/api/check",
		summary: "Get link",
		parameters: [
			new OA\Parameter(
				name: "pin",
				description: "This form cuts-off all characters that are not numbers.",
				in: "query",
				required: true,
				schema: new OA\Schema(type: "string"),
				example: "123-456",
			),
		],
		responses: [
			new OA\Response(
				response: 200,
				description: "Link Found",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 200),
						new OA\Property(property: "message", type: "string", example: "Link Found"),
						new OA\Property(
							property: "data",
							properties: [
								new OA\Property(property: "pin", type: "string", example: "123456"),
								new OA\Property(property: "url", type: "string", example: "https://example.com"),
							],
							type: "object",
						),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 202,
				description: "When the recipient is found: Waiting for the link",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 202),
						new OA\Property(property: "message", type: "string", example: "Waiting for the link"),
						new OA\Property(
							property: "data",
							properties: [
								new OA\Property(property: "pin", type: "string", example: "123456"),
							],
							type: "object",
						),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 400,
				description: "If the `pin` param are not passed or empty",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 400),
						new OA\Property(property: "message", type: "string", example: "`pin` param cannot be empty"),
						new OA\Property(property: "data", type: "null", example: null),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 404,
				description: "If the recipient is not found",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 404),
						new OA\Property(property: "message", type: "string", example: "Recipient not found"),
						new OA\Property(property: "data", properties: [
							new OA\Property(
								property: "pin",
								description: "Returns the received pin",
								type: "string",
								example: "123456",
							),
						]),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 500,
				description: "Default API Error",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 500),
						new OA\Property(property: "message", type: "string", example: "Throws an exception message"),
						new OA\Property(property: "data", type: "null", example: null),
					],
					type: "object",
				),
			),
		],
	)]
	#[Route(path: ["/api/check", "/check"], name: "check", methods: "GET")]
	public function check(Request $request): Response
	{
		$status = Response::HTTP_OK;
		$message = "Link Found";
		$data = [];

		try {
			# Подготавливаем PIN.
			$pin = $this->LinkBridge->sanitizePinCode(pin: $request->query->get("pin"));

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

	#[OA\Post(
		path: "/api/send/1",
		summary: "Don't use it.",
		deprecated: true,
	)]
	#[OA\Post(
		path: "/send/api/1",
		summary: "Don't use it.",
		deprecated: true,
	)]
	#[Route(path: ["/api/send/1", "/send/api/1"], name: "api_send_1", methods: ["POST"])]
	public function api_send_1(Request $request): Response
	{
		$pin = $request->request->get("SSID");
		$url = $request->request->get("URL");

		try {
			$pin = $this->LinkBridge->sanitizePinCode($pin);
			$url = $this->LinkBridge->sanitizeUrl($url);

			if (empty($pin) || empty($url)) throw new Exception(
				message: "Device or URL is not specified",
				code: Response::HTTP_BAD_REQUEST,
			);

			# Записываем ссылку для получателя.
			$this->LinkBridgeRepo->writeLinkForRecipient($pin, $url);

			$message = "Success";
			$status = Response::HTTP_OK;
		} catch (Exception $e) {
			$code = $e->getCode();
			$message = $e->getMessage();
			$status = is_int($code) && $code >= 100 && $code < 600 ? $code : Response::HTTP_INTERNAL_SERVER_ERROR;
		}

		return $this->json(
			data: [
				"description" => $message,
				"status" => $status,
				"for" => $pin,
			],
			status: $status,
		);
	}

	#[OA\Post(
		path: "/api/send",
		summary: "Send link",
		requestBody: new OA\RequestBody(
			required: true,
			content: new OA\JsonContent(
				required: ["pin", "url"],
				properties: [
					new OA\Property(property: "pin", type: ["string", "integer"], example: "123-456"),
					new OA\Property(property: "url", type: "string", example: "https://example.com"),
				],
				type: "object"
			),
		),
		responses: [
			new OA\Response(
				response: 202,
				description: "OK",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "message", type: "string", example: "The link was sent successfully"),
						new OA\Property(property: "status", type: "integer", example: 202),
						new OA\Property(property: "data", properties: [
							new OA\Property(property: "pin", type: "string", example: "123456"),
							new OA\Property(property: "url", type: "string", example: "https://example.com"),
						],
							type: "object"
						),
					],
					type: "object"
				),
			),
			new OA\Response(
				response: 400,
				description: "If the `pin` or `url` params is empty",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 400),
						new OA\Property(property: "message", type: "string", example: "param `\$key` cannot be empty."),
						new OA\Property(property: "data", type: "null", example: null),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 404,
				description: "If the recipient is not found",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 404),
						new OA\Property(property: "message", type: "string", example: "Recipient not found"),
						new OA\Property(property: "data", properties: [
							new OA\Property(
								property: "pin",
								description: "Returns the received pin",
								type: "string",
								example: "123456",
							),
						]),
					],
					type: "object",
				),
			),
			new OA\Response(
				response: 500,
				description: "Default API Error",
				content: new OA\JsonContent(
					properties: [
						new OA\Property(property: "status", type: "integer", example: 500),
						new OA\Property(property: "message", type: "string", example: "Throws an exception message"),
						new OA\Property(property: "data", type: "null", example: null),
					],
					type: "object",
				),
			),

		],
	)]
	#[OA\Post(
		path: "/api/send/2",
		summary: "Fallback. Don't use it.",
		deprecated: true,
	)]
	#[Route(["/api/send", "/api/send/2"], name: "api_send_2", methods: ["POST"])]
	public function api_send_2(Request $request): Response
	{
		$data = $request->toArray();
		$pin = $data["pin"] ?? null;
		$url = $data["url"] ?? null;

		try {
			$pin = $this->LinkBridge->sanitizePinCode($pin);
			$url = $this->LinkBridge->sanitizeUrl($url);

			foreach (["pin", "url"] as $key) {
				if (empty($data[$key])) throw new Exception("param `$key` cannot be empty.", 400);
			}

			# Ищем получателя с его ссылкой.
			$LinkBridge = $this->LinkBridgeRepo->findOneBy(["pin" => $pin]);
			if (empty($LinkBridge)) throw new Exception(
				message: "Recipient not found",
				code: Response::HTTP_NOT_FOUND,
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
