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
use Dompdf\Dompdf;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
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
        $user = $this->getUser();
        $currentUser = $user->getId();
    
        if ($spot->getStatut() !== 'free') {
            $this->addFlash('error', 'This spot is not available for reservation');
            return $this->redirectToRoute('app_parking');
        }
    
        $reservation = new Reservation();
        $reservation->setPlaceParking($spot);
        $reservation->setIdUtilisateur($currentUser);
    
        $form = $this->createForm(ReservationType::class, $reservation);
        $form->handleRequest($request);
    
        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $start = $reservation->getDateReservation();
                $end = $reservation->getDateExpiration();
                $now = new \DateTime();
                
                // Additional validation checks
                if ($start < $now) {
                    $form->get('dateReservation')->addError(new FormError('Start time cannot be in the past'));
                }
                
                if ($end <= $start) {
                    $form->get('dateExpiration')->addError(new FormError('End time must be after start time'));
                }
    
                // If there are no errors, proceed with reservation
                if ($form->isValid()) {
                    // Calculate duration in hours (rounded up)
                    $interval = $start->diff($end);
                    $hours = $interval->h + ($interval->days * 24) + ($interval->i > 0 ? 1 : 0);
                    $hours = max(1, $hours); // Minimum 1 hour
    
                    // Pricing configuration
                    $hourlyRates = [
                        'Motorcycle' => 3.5,
                        'Compact' => 5.0,
                        'SUV' => 6.0,
                        'Van' => 7.5
                    ];
    
                    $carWashPrices = [
                        'Basic' => 10,
                        'Premium' => 20,
                        'Deluxe' => 30
                    ];
    
                    // Calculate base parking cost
                    $vehicleType = $reservation->getVehicleType();
                    if (!isset($hourlyRates[$vehicleType])) {
                        $this->addFlash('error', 'Invalid vehicle type selected');
                        return $this->redirectToRoute('app_parking');
                    }
    
                    $parkingCost = $hourlyRates[$vehicleType] * $hours;
    
                    // Add car wash if selected
                    $carWashType = $reservation->getCarWashType();
                    $carWashCost = 0;
                    
                    if ($carWashType && $carWashType !== 'None') {
                        if (!isset($carWashPrices[$carWashType])) {
                            $this->addFlash('error', 'Invalid car wash type selected');
                            return $this->redirectToRoute('app_parking');
                        }
                        $carWashCost = $carWashPrices[$carWashType];
                    }
    
                    $totalPrice = $parkingCost + $carWashCost;
    
                    // Set the final price
                    $reservation->setPrice($totalPrice);
                    $reservation->setStatut('active');
                    $spot->setStatut('reserved');
    
                    $this->entityManager->persist($reservation);
                    $this->entityManager->persist($spot);
                    $this->entityManager->flush();
    
                    // Send SMS confirmation
                    $user = $userRepository->find($currentUser);
                    if ($user && $user->getTelephone()) {
                        $message = sprintf(
                            "Welcome to InnoMall Smart Parking!\n\n".
                            "Thank you for choosing our parking services. Here are your reservation details:\n\n" .
                            "Spot: %s%s Floor %s\n".
                            "Time: %s - %s\n".
                            "Vehicle: %s\n".
                            "Parking: %.2f TND\n".
                            "Car Wash: %.2f TND\n".
                            "Total: %.2f TND\n".
                            "Happy Shopping!",
                            $spot->getZone(),
                            $spot->getId(),
                            str_replace('Level ', '', $spot->getFloor()),
                            $start->format('d/m H:i'),
                            $end->format('H:i'),
                            $vehicleType,
                            $parkingCost,
                            $carWashCost,
                            $totalPrice
                        );
                        
                        $smsService->sendSms($user->getTelephone(), $message);
                    }
    
                    // Handle AJAX response
                    if ($request->isXmlHttpRequest()) {
                        return $this->json([
                            'success' => true,
                            'message' => sprintf('Reservation created successfully! Total: %.2f TND', $totalPrice),
                            'redirect' => $this->generateUrl('app_parking')
                        ]);
                    }
    
                    $this->addFlash('success', sprintf(
                        'Reservation created successfully! Total: %.2f TND',
                        $totalPrice
                    ));
                    
                    return $this->redirectToRoute('app_parking');
                }
            }
    
            // Handle AJAX response for errors
            if ($request->isXmlHttpRequest()) {
                $errors = [];
                foreach ($form->getErrors(true) as $error) {
                    $errors[] = $error->getMessage();
                }
                
                return $this->json([
                    'success' => false,
                    'errors' => $errors
                ], 400);
            }
        }
    
        // Handle regular form rendering
        return $this->render('parking/reservation_form.html.twig', [
            'spot' => $spot,
            'form' => $form->createView(),
        ]);
    }


    #[Route('/parking/my-reservations', name: 'app_parking_my_reservations')]
    public function myReservations(
        Request $request,
        PaginatorInterface $paginator,
        ReservationRepository $reservationRepository
    ): Response {
        $user = $this->getUser();
        $userId = $user->getId();
    
        $queryBuilder = $reservationRepository->createQueryBuilder('r')
            ->leftJoin('r.placeParking', 'p')
            ->addSelect('p')
            ->where('r.idUtilisateur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateReservation', 'DESC');
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            10 // 9adeh mn element nhb nchouf
        );
    
        // Statistics calculations
        $allReservations = $reservationRepository->findAllReservationsForUser($userId);
        $activeCount = count(array_filter($allReservations, fn($r) => $r->getStatut() === 'active'));
        $thisMonthCount = count(array_filter($allReservations, fn($r) => 
            $r->getDateReservation()->format('Y-m') === (new \DateTime())->format('Y-m')
        ));
    
        return $this->render('parking/reservations.html.twig', [
            'pagination' => $pagination,
            'active_count' => $activeCount,
            'this_month_count' => $thisMonthCount,
            'total_spent' => array_reduce($allReservations, fn($total, $r) => $total + $r->getPrice(), 0)
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
public function adminStatistics(ReservationRepository $reservationRepository): Response
{
    // Get all reservations for revenue calculations
    $allReservations = $reservationRepository->findAll();
    
    // Calculate total statistics across all floors
    $totalSpots = $this->placeParkingRepository->count([]);
    $availableSpots = $this->placeParkingRepository->count(['statut' => 'free']);
    $occupiedSpots = $this->placeParkingRepository->count(['statut' => ['taken', 'reserved']]);
    $occupancyRate = $totalSpots > 0 ? round(($occupiedSpots / $totalSpots) * 100) : 0;
    $activeReservations = $reservationRepository->count(['statut' => 'active']);

    // Calculate revenue
    $dailyRevenue = 0;
    $monthlyRevenue = 0;
    $today = new \DateTime();
    $thisMonth = new \DateTime('first day of this month');
    
    foreach ($allReservations as $reservation) {
        if ($reservation->getDateReservation() > $today->modify('-1 day')) {
            $dailyRevenue += $reservation->getPrice();
        }
        if ($reservation->getDateReservation() > $thisMonth) {
            $monthlyRevenue += $reservation->getPrice();
        }
    }

    // Get statistics for each floor
    $floorStats = [];
    $floors = ['Level 1', 'Level 2', 'Level 3'];
    
    foreach ($floors as $floor) {
        $total = $this->placeParkingRepository->count(['floor' => $floor]);
        $available = $this->placeParkingRepository->count([
            'floor' => $floor, 
            'statut' => 'free'
        ]);
        $occupied = $this->placeParkingRepository->count([
            'floor' => $floor, 
            'statut' => ['taken', 'reserved']
        ]);
        
        $floorStats[] = [
            'floor' => $floor,
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0
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
#[Route('/admin/parking/statistics/export-pdf', name: 'app_parking_statistics_export_pdf')]
public function exportStatisticsToPdf(ReservationRepository $reservationRepository): Response
{
    // Get all the same data as the regular statistics page
    $allReservations = $reservationRepository->findAll();
    
    $totalSpots = $this->placeParkingRepository->count([]);
    $availableSpots = $this->placeParkingRepository->count(['statut' => 'free']);
    $occupiedSpots = $this->placeParkingRepository->count(['statut' => ['taken', 'reserved']]);
    $occupancyRate = $totalSpots > 0 ? round(($occupiedSpots / $totalSpots) * 100) : 0;
    $activeReservations = $reservationRepository->count(['statut' => 'active']);

    $dailyRevenue = 0;
    $monthlyRevenue = 0;
    $today = new \DateTime();
    $thisMonth = new \DateTime('first day of this month');
    
    foreach ($allReservations as $reservation) {
        if ($reservation->getDateReservation() > $today->modify('-1 day')) {
            $dailyRevenue += $reservation->getPrice();
        }
        if ($reservation->getDateReservation() > $thisMonth) {
            $monthlyRevenue += $reservation->getPrice();
        }
    }

    $floorStats = [];
    $floors = ['Level 1', 'Level 2', 'Level 3'];
    
    foreach ($floors as $floor) {
        $total = $this->placeParkingRepository->count(['floor' => $floor]);
        $available = $this->placeParkingRepository->count([
            'floor' => $floor, 
            'statut' => 'free'
        ]);
        $occupied = $this->placeParkingRepository->count([
            'floor' => $floor, 
            'statut' => ['taken', 'reserved']
        ]);
        
        $floorStats[] = [
            'floor' => $floor,
            'total' => $total,
            'available' => $available,
            'occupied' => $occupied,
            'occupancy_rate' => $total > 0 ? round(($occupied / $total) * 100) : 0
        ];
    }

    // Render the HTML template for PDF
    $html = $this->renderView('parking/pdf/statistics_pdf.html.twig', [
        'total_spots' => $totalSpots,
        'available_spots' => $availableSpots,
        'occupancy_rate' => $occupancyRate,
        'active_reservations' => $activeReservations,
        'daily_revenue' => $dailyRevenue,
        'monthly_revenue' => $monthlyRevenue,
        'floor_stats' => $floorStats,
        'generated_at' => new \DateTime(),
    ]);

    // Configure Dompdf
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    // Generate filename
    $filename = sprintf('parking-statistics-%s.pdf', date('Y-m-d-H-i-s'));

    // Return the PDF as response
    return new Response(
        $dompdf->output(),
        Response::HTTP_OK,
        [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]
    );
}
#[Route('/parking/mqtt-updates', name: 'app_parking_mqtt_updates')]
public function mqttUpdates(Request $request): JsonResponse
{
    // This endpoint will be called via AJAX to get updates
    $floor = $request->query->get('floor', 1);
    $floorValue = 'Level ' . $floor;
    
    $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
    
    $formattedSpots = [];
    foreach ($spots as $spot) {
        $formattedSpots[] = [
            'id' => $spot->getId(),
            'status' => $spot->getStatut(),
            'zone' => $spot->getZone()
        ];
    }
    
    return $this->json([
        'spots' => $formattedSpots,
        'timestamp' => time()
    ]);
}

private function getParkingSpotsData(int $floor): array
{
    $floorValue = 'Level ' . $floor;
    $spots = $this->placeParkingRepository->findBy(['floor' => $floorValue]);
    
    $formattedSpots = [];
    foreach ($spots as $spot) {
        $formattedSpots[$spot->getId()] = [
            'id' => $spot->getId(),
            'status' => $spot->getStatut(),
            'zone' => $spot->getZone()
        ];
    }
    
    return $formattedSpots;
}
}