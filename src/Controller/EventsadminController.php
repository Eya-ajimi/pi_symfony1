<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Utilisateur;
use App\Repository\EventClientRepository;
use App\Repository\EventRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Enums\Role;

final class EventsadminController extends AbstractController
{
    #[Route('/eventsadmin', name: 'app_eventsadmin')]
    public function index(
        UtilisateurRepository $userRepository,
        EventRepository $eventRepository
    ): Response {
        // Get all shops (organisateurs)
        $shops = $userRepository->findBy(['role' => Role::SHOPOWNER]);
        
        // Get events for each shop
        $shopsWithEvents = [];
        foreach ($shops as $shop) {
            $events = $eventRepository->findBy(['organisateur' => $shop]);
            $shopsWithEvents[] = [
                'shop' => $shop,
                'eventCount' => count($events),
            ];
        }

        return $this->render('backend/events.html.twig', [
            'shops' => $shopsWithEvents,
        ]);
    }

    #[Route('/eventsadmin/statistics/{id}', name: 'app_eventsadmin_statistics')]
    public function statistics(
        Utilisateur $shop,
        EventRepository $eventRepository,
        EventClientRepository $eventClientRepository,
        Request $request
    ): Response {
        // Get all events for this shop
        $events = $eventRepository->findBy(['organisateur' => $shop]);
        
        // Get selected year (default to current year)
        $selectedYear = $request->query->get('year', date('Y'));
        
        // Prepare monthly statistics
        $monthlyStats = [];
        $totalParticipants = 0;
        
        for ($month = 1; $month <= 12; $month++) {
            $monthStart = new \DateTime("$selectedYear-$month-01");
            $monthEnd = new \DateTime("$selectedYear-$month-01 last day of this month");
            
            $monthParticipants = 0;
            
            foreach ($events as $event) {
                // Check if event overlaps with this month
                $eventStart = new \DateTime($event->getDateDebut());
                $eventEnd = new \DateTime($event->getDateFin());
                
                if ($eventStart <= $monthEnd && $eventEnd >= $monthStart) {
                    // Get participants for this event
                    $participants = $eventClientRepository->createQueryBuilder('ec')
                        ->select('COUNT(ec.idClient)')
                        ->where('ec.idEvent = :event')
                        ->setParameter('event', $event)
                        ->getQuery()
                        ->getSingleScalarResult();
                    
                    $monthParticipants += $participants;
                }
            }
            
            $monthlyStats[] = [
                'month' => $monthStart->format('F'),
                'participants' => $monthParticipants,
            ];
            
            $totalParticipants += $monthParticipants;
        }
        
        // Get years with events for dropdown
        $years = [];
        foreach ($events as $event) {
            $year = (new \DateTime($event->getDateDebut()))->format('Y');
            if (!in_array($year, $years)) {
                $years[] = $year;
            }
        }
        rsort($years); // Show most recent first
        
        return $this->render('backend/event_statistics.html.twig', [
            'shop' => $shop,
            'monthlyStats' => $monthlyStats,
            'totalParticipants' => $totalParticipants,
            'selectedYear' => $selectedYear,
            'availableYears' => $years,
        ]);
    }
}