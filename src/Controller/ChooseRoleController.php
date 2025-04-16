<?php
// src/Controller/ChooseRoleController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChooseRoleController extends AbstractController
{
    #[Route('/choose-role', name: 'app_choose_role')]
    public function index(): Response
    {
        return $this->render('choose_role.html.twig');
    }

    
}
