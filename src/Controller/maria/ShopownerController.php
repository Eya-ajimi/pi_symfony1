<?php
// src/Controller/maria/ShopownerController.php
namespace App\Controller\maria;


use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EventRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\ProduitRepository;
use App\Repository\LikedProductRepository;
use App\Repository\ScheduledEventRepository;
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
use App\Entity\LikedProduct;


use App\Form\EventType;
use App\Form\maria\ProductType;
use App\Form\maria\EditProductType;
use App\Form\maria\DiscountType;


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
            'shop' => $user,
            'discounts' => $discounts
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
            $product->setShop($shop);

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

        $form = $this->createForm(EditProductType::class, $product, [
            'shop' => $shop,
            'discounts' => $discounts,
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

        // Create new discount form
        $discount = new Discount();
        $discount->setShop($shop);
        $form = $this->createForm(DiscountType::class, $discount);

        // Create dummy edit form
        $editDiscount = new Discount();
        $editForm = $this->createForm(DiscountType::class, $editDiscount);

        // Get existing discounts
        $discounts = $em->getRepository(Discount::class)->findBy(['shop' => $shop]);

        return $this->render('maria_templates/discounts.html.twig', [
            'discounts' => $discounts,
            'form' => $form->createView(),
            'editForm' => $editForm->createView()
        ]);
    }

    #[Route('/discount/new', name: 'discount_new', methods: ['POST'])]
    public function newDiscount(Request $request, EntityManagerInterface $em): Response
    {
        $shop = $em->getRepository(Utilisateur::class)->find(8);
        $discount = new Discount();
        $discount->setShop($shop);

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
            return $this->redirectToRoute('discounts');
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

    // src/Controller/ShopController.php
    #[Route('/schedule', name: 'schedule')]
    public function schedule(ScheduleRepository $scheduleRepo): Response
    {
        $schedules = $scheduleRepo->findBy(['shopId' => 8], ['dayOfWeek' => 'ASC']);

        return $this->render('maria_templates/schedule.html.twig', [
            'schedules' => $schedules
        ]);
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