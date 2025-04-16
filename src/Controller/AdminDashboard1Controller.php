<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminDashboard1Controller extends AbstractController{
    #[Route('/admin/dashboard1', name: 'app_admin_dashboard1')]
    public function index(): Response
    {
        return $this->render('backend/dashboard.html.twig');
    }
}
