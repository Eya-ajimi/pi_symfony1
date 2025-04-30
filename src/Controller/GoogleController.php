<?php
// src/Controller/GoogleController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class GoogleController extends AbstractController
{
    #[Route('/connect/google/start', name: 'connect_google_start')]
    public function connect(Request $request): RedirectResponse
    {
        // 1. Récupère le rôle depuis l'URL
        $role = $request->query->get('role', 'CLIENT');

        // 2. Sauvegarde le rôle choisi dans la session
        $request->getSession()->set('google_auth_role', $role);

        // 3. Redirige immédiatement vers la route "knpu_connect" de Google
        return $this->redirectToRoute('connect_google');
    }
}
