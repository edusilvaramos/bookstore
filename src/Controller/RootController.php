<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class RootController extends AbstractController
{
    #[Route('/', name: 'app_root', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('app_home_index');
    }
}
