<?php
// src/Controller/maria/ShopownerController.php
namespace App\Controller\maria;

use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\Utilisateur;
use App\Form\EventType;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Repository\ProduitRepository;
use App\Repository\LikedProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Produit;
use App\Entity\Discount;
use App\Repository\ScheduleRepository;

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
    public function products(
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository
    ): Response {
        // Get the current shop owner (user ID 8 in your example)
        $user = $entityManager->getRepository(Utilisateur::class)->find(8);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get products for this shop with their like counts
        $products = $produitRepository->findByShopIdWithLikeCounts($user->getId());

        return $this->render('maria_templates/products.html.twig', [
            'products' => $products
        ]);
    }
    #[Route('/discounts', name: 'discounts')]
    public function discounts(EntityManagerInterface $em): Response
    {
        // 1. Get the shop owner (user ID 8)
        $shopOwner = $em->getRepository(Utilisateur::class)->find(8);

        if (!$shopOwner) {
            throw $this->createNotFoundException('Shop owner not found');
        }

        // 2. Fetch ALL discounts (no filtering, handled by JavaScript)
        $discounts = $em->getRepository(Discount::class)->findBy(
            ['shop' => $shopOwner],
            ['startDate' => 'DESC'] // Newest first
        );

        // 3. Pass to template (no need for `currentFilter` since JS handles it)
        return $this->render('maria_templates/discounts.html.twig', [
            'discounts' => $discounts
        ]);
    }
    #[Route('/discount/new', name: 'discount_new')]
    public function newDiscount(): Response
    {
        // Your discount creation form logic
    }

    #[Route('/discount/edit/{id}', name: 'discount_edit')]
    public function editDiscount(int $id): Response
    {
        // Your discount edit form logic
    }

    #[Route('/discount/delete/{id}', name: 'discount_delete')]
    public function deleteDiscount(int $id): Response
    {
        // Your discount deletion logic
    }

    //Schedule 

    // src/Controller/ShopController.php
    #[Route('/schedule', name: 'schedule')]
    public function schedule(ScheduleRepository $scheduleRepo): Response
    {
        $schedules = $scheduleRepo->findBy(['shopId' => 8], ['dayOfWeek' => 'ASC']);

        return $this->render('maria_templates/schedule.html.twig', [
            'schedules' => $schedules
        ]);
    }
    // Events - Keep only this one version
    #[Route('/events', name: 'events')]
    public function events(EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $entityManager->getRepository(Utilisateur::class)->find(8);

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $event = new Event();
        $event->setOrganisateur($user);
        $event->setNomOrganisateur($user->getNom());

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                // Ensure dates are properly formatted before persisting
                $event->setDateDebut($form->get('dateDebut')->getData());
                $event->setDateFin($form->get('dateFin')->getData());

                $entityManager->persist($event);
                $entityManager->flush();

                $this->addFlash('success', 'Event created successfully!');
                return $this->redirectToRoute('events');
            } else {
                // Add error flash message if form is invalid
                $this->addFlash('error', 'Please correct the errors in the form.');
            }
        }

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