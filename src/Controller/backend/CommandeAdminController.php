<?php


namespace App\Controller\backend;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommandeAdminController extends AbstractController{
    #[Route('/commandeadmin', name: 'app_commande')]
    public function index(): Response
    {
        return $this->render('backend/billing.html.twig');
    }
}
