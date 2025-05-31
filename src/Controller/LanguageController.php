<?php # TRANSLATE:

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class LanguageController extends AbstractController
{

	#[Route(path: ["/lang/{locale}"], name: "change_language")]
	public function changeLanguage(string $locale, Request $request): RedirectResponse
	{
		$request->getSession()->set("_locale", $locale);
		$url = $request->headers->get("referer");
		return new RedirectResponse($url ?: $this->generateUrl("home"));
	}

}
