<?php
// src/Controller/ParkingController.php
namespace App\Controller;

use App\Entity\PlaceParking;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Service\TwilioSmsService;
use App\Form\ParkingSpotType;
use App\Repository\UtilisateurRepository;
use App\Repository\PlaceParkingRepository;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
class ParkingController extends AbstractController
{
    private $placeParkingRepository;
    private $reservationRepository;
    private $entityManager;

    public function __construct(
        PlaceParkingRepository $placeParkingRepository,
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->placeParkingRepository = $placeParkingRepository;
        $this->reservationRepository = $reservationRepository;
        $this->entityManager = $entityManager;
    }

    private function createDemoSpotsForFloor(string $floorValue): void
    {
        $zones = ['A', 'B', 'C'];

        for ($i = 1; $i <= 20; $i++) {
            $spot = new PlaceParking();
            $spot->setZone($zones[array_rand($zones)]);
            $spot->setFloor($floorValue);
            $spot->setStatut(mt_rand(0, 1) ? 'free' : 'taken');

            $this->entityManager->persist($spot);
        }

        $this->entityManager->flush();
    }
    private function checkExpiredReservations(): void
{
    $expiredReservations = $this->reservationRepository->findExpiredReservations();
    
    foreach ($expiredReservations as $reservation) {
        // Update reservation status
        $reservation->setStatut('expired');
        
        // Free up the parking spot
        $spot = $reservation->getPlaceParking();
        if ($spot) {
            $spot->setStatut('free');
            $this->entityManager->persist($spot);
        }
        
        $this->entityManager->persist($reservation);
    }

    if (count($expiredReservations) > 0) {
        $this->entityManager->flush();
    }
}
    #[Route('/parking', name: 'app_parking')]
    public function index(Request $request): Response
    {
        $this->checkExpiredReservations();
        // Default to floor 1
        $floor = $request->query->get('floor', 1);
        $floorValue = 'Level ' . $floor;

        // Get only spots for this floor
        $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
        if (empty($spots)) {
            $this->createDemoSpotsForFloor($floorValue);
            $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
        }

        // Calculate stats
        $availableSpots = $this->placeParkingRepository->count([
            'floor' => $floorValue,
            'statut' => 'free'
        ]);

        $totalSpots = $this->placeParkingRepository->count(['floor' => $floorValue]);
        $occupancyRate = $totalSpots > 0 ? round(($totalSpots - $availableSpots) / $totalSpots * 100) : 0;

        return $this->render('parking/park.html.twig', [
            'parking_spots' => $spots,
            'available_spots_count' => $availableSpots,
            'occupancy_rate' => $occupancyRate,
            'floor' => $floor,
        ]);
    }


    


    #[Route('/parking/cancel-reservation/{id}', name: 'app_parking_cancel_reservation', methods: ['POST'])]
    public function cancelReservation(Reservation $reservation): Response
    {
        // Update reservation status
        $reservation->setStatut('cancelled');

        // Update parking spot status
        $spot = $reservation->getPlaceParking();
        if ($spot) {
            $spot->setStatut('free');
            $this->entityManager->persist($spot);
        }

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation cancelled successfully');
        return $this->redirectToRoute('app_parking_my_reservations');
    }
    
    #[Route('/parking/reserve/{id}', name: 'app_parking_reserve', methods: ['GET', 'POST'])]
    public function reserveSpot(
        Request $request, 
        PlaceParking $spot,
        TwilioSmsService $smsService,
        UtilisateurRepository $userRepository
    ): Response {
        $user=$this->getUser();
        $currentUser=$user->getId();
        // Check if spot is available
        if ($spot->getStatut() !== 'free') {
            $this->addFlash('error', 'This spot is not available for reservation');
            return $this->redirectToRoute('app_parking');
        }

        $reservation = new Reservation();
        $reservation->setPlaceParking($spot);
        $reservation->setIdUtilisateur($currentUser); 

        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calculate price based on duration and vehicle type
            $start = $reservation->getDateReservation();
            $end = $reservation->getDateExpiration();
            $now = new \DateTime();
        if ($start < $now) {
            $this->addFlash('error', 'Start time cannot be in the past');
            return $this->redirectToRoute('app_parking_reserve', ['id' => $spot->getId()]);
        }
        
        if ($end <= $start) {
            $this->addFlash('error', 'End time must be after start time');
            return $this->redirectToRoute('app_parking_reserve', ['id' => $spot->getId()]);
        }
            $duration = $end->diff($start)->h; // Get hours difference

            $pricing = [
                'Motorcycle' => 3,  // Changed from 'motorcycle'
                'Compact' => 5,     // Changed from 'sedan'
                'SUV' => 7,         // Changed from 'suv'
                'Van' => 8          // Changed from 'van'
            ];

            $vehicleType = $reservation->getVehicleType();
            if (!array_key_exists($vehicleType, $pricing)) {
                $this->addFlash('error', 'Invalid vehicle type selected');
                return $this->redirectToRoute('app_parking');
            }
            $basePrice = $pricing[$vehicleType] * $duration;


            // Add car wash price if selected
            $carWashOptions = [
                'Basic' => 8.99,    // Changed from 'basic'
                'Premium' => 14.99, // Changed from 'premium'
                'Deluxe' => 19.99   // Changed from 'deluxe'
            ];

            $carWashType = $reservation->getCarWashType();
            if ($carWashType && $carWashType !== 'None') {
                if (!array_key_exists($carWashType, $carWashOptions)) {
                    $this->addFlash('error', 'Invalid car wash type selected');
                    return $this->redirectToRoute('app_parking');
                }
                $basePrice += $carWashOptions[$carWashType];
            }
            if ($carWashType && isset($carWashOptions[$carWashType])) {
                $basePrice += $carWashOptions[$carWashType];
            }

            $reservation->setPrice($basePrice);
            $reservation->setStatut('active');

            // Update parking spot status
            $spot->setStatut('reserved');

            $this->entityManager->persist($reservation);
            $this->entityManager->persist($spot);
            $this->entityManager->flush();
            
            $user = $userRepository->find($currentUser);
            $phoneNumber = $user->getTelephone();
            if ($phoneNumber) {
                // Format the reservation details for SMS
                $message = sprintf(
                    "Thank you for your reservation!\n" .
                    "Spot: %s%s\n" .
                    "Floor: %s\n" .
                    "From: %s\n" .
                    "To: %s\n" .
                    "Vehicle: %s\n" .
                    "Total: %.2f TND",
                    $spot->getZone(),
                    $spot->getId(),
                    str_replace('Level ', '', $spot->getFloor()),
                    $reservation->getDateReservation()->format('Y-m-d H:i'),
                    $reservation->getDateExpiration()->format('Y-m-d H:i'),
                    $reservation->getVehicleType(),
                    $reservation->getPrice()
                );
                // Send SMS
            $smsService->sendSms($phoneNumber, $message);
            }
            $this->addFlash('success', 'Reservation created successfully!');
            return $this->redirectToRoute('app_parking');
        }

        return $this->render('parking/reservation_form.html.twig', [
            'spot' => $spot,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/parking/my-reservations', name: 'app_parking_my_reservations')]
    public function myReservations(): Response
    {
        $user=$this->getUser();
        // Static user ID 7 for demonstration
        $userId = $user->getId();

        $reservations = $this->reservationRepository->findAllReservationsForUser($userId);

        // Structure the results to match your template
        $structuredReservations = array_map(function($reservation) {
            return [
                'reservation' => $reservation,
                'spot' => $reservation->getPlaceParking()
            ];
        }, $reservations);

        // Calculate statistics
        $activeCount = count(array_filter($reservations, fn($r) => $r->getStatut() === 'active'));
        $thisMonthCount = count(array_filter($reservations, fn($r) =>
            $r->getDateReservation()->format('Y-m') === (new \DateTime())->format('Y-m')
        ));

        return $this->render('parking/reservations.html.twig', [
            'reservations' => $structuredReservations,
            'active_count' => $activeCount,
            'this_month_count' => $thisMonthCount,
            'total_spent' => array_reduce($reservations, fn($total, $r) => $total + $r->getPrice(), 0)
        ]);
    }

    #[Route('/admin/parking/floor/{floor}', name: 'app_parking_floor', methods: ['GET'])]
    public function getFloorSpots(int $floor): JsonResponse
    {
        $floorValue = 'Level ' . $floor;
        $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);

        // Create demo spots if none exist
        if (empty($spots)) {
            $this->createDemoSpotsForFloor($floorValue);
            $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
        }

        $formattedSpots = array_map(function($spot) {
            return [
                'id' => $spot->getId(),
                'zone' => $spot->getZone(),
                'floor' => $spot->getFloor(),
                'statut' => $spot->getStatut()
            ];
        }, $spots);

        return $this->json([
            'spots' => $formattedSpots,
            'stats' => [
                'total' => count($spots),
                'available' => $this->placeParkingRepository->count([
                    'floor' => $floorValue,
                    'statut' => 'free'
                ]),
                'occupied' => $this->placeParkingRepository->count([
                    'floor' => $floorValue,
                    'statut' => ['taken', 'reserved']
                ])
            ]
        ]);
    }
    #[Route('/admin/parking/add', name: 'admin_parking_add')]
    public function addSpot(Request $request): Response
    {
        $spot = new PlaceParking();
        $form = $this->createForm(ParkingSpotType::class, $spot);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Handle zone formatting for new spots
            $zone = $spot->getZone();
            if (preg_match('/^[A-Za-z]$/', $zone)) {
                $spot->setZone('Zone ' . strtoupper($zone));
            }

            $this->entityManager->persist($spot);
            $this->entityManager->flush();

            $this->addFlash('success', 'Parking spot added successfully!');
            return $this->redirectToRoute('app_parking_spots', ['floor' => 1]); // Updated redirect
        }

        return $this->render('parking/add_spot.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/admin/parking/edit/{id}', name: 'admin_parking_edit')]
    public function editSpot(Request $request, PlaceParking $spot): Response
    {
        // Store original zone value
        $originalZone = $spot->getZone();
        
        // If zone starts with "Zone ", strip it for the form
        if (strpos($originalZone, 'Zone ') === 0) {
            $spot->setZone(substr($originalZone, 5));
        }
    
        $form = $this->createForm(ParkingSpotType::class, $spot);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the submitted zone value
            $zone = $spot->getZone();
    
            // If it's a single letter (A-Z), prepend "Zone "
            if (preg_match('/^[A-Za-z]$/', $zone)) {
                $spot->setZone('Zone ' . strtoupper($zone));
            } else {
                // If it's not a single letter, restore the original value
                $spot->setZone($originalZone);
            }
    
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Parking spot updated successfully!');
            // Extract floor from spot and redirect back to same floor
            $floor = str_replace('Level ', '', $spot->getFloor());
            return $this->redirectToRoute('app_parking_spots', ['floor' => $floor]);
        }
    
        // Restore original value if form wasn't submitted
        if (!$form->isSubmitted()) {
            $spot->setZone($originalZone);
        }
    
        return $this->render('parking/edit_spot.html.twig', [
            'form' => $form->createView(),
            'spot' => $spot,
        ]);
    }
    #[Route('/admin/parking/update-spot/{id}', name: 'admin_parking_update_spot', methods: ['POST'])]
    public function updateSpot(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $spot = $this->placeParkingRepository->find($id);

        if (!$spot) {
            return $this->json([
                'success' => false,
                'message' => 'Spot not found'
            ], 404);
        }

        try {
            $spot->setStatut($data['status']);
            $entityManager->flush();

            return $this->json([
                'success' => true,
                'message' => 'Spot updated successfully'
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Failed to update spot: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/admin/parking/delete/{id}', name: 'admin_parking_delete')]
    public function deleteSpot(PlaceParking $spot): Response
    {
        // First get the floor before deleting
        $floor = str_replace('Level ', '', $spot->getFloor());

        $this->entityManager->remove($spot);
        $this->entityManager->flush();

        $this->addFlash('success', 'Parking spot deleted successfully!');
        return $this->redirectToRoute('app_parking_spots', ['floor' => $floor]); // Updated redirect
    }
    #[Route('/admin/reservation/edit/{id}', name: 'admin_reservation_edit')]
    public function editReservation(Reservation $reservation): Response
    {
        return $this->render('parking/edit_reservation.html.twig', [
            'reservation' => $reservation
        ]);
    }
    #[Route('/admin/reservation/update/{id}', name: 'admin_reservation_update', methods: ['POST'])]
    public function updateReservation(Request $request, Reservation $reservation): Response
    {
        $startTime = new \DateTime($request->request->get('start_time'));
        $endTime = new \DateTime($request->request->get('end_time'));
        $status = $request->request->get('status');
        $vehicleType = $request->request->get('vehicle_type');
        $price = $request->request->get('price');

        $reservation->setDateReservation($startTime);
        $reservation->setDateExpiration($endTime);
        $reservation->setStatut($status);
        $reservation->setVehicleType($vehicleType);
        $reservation->setPrice($price);

        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation updated successfully!');
        return $this->redirectToRoute('app_parking_reservations');
    }
    #[Route('/admin/reservation/delete/{id}', name: 'admin_reservation_delete')]
    public function deleteReservation(Reservation $reservation): Response
    {
        // Free up the parking spot if it exists
        if ($reservation->getPlaceParking()) {
            $reservation->getPlaceParking()->setStatut('free');
            $this->entityManager->persist($reservation->getPlaceParking());
        }

        $this->entityManager->remove($reservation);
        $this->entityManager->flush();

        $this->addFlash('success', 'Reservation deleted successfully!');
        return $this->redirectToRoute('app_parking_spots');
    }
// Add these new routes to your ParkingController

    #[Route('/admin/parking/spots/{floor}', name: 'app_parking_spots', defaults: ['floor' => 1])]
    public function adminSpots(int $floor): Response
    {
        $floorValue = 'Level ' . $floor;
        $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);

        // Create demo spots if none exist
        if (empty($spots)) {
            $this->createDemoSpotsForFloor($floorValue);
            $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
        }

        return $this->render('parking/spotsAdmin.html.twig', [
            'spots' => $spots,
            'current_floor' => $floor  // Pass current floor to template
        ]);
    }
    #[Route('/admin/parking/reservations', name: 'app_parking_reservations')]
    public function adminReservations(): Response
    {
        $allReservations = $this->reservationRepository->findAll();

        return $this->render('parking/reservationsAdmin.html.twig', [
            'all_reservations' => $allReservations,
        ]);
    }

    #[Route('/admin/parking/statistics', name: 'app_parking_statistics')]
    public function adminStatistics(): Response
    {
        $floorValue = 'Level 1';
        $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
        $allReservations = $this->reservationRepository->findAll();

        $totalSpots = count($spots);
        $availableSpots = $this->placeParkingRepository->count([
            'floor' => $floorValue,
            'statut' => 'free'
        ]);
        $occupiedSpots = $this->placeParkingRepository->count([
            'floor' => $floorValue,
            'statut' => ['taken', 'reserved']
        ]);
        $occupancyRate = $totalSpots > 0 ? round(($totalSpots - $availableSpots) / $totalSpots * 100) : 0;
        $activeReservations = $this->reservationRepository->count(['statut' => 'active']);

        $dailyRevenue = 0;
        $monthlyRevenue = 0;
        foreach ($allReservations as $reservation) {
            if ($reservation->getDateReservation() > new \DateTime('-1 day')) {
                $dailyRevenue += $reservation->getPrice();
            }
            if ($reservation->getDateReservation() > new \DateTime('-1 month')) {
                $monthlyRevenue += $reservation->getPrice();
            }
        }

        $floorStats = [];
        $floors = ['Level 1', 'Level 2', 'Level 3'];
        foreach ($floors as $floor) {
            $total = $this->placeParkingRepository->count(['floor' => $floor]);
            $available = $this->placeParkingRepository->count(['floor' => $floor, 'statut' => 'free']);
            $occupied = $this->placeParkingRepository->count(['floor' => $floor, 'statut' => ['taken', 'reserved']]);
            $floorStats[] = [
                'floor' => $floor,
                'total' => $total,
                'available' => $available,
                'occupied' => $occupied,
                'occupancy_rate' => $total > 0 ? round($occupied / $total * 100) : 0
            ];
        }

        return $this->render('parking/statisticsAdmin.html.twig', [
            'total_spots' => $totalSpots,
            'available_spots' => $availableSpots,
            'occupancy_rate' => $occupancyRate,
            'active_reservations' => $activeReservations,
            'daily_revenue' => $dailyRevenue,
            'monthly_revenue' => $monthlyRevenue,
            'floor_stats' => $floorStats,
        ]);
    }
}