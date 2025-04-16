<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardAdminController extends AbstractController
{
    #[Route('/adminpage', name: 'dashboardadminn')]
    public function dashboard(): Response
    {
        return $this->render('backend/dashboard.html.twig');
    }
}
