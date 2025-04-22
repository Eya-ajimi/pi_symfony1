<?php


namespace App\Controller\backend;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventsadminController extends AbstractController{
    #[Route('/eventsadmin', name: 'app_eventsadmin')]
    public function index(): Response
    {
        return $this->render('backend/events.html.twig');
    }
}
