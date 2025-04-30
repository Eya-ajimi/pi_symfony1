<?php
// src/Controller/maria/ShopownerController.php
namespace App\Controller\maria;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EventRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ProduitRepository;
use App\Repository\LikedProductRepository;
use App\Repository\ScheduledEventRepository;
use App\Repository\ScheduleRepository;
use App\Repository\CommandeRepository;
use App\Repository\PanierRepository;
use App\Repository\CategorieRepository;
use App\Repository\FeedbackRepository;
use App\Repository\DiscountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Produit;
use App\Entity\Notification;
use App\Entity\Discount;
use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\Utilisateur;
use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\LikedProduct;
use App\Entity\Categorie;
use App\Form\EventType;
use App\Form\maria\ProductType;
use App\Form\maria\EditProductType;
use App\Form\maria\DiscountType;
use App\Form\maria\ScheduleType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Event\LowStockEvent;
use App\Controller\maria\NotificationController;
use App\Service\DiscountCalendarSubscriber;
use Spatie\CalendarLinks\Link;  // ← This is the critical line
use Spatie\IcalendarGenerator\Components\Calendar;
use Spatie\IcalendarGenerator\Components\Event as IcalendarEvent;  // ← Alias to avoid conflict
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use App\Entity\Message;
use App\Repository\MessageRepository;
use App\Form\MessageType;
use App\Service\DiscountNotificationService;



#[Route('/shopOwner')]
class ShopOwnerController extends AbstractController
{

    #[Route('/admindashboard', name: 'dashboard')]
    public function dashboard(
        EntityManagerInterface $entityManager,
        FeedbackRepository $feedbackRepository,
        ProduitRepository $produitRepository,
        CommandeRepository $commandeRepository,
        DiscountRepository $discountRepository,
        EventRepository $eventRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $currentId = $this->getUser()->getId();
        $shop = $entityManager->getRepository(Utilisateur::class)->find($currentId);

        if (!$shop) {
            throw $this->createNotFoundException('Shop not found');
        }

        // Get active discount
        $activeDiscount = $discountRepository->findActiveDiscountForShop($shop->getId());

        // Get feedback statistics
        $averageRating = $feedbackRepository->getAverageRatingValue($shop);
        $ratingCount = $feedbackRepository->countByShop($shop);
        $ratingDistribution = $feedbackRepository->getRatingDistribution($shop);

        // Get product data
        $topLikedProducts = $produitRepository->findTopLikedProducts($shop->getId(), 4);
        $productCount = $produitRepository->count(['shopId' => $shop]);

        // Get sales data
        $topSoldProducts = $produitRepository->findTop10SoldProductsByShop($shop->getId());
        $salesData = [
            'labels' => [],
            'quantities' => []
        ];

        foreach ($topSoldProducts as $item) {
            $salesData['labels'][] = $item['product']->getNom();
            $salesData['quantities'][] = $item['totalQuantity'];
        }

        // Get stock status
        $lowStockProducts = $produitRepository->findByLowStockAndShop($shop->getId());
        $outOfStockProducts = $produitRepository->findByOutOfStockAndShop($shop->getId());

        // Get events
        $relevantEvent = $eventRepository->findRelevantEvents($shop->getId());
        $eventData = null;

        if (!empty($relevantEvent)) {
            $firstEvent = $relevantEvent[0];
            $today = new \DateTime();
            $startDate = $firstEvent->getDateDebut();
            $endDate = $firstEvent->getDateFin();

            $eventData = [
                'name' => $firstEvent->getNomOrganisateur(),
                'desc' => $firstEvent->getDescription(),
                'startDate' => $startDate,
                'endDate' => $endDate,
                'location' => $firstEvent->getEmplacement(),
                'daysUntil' => $today->diff($startDate)->days,
                'isCurrent' => ($today >= $startDate && $today <= $endDate)
            ];
        }

        // Get sales statistics - UPDATED SECTION
        $totalSalesLast7Days = $commandeRepository->findTotalSalesLast7Days($shop->getId());
        $dailySalesLast8Days = $commandeRepository->findDailyTotalSalesLast8Days($shop->getId());

        // Prepare chart data
        $weeklySalesChartData = [
            'labels' => array_keys($dailySalesLast8Days),
            'data' => array_values($dailySalesLast8Days)
        ];
        $discounts = $discountRepository->findBy(['shop' => $shop->getId()]);

        $seasonalAverages = [
            'Winter' => 0,
            'Spring' => 0,
            'Summer' => 0,
            'Fall' => 0
        ];
        $seasonCounts = array_fill_keys(array_keys($seasonalAverages), 0);

        foreach ($discounts as $discount) {
            $startDate = $discount->getStartDate();
            $month = (int) $startDate->format('n');
            $day = (int) $startDate->format('j');

            $season = match (true) {
                ($month == 12 && $day >= 21) || ($month <= 3 && $day <= 20) => 'Winter',
                ($month == 3 && $day >= 21) || ($month <= 6 && $day <= 20) => 'Spring',
                ($month == 6 && $day >= 21) || ($month <= 9 && $day <= 22) => 'Summer',
                default => 'Fall'
            };

            $seasonalAverages[$season] += $discount->getDiscountPercentage();
            $seasonCounts[$season]++;
        }

        // Calculate final averages
        foreach ($seasonalAverages as $season => $total) {
            $seasonalAverages[$season] = $seasonCounts[$season] > 0
                ? round($total / $seasonCounts[$season], 2)
                : 0;
        }

        return $this->render('maria_templates/admindashboard.html.twig', [
            'averageRating' => $averageRating,
            'ratingCount' => $ratingCount,
            'productCount' => $productCount,
            'topLikedProducts' => $topLikedProducts,
            'ratingDistribution' => $ratingDistribution,
            'salesData' => $salesData,
            'lowStockProducts' => $lowStockProducts,
            'outOfStockProducts' => $outOfStockProducts,
            'shop' => $shop,
            'activeDiscount' => $activeDiscount,
            'eventData' => $eventData,
            'totalSalesLast7Days' => $totalSalesLast7Days,
            'weeklySalesChartData' => $weeklySalesChartData,
            'seasonalAverages' => $seasonalAverages, // Pass the raw data separately

        ]);
    }
    // // ===== Helper Methods =====
    // private function prepareSeasonalDiscountData(array $discounts): array
    // {
    //     $seasons = [
    //         'Winter' => ['total' => 0, 'count' => 0],
    //         'Spring' => ['total' => 0, 'count' => 0],
    //         'Summer' => ['total' => 0, 'count' => 0],
    //         'Fall' => ['total' => 0, 'count' => 0]
    //     ];

    //     foreach ($discounts as $discount) {
    //         $startDate = $discount->getStartDate();
    //         $month = (int) $startDate->format('n');
    //         $day = (int) $startDate->format('j');

    //         $season = match (true) {
    //             ($month == 12 && $day >= 21) || ($month <= 3 && $day <= 20) => 'Winter',
    //             ($month == 3 && $day >= 21) || ($month <= 6 && $day <= 20) => 'Spring',
    //             ($month == 6 && $day >= 21) || ($month <= 9 && $day <= 22) => 'Summer',
    //             default => 'Fall'
    //         };

    //         $seasons[$season]['total'] += $discount->getDiscountPercentage();
    //         $seasons[$season]['count']++;
    //     }

    //     return array_map(
    //         fn($season) => $season['count'] > 0 ? $season['total'] / $season['count'] : 0,
    //         $seasons
    //     );
    // }

    private function getSeasonalChartData(array $discounts): array
    {
        $seasons = [
            'Winter' => ['total' => 0, 'count' => 0],
            'Spring' => ['total' => 0, 'count' => 0],
            'Summer' => ['total' => 0, 'count' => 0],
            'Fall' => ['total' => 0, 'count' => 0]
        ];

        foreach ($discounts as $discount) {
            $startDate = $discount->getStartDate();
            $month = (int) $startDate->format('n');
            $day = (int) $startDate->format('j');

            $season = match (true) {
                ($month == 12 && $day >= 21) || ($month <= 3 && $day <= 20) => 'Winter',
                ($month == 3 && $day >= 21) || ($month <= 6 && $day <= 20) => 'Spring',
                ($month == 6 && $day >= 21) || ($month <= 9 && $day <= 22) => 'Summer',
                default => 'Fall'
            };

            $seasons[$season]['total'] += $discount->getDiscountPercentage();
            $seasons[$season]['count']++;
        }
        $averages = array_map(
            fn($season) => $season['count'] > 0 ? $season['total'] / $season['count'] : 0,
            $seasons
        );

        return [
            'labels' => array_keys($seasons),
            'datasets' => [
                [
                    'label' => 'Average Seasonal Discount (%)',
                    'data' => array_values($averages),
                    'backgroundColor' => [
                        '#36A2EB',
                        '#4BC0C0',
                        '#FFCE56',
                        '#FF6384'
                    ]
                ]
            ]
        ];
    }
    private function createSeasonalChart(ChartBuilderInterface $chartBuilder, array $data): Chart
    {
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_keys($data),
            'datasets' => [
                [
                    'label' => 'Average Seasonal Discount (%)',
                    'data' => array_values($data),
                    'backgroundColor' => [
                        '#36A2EB', // Winter
                        '#4BC0C0', // Spring
                        '#FFCE56', // Summer
                        '#FF6384'  // Fall
                    ],
                ]
            ]
        ]);
        $chart->setOptions([
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.raw + "%"; }'
                    ]
                ]
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['callback' => 'function(value) { return value + "%"; }']
                ]
            ]
        ]);

        return $chart;
    }

    //todaysummary
    #[Route('/todaysummary', name: 'todaysummary')]
    public function todaysummary(): Response
    {
        $currentId = $this->getUser()->getId();

        return $this->render('maria_templates/todaysummary.html.twig');
    }
    //**************************************************************************************************************************** */

    #[Route('/products', name: 'products')]
    public function products(
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        DiscountRepository $discountRepo
    ): Response {
        $currentId = $this->getUser()->getId();
        // Get the current shop owner
        $user = $entityManager->getRepository(Utilisateur::class)->find($currentId);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        // Get products and discounts
        $products = $produitRepository->findByShopIdWithLikeCounts($user->getId());
        $discounts = $discountRepo->findBy(['shop' => $user]);

        // Create new product form
        $newProduct = new Produit();
        $addForm = $this->createForm(ProductType::class, $newProduct);

        // Create dummy edit form (will be populated via JavaScript)
        $editProduct = new Produit();
        $editForm = $this->createForm(EditProductType::class, $editProduct, [
            'shopId' => $user,
            'promotionId' => $discounts
        ]);

        return $this->render('maria_templates/products.html.twig', [
            'products' => $products,
            'form' => $addForm->createView(),
            'editForm' => $editForm->createView(),
            'discounts' => $discounts
        ]);
    }

    #[Route('/product/new', name: 'product_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $utilisateurRepo,
        EventDispatcherInterface $dispatcher
    ): Response {
        $currentId = $this->getUser()->getId();
        $product = new Produit();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $shop = $utilisateurRepo->find($currentId);
            if (!$shop) {
                throw $this->createNotFoundException('Shop owner not found');
            }
            $product->setShopId($shop);

            // Handle file upload
            $imageFile = $form->get('image_url')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate(
                    'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                    $originalFilename
                );
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();

                $targetDirectory = $this->getParameter('kernel.project_dir') . '/public/resources/assets/product_images/';

                if (!file_exists($targetDirectory)) {
                    mkdir($targetDirectory, 0777, true);
                }

                try {
                    $imageFile->move($targetDirectory, $newFilename);
                    $product->setImage_url('resources\assets\product_images\ ' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'File upload failed: ' . $e->getMessage());
                    return $this->redirectToRoute('product_new');
                }
            }

            $em->persist($product);
            $em->flush();

            // Check for low stock
            if ($product->getStock() <= 10) {
                $notification = new Notification();
                $notification->setMessage(sprintf(
                    'Low stock: %s (%d left)',
                    $product->getNom(),
                    $product->getStock()
                ));
                $notification = new Notification();
                $notification->setUser($this->getUser());

                $em->persist($notification);
                $em->flush();
            }

            $this->addFlash('success', 'Product created successfully!');
            return $this->redirectToRoute('products');
        }

        return $this->render('maria_templates/products.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/product/edit/{id}', name: 'product_edit', methods: ['GET', 'POST'])]
    public function editProduct(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ProduitRepository $produitRepo,
        DiscountRepository $discountRepo,
        UtilisateurRepository $utilisateurRepo,
        EventDispatcherInterface $dispatcher
    ): Response {
        $currentId = $this->getUser()->getId();
        $product = $produitRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $shop = $utilisateurRepo->find($currentId);
        $discounts = $discountRepo->findBy(['shop' => $shop]);

        $form = $this->createForm(EditProductType::class, $product, [
            'shopId' => $shop,
            'promotionId' => $discounts,
            'action' => $this->generateUrl('product_edit', ['id' => $id])
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image_url')->getData();
            if ($imageFile) {
                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $targetDirectory = $this->getParameter('kernel.project_dir') . '/public/resources/assets/product_images/';
                $imageFile->move($targetDirectory, $newFilename);
                $product->setImage_url('resources/assets/product_images/' . $newFilename);
            }

            $em->flush();

            // Check for low stock
            if ($product->getStock() <= 10) {
                $notification = new Notification();
                $notification->setMessage(sprintf(
                    'Low stock: %s (%d left)',
                    $product->getNom(),
                    $product->getStock()
                ));
                $notification->setCreatedAt(new \DateTime());
                $notification->setUser($this->getUser());

                $em->persist($notification);
                $em->flush();
            }

            return $this->redirectToRoute('products');
        }

        if ($request->isXmlHttpRequest()) {
            return $this->render('maria_templates/forms/edit_product_form.html.twig', [
                'form' => $form->createView()
            ]);
        }

        return $this->redirectToRoute('products');
    }

    #[Route('/product/delete/{id}', name: 'product_delete', methods: ['POST', 'DELETE'])]
    public function deleteProduct(
        int $id,
        ProduitRepository $produitRepo
    ): Response {
        $product = $produitRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $produitRepo->remove($product, true); // The flush parameter

        return $this->redirectToRoute('products');
    }

    #[Route('/discounts', name: 'discounts')]
    public function discounts(
        EntityManagerInterface $em,
        Request $request
    ): Response {

        // Get the current shop owner
        $shop = $this->getUser();

        // Create new discount form for the "Add" modal
        $discount = new Discount();
        $discount->setShop($shop);
        $addForm = $this->createForm(DiscountType::class, $discount);

        // Get existing discounts
        $discounts = $em->getRepository(Discount::class)->findBy(['shop' => $shop]);

        // Create edit forms for each existing discount
        $editForms = [];
        foreach ($discounts as $discount) {
            $editForms[$discount->getId()] = $this->createForm(DiscountType::class, $discount)->createView();
        }

        return $this->render('maria_templates/discounts.html.twig', [
            'discounts' => $discounts,
            'addForm' => $addForm->createView(),
            'editForms' => $editForms,
        ]);
    }

    #[Route('/discount/new', name: 'discount_new', methods: ['POST'])] public function newDiscount(
        Request $request,
        EntityManagerInterface $em,
        DiscountNotificationService $discountNotificationService
    ): Response {
        $shop = $this->getUser();
        $discount = new Discount();
        $discount->setShop($shop);

        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discount);
            $em->flush();

            // Send email notification after successful discount creation
            try {
                $discountNotificationService->sendDiscountNotification(
                    $discount->getDiscountPercentage(), // Assuming your Discount entity has getProduct()
                    $discount->getDiscountPercentage(),  // Assuming your Discount entity has getDiscountPercentage()
                    'ammarim073@gmail.com'               // Test email - replace with actual client emails in production
                );

                $this->addFlash('success', 'Discount added and notification sent!');
            } catch (\Exception $e) {
                $this->addFlash('warning', 'Discount was added but email notification failed: ' . $e->getMessage());
            }

            return $this->redirectToRoute('discounts');
        }

        // If form is not valid, you might want to render the form again with errors
        // Currently your original code redirects even on invalid form submission
        // Consider changing to render the form template if invalid
        return $this->redirectToRoute('discounts');
    }

    #[Route('/discount/edit/{id}', name: 'discount_edit', methods: ['POST'])]
    public function editDiscount(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $discount = $em->getRepository(Discount::class)->find($id);
        if (!$discount) {
            throw $this->createNotFoundException('Discount not found');
        }

        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Discount updated successfully');
        } else {
            $this->addFlash('error', 'There was an error updating the discount');
        }

        return $this->redirectToRoute('discounts');
    }

    #[Route('/discount/delete/{id}', name: 'discount_delete', methods: ['POST'])]
    public function deleteDiscount(int $id, EntityManagerInterface $em): Response
    {
        $discount = $em->getRepository(Discount::class)->find($id);
        if (!$discount) {
            throw $this->createNotFoundException('Discount not found');
        }

        // First, remove discount reference from all products
        $em->createQuery('UPDATE App\Entity\Produit p SET p.promotionId = NULL WHERE p.promotionId = :discount')
            ->setParameter('discount', $discount)
            ->execute();

        // Then delete the discount
        $em->remove($discount);
        $em->flush();

        return $this->redirectToRoute('discounts');
    }

    //Schedule
    #[Route('/schedule', name: 'schedule')]
    public function schedule(
        ScheduleRepository $scheduleRepo,
        EntityManagerInterface $em
    ): Response {
        $shop = $this->getUser();
        if (!$shop) {
            throw $this->createNotFoundException('Shop not found');
        }

        $schedules = $scheduleRepo->findBy(['shop' => $shop], ['dayOfWeek' => 'ASC']);

        // Create edit forms for each schedule
        $editForms = [];
        foreach ($schedules as $schedule) {
            $editForms[$schedule->getId()] = $this->createForm(ScheduleType::class, $schedule, [
                'is_closed' => $schedule->getOpeningTime() == $schedule->getClosingTime(),
                'action' => $this->generateUrl('schedule_edit', ['id' => $schedule->getId()])
            ])->createView();
        }

        // Create add form
        $newSchedule = new Schedule();
        $newSchedule->setUtilisateur($shop);
        $addForm = $this->createForm(ScheduleType::class, $newSchedule);

        return $this->render('maria_templates/schedule.html.twig', [
            'schedules' => $schedules,
            'addForm' => $addForm->createView(),
            'editForms' => $editForms
        ]);
    }

    #[Route('/schedule/new', name: 'schedule_new', methods: ['POST'])]
    public function newSchedule(
        Request $request,
        EntityManagerInterface $em,
        UtilisateurRepository $utilisateurRepo
    ): Response {
        $shop = $this->getUser();
        if (!$shop) {
            throw $this->createNotFoundException('Shop not found');
        }

        $schedule = new Schedule();
        $schedule->setUtilisateur($shop);

        $form = $this->createForm(ScheduleType::class, $schedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle closed days
            if ($form->get('isClosed')->getData()) {
                $schedule->setOpeningTime(new \DateTime('00:00:00'));
                $schedule->setClosingTime(new \DateTime('00:00:00'));
            }

            $em->persist($schedule);
            $em->flush();

            $this->addFlash('success', 'Schedule added successfully!');
            return $this->redirectToRoute('schedule');
        }

        // Handle form errors
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
        return $this->redirectToRoute('schedule');
    }

    #[Route('/schedule/edit/{id}', name: 'schedule_edit', methods: ['POST'])]
    public function editSchedule(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        ScheduleRepository $scheduleRepo
    ): Response {
        $schedule = $scheduleRepo->find($id);
        if (!$schedule) {
            throw $this->createNotFoundException('Schedule not found');
        }

        $form = $this->createForm(ScheduleType::class, $schedule, [
            'is_closed' => $schedule->getOpeningTime()->format('H:i') === '00:00' &&
                $schedule->getClosingTime()->format('H:i') === '00:00'
        ]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $isClosed = $form->get('isClosed')->getData();
            if (!$form->isValid()) {
                // Return form with errors
                return $this->render('your_template.html.twig', [
                    'form' => $form->createView()
                ]);
            }
            if ($isClosed) {
                $schedule->setOpeningTime(new \DateTime('00:00:00'));
                $schedule->setClosingTime(new \DateTime('00:00:00'));
            }

            $em->flush();
            return $this->redirectToRoute('schedule');
        }

        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
        return $this->redirectToRoute('schedule');
    }

    #[Route('/schedule/delete/{id}', name: 'schedule_delete', methods: ['POST'])]
    public function deleteSchedule(
        int $id,
        EntityManagerInterface $em,
        ScheduleRepository $scheduleRepo
    ): Response {
        $schedule = $scheduleRepo->find($id);
        if (!$schedule) {
            throw $this->createNotFoundException('Schedule not found');
        }

        // Verify schedule belongs to current shop
        if ($schedule->getUtilisateur()->getId() !== $this->currentId) {
            throw $this->createAccessDeniedException('You can only delete schedules for your shop');
        }

        $em->remove($schedule);
        $em->flush();

        $this->addFlash('success', 'Schedule deleted successfully!');
        return $this->redirectToRoute('schedule');
    }

    // Partie Oussema
    // Events - Keep only this one version
    #[Route('/events', name: 'events')]
    public function events(EntityManagerInterface $entityManager, Request $request): Response
    {
        $user = $this->getUser();

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



    //Profile
    #[Route('/profile', name: 'profile')]
    public function profile(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        CategorieRepository $categorieRepo
    ): Response {
        $user = $this->getUser();
        $categories = $categorieRepo->findAll();

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $categorie = $request->request->get('categorie');
            $description = $request->request->get('description');
            $password = $request->request->get('password');

            $categorieObj = null;
            if ($nom !== null) {
                $user->setNom($nom);
            }
            if ($email !== null) {
                $user->setEmail($email);
            }
            if ($telephone !== null) {
                $user->setTelephone($telephone);
            }
            if ($categorie !== null) {
                $categorieObj = $categorieRepo->findOneBy(['nom' => $categorie]);
                $user->setCategorie($categorieObj);
            }
            if ($description !== null) {
                $user->setDescription($description);
            }

            if ($password && $password !== '******') {
                $encodedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($encodedPassword);
            }

            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Le profil commerçant a été mis à jour.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('maria_templates/profile.html.twig', [
            'user' => $user,
            'categories' => $categories,
        ]);
    }

    //aziz
    #[Route('/profile/shopowner_edit', name: 'shopowner_edit')]
    public function shopowner_edit(
        Request $request,
        ManagerRegistry $doctrine,
        UserPasswordHasherInterface $passwordHasher,
        CategorieRepository $categorieRepo
    ): Response {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('Shop owner not found.');
        }

        $categories = $categorieRepo->findAll();

        if ($request->isMethod('POST')) {
            $nom = $request->request->get('nom');
            $email = $request->request->get('email');
            $telephone = $request->request->get('telephone');
            $categorieId = $request->request->get('categorie');
            $description = $request->request->get('description');
            $password = $request->request->get('password');

            // Update user properties
            if ($nom !== null) {
                $user->setNom($nom);
            }
            if ($email !== null) {
                $user->setEmail($email);
            }
            if ($telephone !== null) {
                $user->setTelephone($telephone);
            }
            if ($categorieId !== null) {
                $category = $categorieRepo->find($categorieId);
                if ($category) {
                    $user->setCategorie($category);
                } else {
                    $this->addFlash('error', 'Invalid category selected');
                }
            }
            if ($description !== null) {
                $user->setDescription($description);
            }

            // Handle password change
            if ($password && $password !== '******') {
                $encodedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($encodedPassword);
            }

            // Save changes
            $entityManager = $doctrine->getManager();
            $entityManager->persist($user);
            $entityManager->flush();


            $this->addFlash('success', 'Le profil commerçant a été mis à jour.');
            return $this->redirectToRoute('profile');
        }

        return $this->render('maria_templates/profile.html.twig', [
            'user' => $user,
            'categories' => $categories,
        ]);
    }
    //ContactAdmin
    // src/Controller/maria/ShopownerController.php

    // src/Controller/maria/ShopownerController.php

    #[Route('/contact-admin', name: 'contact_admin')]
    #[IsGranted('ROLE_SHOP_OWNER')]
    public function contactAdmin(
        Request $request,
        MessageRepository $messageRepository,
        EntityManagerInterface $entityManager
    ): Response {
        /** @var Utilisateur $shopOwner */
        $shopOwner = $this->getUser();

        // Verify we have a valid user
        if (!$shopOwner instanceof Utilisateur) {
            throw $this->createAccessDeniedException('Invalid user type');
        }

        // Get messages - now handles both object and ID
        $messages = $messageRepository->findShopOwnerConversations($shopOwner);
        // Create new message
        $message = new Message();
        $message->setSender($shopOwner);
        $message->setIsToAllAdmins(true);

        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($message);
            $entityManager->flush();

            return $this->redirectToRoute('contact_admin');
        }

        return $this->render('maria_templates/ContactAdmin.html.twig', [
            'messages' => $messages,
            'form' => $form->createView(),
            'shopOwner' => $shopOwner
        ]);
    }
}