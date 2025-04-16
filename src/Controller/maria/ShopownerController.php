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
use App\Repository\DiscountRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Produit;
use App\Entity\Discount;
use App\Entity\Event;
use App\Entity\Schedule;
use App\Entity\Utilisateur;
use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\LikedProduct;


use App\Form\EventType;
use App\Form\maria\ProductType;
use App\Form\maria\EditProductType;
use App\Form\maria\DiscountType;
use App\Form\maria\ScheduleType;
;


class ShopownerController extends AbstractController
{
    //Main

    #[Route('/admindashboard', name: 'dashboard')]
    public function dashboard(): Response
    {
        return $this->render('maria_templates/admindashboard.html.twig');
    }

    #[Route('/products', name: 'products')]
    public function products(
        EntityManagerInterface $entityManager,
        ProduitRepository $produitRepository,
        DiscountRepository $discountRepo
    ): Response {
        // Get the current shop owner (static ID 8)
        $user = $entityManager->getRepository(Utilisateur::class)->find(8);
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
    // src/Controller/ProductController.php

    #[Route('/product/new', name: 'product_new')]
    public function new(Request $request, EntityManagerInterface $em, UtilisateurRepository $utilisateurRepo): Response
    {
        $product = new Produit();
        $form = $this->createForm(ProductType::class, $product);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Get the Utilisateur with ID 8 as the shop owner
            $shop = $utilisateurRepo->find(8);
            if (!$shop) {
                throw $this->createNotFoundException('Shop owner not found');
            }
            $product->setShopId($shop);  // This will now work correctly
            // Handle file upload
            $imageFile = $form->get('image_url')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate(
                    'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                    $originalFilename
                );
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $imageFile->guessExtension();


                //default saving path 
                $targetDirectory = $this->getParameter('kernel.project_dir') . '/public/resources/assets/product_images/';

                // Create directory if it doesn't exist
                if (!file_exists($targetDirectory)) {
                    mkdir($targetDirectory, 0777, true);
                }

                try {
                    $imageFile->move(
                        $targetDirectory,
                        $newFilename
                    );
                    // Store relative path in database
                    $product->setImage_url('resources\assets\product_images\ ' . $newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'File upload failed: ' . $e->getMessage());
                    return $this->redirectToRoute('product_new');
                }
            }

            $em->persist($product);
            $em->flush();

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
        UtilisateurRepository $utilisateurRepo
    ): Response {
        $product = $produitRepo->find($id);
        if (!$product) {
            throw $this->createNotFoundException('Product not found');
        }

        $shop = $utilisateurRepo->find(8); // Static shop ID 8
        $discounts = $discountRepo->findBy(['shop' => $shop]);
        $count = $discountRepo->count(['shop' => $shop]);
        dump($count);

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
                $product->setImageUrl('resources/assets/product_images/' . $newFilename);
            }

            $em->flush();
            return $this->redirectToRoute('products');
        }

        // For AJAX requests to get the form
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
        // Get the current shop owner (static ID 8)
        $shop = $em->getRepository(Utilisateur::class)->find(8);

        // Create new discount form for the "Add" modal
        $discount = new Discount();
        $discount->setShop

        ($shop);
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
            'editForms' => $editForms,  // This will be an array of form views keyed by discount ID
        ]);
    }

    #[Route('/discount/new', name: 'discount_new', methods: ['POST'])]
    public function newDiscount(Request $request, EntityManagerInterface $em): Response
    {
        $shop = $em->getRepository(Utilisateur::class)->find(8);
        $discount = new Discount();
        $discount->setPromotionId($shop);

        $form = $this->createForm(DiscountType::class, $discount);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($discount);
            $em->flush();
            return $this->redirectToRoute('discounts');
        }

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
        $em->createQuery('UPDATE App\Entity\Produit p SET p.discount = NULL WHERE p.discount = :discount')
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
        $shop = $em->getRepository(Utilisateur::class)->find(8);
        if (!$shop) {
            throw $this->createNotFoundException('Shop with ID 8 not found');
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
        $current_user = 8;
        $shop = $utilisateurRepo->findutilisateurbyid($current_user);
        if (!$shop) {
            throw $this->createNotFoundException('Shop with ID 8 not found');
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

        // Verify schedule belongs to shop ID 8
        if ($schedule->getUtilisateur()->getId() !== 8) {
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
    public function commands(CommandeRepository $commandeRepository): Response
    {
        $shopOwnerId = 8; // Static ID (or fetch dynamically)
        $todayCommands = $commandeRepository->findTodayPaidOrdersByShop($shopOwnerId);

        $result = [];
        foreach ($todayCommands as $commande) {
            $result[] = [
                'numeroTicket' => $commande->getPaniers()->first()?->getNumeroTicket(),
                'commandeId' => $commande->getId(),
                'date' => $commande->getDateCommande()->format('d-m-y'),
                'total' => $commande->getTotal(),
                'client' => $commande->getIdClient()->getNom() . " " . $commande->getIdClient()->getPrenom(),
            ];
        }

        return $this->render('maria_templates/commands.html.twig', [
            'commands' => $result,
            'utilisateurId' => $shopOwnerId, // ✅ Now passed to Twig
        ]);
    }
    //Profile 

    #[Route('/profile', name: 'profile')]
    public function profile(): Response
    {
        return $this->render('maria_templates/profile.html.twig');
    }
}