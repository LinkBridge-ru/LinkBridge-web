<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\LinkBridgeService;
use App\Repository\LinkBridgeRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class HomeController extends AbstractController
{

	public function __construct(
		private readonly LinkBridgeService    $LinkBridge,
		private readonly LinkBridgeRepository $LinkBridgeRepo,
	)
	{
	}

	#[Route(path: "/", name: "home", methods: "GET")]
	public function index(SessionInterface $session): Response
	{
		# Создаём сессию.
		if (!$session->isStarted()) $session->start();
		if (!$session->has("_initialized")) $session->set("_initialized", true);

		# Очищаем старые сессии.
		$this->LinkBridgeRepo->cleanupOldSessions();

		# Получаем сессию от браузера.
		$ssid = $session->getId();

		# Поиск записи.
		$link = $this->LinkBridgeRepo->findOneBy(["session_id" => $ssid]);

		# Обновляем время последнего обращения.
		if ($link) {
			$pin = $link->getPin();
			$this->LinkBridgeRepo->updatePinRequest($link);
		} else {
			# Создаём запись в БД.
			$pin = $this->LinkBridge->getPinCode();
			$this->LinkBridgeRepo->registerNewRecipient(ssid: $ssid, pin: $pin);
		}

		return $this->render("home/index.twig", [
			"PIN" => $pin ?? "000000",
		]);
	}

}
