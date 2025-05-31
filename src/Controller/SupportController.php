<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class SupportController extends AbstractController
{

	#[Route("/support", "support")]
	public function support(): Response
	{
		return $this->render("support/index.twig");
	}

}
