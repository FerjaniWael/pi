<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UnverifiedController extends AbstractController
{
    #[Route('/unverified', name: 'app_unverified')]
    public function index(): Response
    {
        $this->addFlash('warning', 'Your account is not yet verified.');
        return $this->redirectToRoute('unverified');
    }
}
