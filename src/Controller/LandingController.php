<?php
// src/Controller/LandingController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LandingController extends AbstractController
{
    #[Route('/landing', name: 'landing_home')]
    public function home(): Response
    {
        return $this->render('landing.html.twig');
    }
}
