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
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    public function index(
        EventRepository $eventRepository,
        Request $request
    ): Response {
        $date = $request->query->get('date');
        
        $events = $date 
            ? $eventRepository->findEventsByDate($date)
            : $eventRepository->findAllEvents();

        return $this->render('event/index.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/events/participate/{id}', name: 'app_event_participate')]
    public function participate(
        Event $event,
        EventClientRepository $eventClientRepository,
        #[CurrentUser] Utilisateur $user,
        EntityManagerInterface $entityManager
    ): Response {
        // Check if already participating
        if ($eventClientRepository->isParticipating($user->getId(), $event->getId())) {
            $this->addFlash('warning', 'You are already participating in this event.');
            return $this->redirectToRoute('app_events');
        }

        // Create new participation
        $participation = new EventClient();
        $participation->setClient($user);
        $participation->setEvent($event);
        $participation->setDate(date('Y-m-d'));

        $entityManager->persist($participation);
        $entityManager->flush();

        $this->addFlash('success', 'You have successfully joined the event!');
        return $this->redirectToRoute('app_events');
    }
}