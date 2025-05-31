<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class TermsController extends AbstractController
{

	#[Route("/terms", "terms")]
	public function terms(): Response
	{
		return $this->redirectToRoute("terms_ios");
	}

	#[Route("/terms/iOS", "terms_ios")]
	public function terms_ios(): Response
	{
		return $this->render("terms/iOS/index.twig");
	}

}
