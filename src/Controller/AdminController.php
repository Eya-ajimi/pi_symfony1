<?php
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{   
    //Main

    #[Route('/admindashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('maria_templates/admindashboard.html.twig');
    }

    //Products

    #[Route('/products', name: 'products')]
    public function products(): Response
    {
        return $this->render('maria_templates/products.html.twig');
    }

    //Discounts

    #[Route('/discounts', name: 'discounts')]
    public function discounts(): Response
    {
        return $this->render('maria_templates/discounts.html.twig');
    }

   
    //Schedule 

    #[Route('/schedule', name: 'schedule')]
    public function schedule(): Response
    {
        return $this->render('maria_templates/schedule.html.twig');
    }

    //events 

     #[Route('/events', name: 'events')]
     public function events(): Response
     {
         return $this->render('maria_templates/events.html.twig');
     }

    //Schedule 

    #[Route('/commands', name: 'commands')]
    public function commands(): Response
    {
        return $this->render('maria_templates/commands.html.twig');
    }
     //Profile 

     #[Route('/profile', name: 'profile')]
     public function profile(): Response
     {
         return $this->render('maria_templates/profile.html.twig');
     }


}