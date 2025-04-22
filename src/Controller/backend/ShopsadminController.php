<?php


namespace App\Controller\backend;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ShopsadminController extends AbstractController{
    #[Route('/shopsadmin', name: 'app_shopsadmin')]
    public function index(): Response
    {
        return $this->render('backend/shopsadmin.html.twig', [
            'controller_name' => 'ShopsadminController',
        ]);
    }
}
