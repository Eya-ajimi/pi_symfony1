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
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Label\Font\NotoSans;
use App\Repository\EventLikeRepository;
use App\Entity\EventLike;




class EventController extends AbstractController
{
    private EventClientRepository $eventClientRepository;

    public function __construct(
        EventClientRepository $eventClientRepository
    ) {
        $this->eventClientRepository = $eventClientRepository;
    }
    #[Route('/client/events', name: 'app_events')]
    public function index(
        EventRepository $eventRepository,
        EventClientRepository $eventClientRepository, 
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Static user with ID 7
        $user = $this->getUser();
        
        $dateString = $request->query->get('date');
        $date = null;
        $eventRepository->deletePastEvents();
        
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

    #[Route('/client/events/participate/{id}', name: 'app_event_participate', methods: ['GET', 'POST'])]
    public function participate(
        Event $event,
        Request $request,
        EventClientRepository $eventClientRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        $existingParticipation = $eventClientRepository->findOneBy([
            'idClient' => $user->getId(),
            'idEvent' => $event->getId()
        ]);

        if ($request->isMethod('POST')) {
            $places = (int) $request->request->get('places', 1);
            
            // Server-side validation
            $errors = [];
            
            // Validate places input
            if ($places < 1 || $places > 5) {
                $errors[] = 'Le nombre de places doit être compris entre 1 et 5';
            }
            
            // Check event capacity
            if ($event->getMaxParticipants() !== null) {
                $currentParticipants = $eventClientRepository->getTotalParticipantsCount($event->getId());
                $availablePlaces = $event->getMaxParticipants() - $currentParticipants;
                
                if ($availablePlaces <= 0) {
                    $errors[] = 'Cet événement a atteint sa capacité maximale';
                } elseif ($places > $availablePlaces) {
                    $errors[] = sprintf('Il ne reste que %d places disponibles', $availablePlaces);
                }
            }

            // If validation errors, show them
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error);
                }
            } else {
                // Process valid submission
                if ($existingParticipation) {
                    $existingParticipation->setPlaces($existingParticipation->getPlaces() + $places);
                    $message = sprintf('Vous avez réservé %d places supplémentaires!', $places);
                } else {
                    $participation = new EventClient();
                    $participation->setIdClient($user);
                    $participation->setIdEvent($event);
                    $participation->setDate(date('Y-m-d'));
                    $participation->setPlaces($places);
                    $entityManager->persist($participation);
                    $message = sprintf('Vous avez réservé %d places avec succès!', $places);
                }
                
                $entityManager->flush();
                $this->addFlash('success', $message);
                return $this->redirectToRoute('app_events');
            }
        }

        return $this->render('event/participate.html.twig', [
            'event' => $event,
            'existingParticipation' => $existingParticipation,
            'maxPlaces' => 5,
            'availablePlaces' => $event->getMaxParticipants() ? 
                $event->getMaxParticipants() - $eventClientRepository->getTotalParticipantsCount($event->getId()) : 
                null
        ]);
    }

    #[Route('/client/events/decline/{id}', name: 'app_event_decline', methods: ['GET', 'POST'])]
public function decline(
    Event $event,
    Request $request,
    EventClientRepository $eventClientRepository,
    EntityManagerInterface $entityManager
): Response {
    $user = $this->getUser();
    
    $participation = $eventClientRepository->findOneBy([
        'idClient' => $user->getId(),
        'idEvent' => $event->getId()
    ]);
    
    if (!$participation) {
        $this->addFlash('error', 'You are not participating in this event.');
        return $this->redirectToRoute('app_events');
    }

    if ($request->isMethod('POST')) {
        $placesToRemove = (int) $request->request->get('places', 1);
        $placesToRemove = max(1, min($participation->getPlaces(), $placesToRemove));

        try {
            $eventStart = new \DateTime($event->getDateDebutString());
            $eventEnd = new \DateTime($event->getDateFinString());
            $now = new \DateTime();
        } catch (\Exception $e) {
            $this->addFlash('error', 'Invalid date format in event data');
            return $this->redirectToRoute('app_events');
        }

        // Check if event has already started (now is between start and end dates)
        $isDuringEvent = ($now >= $eventStart && $now <= $eventEnd);
        
        // Check if event starts within 24 hours
        $interval = $now->diff($eventStart);
        $hoursUntilEvent = ($interval->days * 24) + $interval->h;

        if (($hoursUntilEvent < 24 && $eventStart > $now) || $isDuringEvent) {
            $this->addFlash(
                'error', 
                'You cannot decline participation within 24 hours of the event start time or during the event.'
            );
            return $this->redirectToRoute('app_events');
        }

        if ($placesToRemove >= $participation->getPlaces()) {
            // Remove all places
            $entityManager->remove($participation);
            $this->addFlash('success', 'You have successfully declined all your reservations.');
        } else {
            // Remove some places
            $participation->setPlaces($participation->getPlaces() - $placesToRemove);
            $this->addFlash('success', sprintf('You have successfully declined %d places.', $placesToRemove));
        }

        $entityManager->flush();
        return $this->redirectToRoute('app_events');
    }

    return $this->render('event/decline.html.twig', [
        'event' => $event,
        'participation' => $participation
    ]);
}

    #[Route('/client/event/qrcode/{id}', name: 'app_event_qrcode')]
    public function generateQrCode(
        Event $event,
        EventClientRepository $eventClientRepository
    ): Response {
        $user = $this->getUser();

        $participation = $eventClientRepository->findOneBy([
            'idClient' => $user->getId(),
            'idEvent' => $event->getId()
        ]);

        if (!$participation || $participation->getPlaces() < 1) {
            throw $this->createNotFoundException('You are not participating in this event');
        }

        $qrContent = json_encode([
            'event_id' => $event->getId(),
            'user_id' => $user->getId(),
            'reserved_places' => $participation->getPlaces(),
            'verification_code' => substr(md5($user->getId().$event->getId()), 0, 8)
        ]);

        // Create the QR Code with content
        $qrCode = new \Endroid\QrCode\QrCode($qrContent);
        
        // Use a PngWriter to generate the QR code image
        $writer = new \Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode); // This returns a QrCodeResult object

        // Get the image as a string
        $qrImage = $result->getString(); // Get the string representation of the QR code image

        return new Response(
            $qrImage,
            Response::HTTP_OK,
            ['Content-Type' => 'image/png']
        );
    }








    #[Route('/client/event/qrcode/{id}/view', name: 'app_event_qrcode_view')]
    public function viewQrCode(
        Event $event,
        EventClientRepository $eventClientRepository
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException('You must be logged in.');
        }

        $participation = $eventClientRepository->findOneBy([
            'idClient' => $user->getId(),
            'idEvent' => $event->getId()
        ]);

        if (!$participation || $participation->getPlaces() < 1) {
            throw $this->createNotFoundException('You are not participating in this event');
        }

        return $this->render('event/qrcode.html.twig', [
            'event' => $event,
            'participation' => $participation
        ]);
    }

    #[Route('/client/events/like/{id}', name: 'app_event_like', methods: ['POST'])]
    public function like(
        Event $event,
        Request $request,
        EntityManagerInterface $entityManager,
        EventLikeRepository $likeRepository
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        $submittedToken = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('like-event', $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token');
            return $this->redirectToRoute('app_events');
        }

        $existingLike = $likeRepository->findOneBy([
            'event' => $event,
            'user' => $user
        ]);

        if ($existingLike) {
            $entityManager->remove($existingLike);
            $this->addFlash('success', 'Event unliked successfully');
        } else {
            $like = new EventLike();
            $like->setEvent($event);
            $like->setUser($user);
            $entityManager->persist($like);
            $this->addFlash('success', 'Event liked successfully');
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_events');
    }

    #[Route('/client/events/most-liked', name: 'app_events_most_liked')]
    public function mostLiked(EventLikeRepository $likeRepository, EventRepository $eventRepository): Response
    {
        $mostLikedData = $likeRepository->getMostLikedEvents(5);
        
        // Get full event entities for the results
        $mostLikedEvents = [];
        foreach ($mostLikedData as $data) {
            $event = $eventRepository->find($data['eventId']);
            if ($event) {
                $mostLikedEvents[] = [
                    'event' => $event,
                    'likeCount' => $data['likeCount']
                ];
            }
        }
        
        return $this->render('event/most_liked.html.twig', [
            'mostLikedEvents' => $mostLikedEvents
        ]);
    }
    #[Route('/client/events/voice-search', name: 'app_events_voice_search', methods: ['POST'])]
    public function voiceSearch(Request $request, EventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        $query = $request->request->get('query', '');
        
        // Process voice command
        $date = null;
        $events = [];
        
        // Check for date-related commands
        if (preg_match('/(today|tomorrow|next week|next month|this weekend)/i', $query, $matches)) {
            $dateCommand = strtolower($matches[1]);
            
            switch ($dateCommand) {
                case 'today':
                    $date = new \DateTime();
                    break;
                case 'tomorrow':
                    $date = (new \DateTime())->modify('+1 day');
                    break;
                case 'next week':
                    $date = (new \DateTime())->modify('next monday');
                    break;
                case 'next month':
                    $date = (new \DateTime())->modify('first day of next month');
                    break;
                case 'this weekend':
                    $date = new \DateTime('this saturday');
                    break;
            }
            
            $events = $date ? $eventRepository->findEventsByDate($date) : [];
        } 
        // Check for "most liked" command
        elseif (preg_match('/(most liked|popular|top events)/i', $query)) {
            return $this->redirectToRoute('app_events_most_liked');
        }
        // Check for event name search
        else {
            $events = $eventRepository->searchByName($query);
        }
        
        // Precompute participation status for each event
        $eventsWithParticipation = [];
        foreach ($events as $event) {
            $eventsWithParticipation[] = [
                'event' => $event,
                'isParticipating' => $this->eventClientRepository->isParticipating($user->getId(), $event->getId())
            ];
        }
        
        return $this->render('event/index.html.twig', [
            'eventsWithParticipation' => $eventsWithParticipation,
            'user' => $user,
            'voiceQuery' => $query
        ]);
    }
}