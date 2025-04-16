<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParkingController extends AbstractController{
    #[Route('/parking', name: 'app_parking')]
    public function index(): Response
    {
        return $this->render('backend/parking.html.twig', [
            'controller_name' => 'ParkingController',
        ]);
    }
}
