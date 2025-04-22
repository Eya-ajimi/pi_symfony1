<?php


namespace App\Controller\backend;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParkingAdminController extends AbstractController{
    #[Route('/parkingadmin', name: 'app_parkingadmin')]
    public function index(): Response
    {
        return $this->render('backend/parking.html.twig', [
            'controller_name' => 'ParkingController',
        ]);
    }


        

      

}
