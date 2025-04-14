<?php
// src/Controller/maria/ShopownerController.php
namespace App\Controller\maria;

use App\Entity\Event;
use App\Entity\Utilisateur;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShopownerController extends AbstractController
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

    // Events - Keep only this one version
    #[Route('/events', name: 'events')]
    public function events(EntityManagerInterface $entityManager, Request $request): Response
    {
        // Get the static user (ID=8)
        $user = $entityManager->getRepository(Utilisateur::class)->find(8);
        
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        
        // Create new event
        $event = new Event();
        $event->setOrganisateur($user);
        $event->setNomOrganisateur($user->getNom()); // Set default organizer name
        
        $form = $this->createForm(EventType::class, $event);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();
            
            $this->addFlash('success', 'Event created successfully!');
            return $this->redirectToRoute('events');
        }
        
        // Get all events for this organizer
        $events = $entityManager->getRepository(Event::class)->findBy(['organisateur' => $user]);
        
        return $this->render('maria_templates/events.html.twig', [
            'form' => $form->createView(),
            'events' => $events,
            'user' => $user
        ]);
    }
    
    #[Route('/event/edit/{id}', name: 'event_edit')]
    public function editEvent(int $id, EntityManagerInterface $entityManager, Request $request): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // The form will automatically handle the DateTimeImmutable to string conversion
            // through the entity's setter methods
            $entityManager->flush();
            
            $this->addFlash('success', 'Event updated successfully!');
            return $this->redirectToRoute('events');
        }
        
        return $this->render('maria_templates/event_edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    #[Route('/event/delete/{id}', name: 'event_delete')]
    public function deleteEvent(int $id, EntityManagerInterface $entityManager): Response
    {
        $event = $entityManager->getRepository(Event::class)->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }
        
        $entityManager->remove($event);
        $entityManager->flush();
        
        $this->addFlash('success', 'Event deleted successfully!');
        return $this->redirectToRoute('events');
    }

    //Commands 

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