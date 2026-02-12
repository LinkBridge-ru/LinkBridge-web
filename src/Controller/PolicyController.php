<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PolicyController extends AbstractController
{

	#[Route("/policy", "policy")]
	public function policy(): Response
	{
		return $this->render("policy/index.twig");
	}

	#[Route("/policy/iOS", "policy_ios")]
	public function policy_ios(): Response
	{
		return $this->render("policy/iOS/index.twig");
	}

}
