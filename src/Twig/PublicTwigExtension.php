<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Service\LinkBridgeService;
use Twig\Extension\GlobalsInterface;
use Twig\Extension\AbstractExtension;
use Symfony\Component\HttpFoundation\RequestStack;

class PublicTwigExtension extends AbstractExtension implements GlobalsInterface
{

	public function __construct(
		private readonly RequestStack      $request,
		private readonly LinkBridgeService $LinkBridge,
	)
	{
	}

	/**
	 * Метод создаёт Публичные глобальные Twig переменные.
	 * @return array
	 */
	public function getGlobals(): array
	{
		return [
			"getLang" => $this->request->getCurrentRequest() ? $this->request->getCurrentRequest()->getLocale() : "??",
			"getCurrentYear" => date(format: "Y"),
			"getThisProjectName" => $_ENV["THIS_PROJECT_NAME"] ?? "LinkBridge",
			"getThisProjectVersion" => $_ENV["THIS_PROJECT_VERSION"] ?? 6,
			"getThisProjectBuildType" => $_ENV["APP_ENV"] === "dev" ? "[dev]" : "",
		];
	}

	/**
	 * Метод создаёт Публичные глобальные Twig(функции).
	 * @return TwigFunction[]
	 */
	public function getFunctions(): array
	{
		return [
			new TwigFunction("getQRCode", [$this->LinkBridge, "getQRCode"]),
			new TwigFunction("splitCode", [$this->LinkBridge, "splitCode"]),
		];
	}

	/**
	 * Метод создаём публичные глобальные Twig|пайпы.
	 * @return TwigFilter[]
	 */
	public function getFilters(): array
	{
		return [
			new TwigFilter("splitCode", [$this->LinkBridge, "splitCode"]),
		];
	}

}
