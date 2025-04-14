<?php
namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventClient;
use App\Entity\Utilisateur;
use App\Repository\EventClientRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{

    #[Route('/events', name: 'app_events')]
    public function index(
        EventRepository $eventRepository,
        EventClientRepository $eventClientRepository, 
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Static user with ID 9
        $user = $entityManager->getReference(Utilisateur::class, 9);
        
        $dateString = $request->query->get('date');
        $date = null;
        
        if ($request->isMethod('GET') && $request->query->has('date')) {
            if (empty($dateString)) {
                $this->addFlash('error', 'Veuillez sélectionner une date.');
                return $this->redirectToRoute('app_events');
            }
            
            try {
                $date = new \DateTime($dateString);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Format de date invalide');
                return $this->redirectToRoute('app_events');
            }
        }

        if ($dateString) {
            try {
                $date = new \DateTime($dateString);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invalid date format');
            }
        }
    
        $events = $date 
            ? $eventRepository->findEventsByDate($date)
            : $eventRepository->findAllEvents();
    
        // Precompute participation status for each event
        $eventsWithParticipation = [];
        foreach ($events as $event) {
            $eventsWithParticipation[] = [
                'event' => $event,
                'isParticipating' => $eventClientRepository->isParticipating($user->getId(), $event->getId())
            ];
        }
    
        return $this->render('event/index.html.twig', [
            'eventsWithParticipation' => $eventsWithParticipation,
            'user' => $user
        ]);
    }

    #[Route('/events/participate/{id}', name: 'app_event_participate')]
    public function participate(
        Event $event,
        EventClientRepository $eventClientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Static user with ID 9
        $user = $entityManager->getReference(Utilisateur::class, 9);

        // Check if already participating
        if ($eventClientRepository->isParticipating($user->getId(), $event->getId())) {
            $this->addFlash('warning', 'You are already participating in this event.');
            return $this->redirectToRoute('app_events');
        }

        // Create new participation
        $participation = new EventClient();
        $participation->setIdClient($user);
        $participation->setIdEvent($event);
        $participation->setDate(date('Y-m-d'));

        $entityManager->persist($participation);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully joined the event!');
        return $this->redirectToRoute('app_events');
    }

    #[Route('/events/decline/{id}', name: 'app_event_decline')]
    public function decline(
        Event $event,
        EventClientRepository $eventClientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Static user with ID 9
        $user = $entityManager->getReference(Utilisateur::class, 9);
        
        $participation = $eventClientRepository->findOneBy([
            'idClient' => $user->getId(),
            'idEvent' => $event->getId()
        ]);
        
        if (!$participation) {
            $this->addFlash('error', 'You are not participating in this event.');
            return $this->redirectToRoute('app_events');
        }

        // Convert string dates to DateTime objects
        try {
            $eventStart = new \DateTime($event->getDateDebut());
            $now = new \DateTime();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Invalid date format in event data');
            return $this->redirectToRoute('app_events');
        }

        // Check if event starts within 24 hours
        $interval = $now->diff($eventStart);
        $hoursUntilEvent = ($interval->days * 24) + $interval->h;

        if ($hoursUntilEvent < 24 && $eventStart > $now) {
            $this->addFlash('error', 'You cannot decline participation within 24 hours of the event start time.');
            return $this->redirectToRoute('app_events');
        }

        $entityManager->remove($participation);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully declined participation.');
        return $this->redirectToRoute('app_events');
    }

    #[Route('/event/qrcode/{id}', name: 'app_event_qrcode')]
    public function generateQrCode(
        Event $event,
        EventClientRepository $eventClientRepository,
        \Endroid\QrCode\Builder\BuilderInterface $customQrCodeBuilder,
        EntityManagerInterface $entityManager
    ): Response {
        // Static user with ID 9
        $user = $entityManager->getReference(Utilisateur::class, 9);
        
        // Verify user is participating
        if (!$eventClientRepository->isParticipating($user->getId(), $event->getId())) {
            throw $this->createNotFoundException('You are not participating in this event');
        }

        // Generate QR code content
        $qrContent = json_encode([
            'event_id' => $event->getId(),
            'user_id' => $user->getId(),
            'event_name' => $event->getDescription(),
            'location' => $event->getEmplacement(),
            'dates' => $event->getDateDebut() . ' to ' . $event->getDateFin()
        ]);

        $qrCode = $customQrCodeBuilder
            ->data($qrContent)
            ->size(300)
            ->margin(20)
            ->build();

        return new Response($qrCode->getString(), 200, [
            'Content-Type' => $qrCode->getMimeType()
        ]);
    }

    #[Route('/event/qrcode/{id}/view', name: 'app_event_qrcode_view')]
    public function viewQrCode(
        Event $event,
        EventClientRepository $eventClientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Static user with ID 9
        $user = $entityManager->getReference(Utilisateur::class, 9);
        
        if (!$eventClientRepository->isParticipating($user->getId(), $event->getId())) {
            throw $this->createNotFoundException('You are not participating in this event');
        }

        return $this->render('event/qrcode.html.twig', [
            'event' => $event
        ]);
    }
}