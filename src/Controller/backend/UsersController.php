<?php


namespace App\Controller\backend;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class UsersController extends AbstractController{
    #[Route('/usersgestion', name: 'app_users')]
    public function index(): Response
    {
        return $this->render('backend/users.html.twig');
    }
}
