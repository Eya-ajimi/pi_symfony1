<?php
// src/Controller/TestController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        return $this->render('test/index.html.twig', [
            'title' => 'My Test Dashboard',
            'welcome_message' => 'This is a temporary test page for my admin dashboard!'
        ]);
    }
}