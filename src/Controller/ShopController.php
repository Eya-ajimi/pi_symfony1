<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Form\FeedbackType;
use App\Repository\FeedbackRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ShopController extends AbstractController
{
    #[Route('/shops', name: 'app_shops')]
    public function index(
        UtilisateurRepository $utilisateurRepository,
        FeedbackRepository $feedbackRepository,
        Request $request
    ): Response {
        // Get all shop owners
        $shopOwners = $utilisateurRepository->findAllShopOwners();
        
        // Get fixed user (ID = 9)
        $fixedUser = $utilisateurRepository->find(9);
        
        // Prepare feedback forms for each shop
        $feedbackForms = [];
        if ($fixedUser) {
            foreach ($shopOwners as $shop) {
                $existingFeedback = $feedbackRepository->findOneByUserAndShop($fixedUser, $shop);
                $feedback = $existingFeedback ?? new Feedback();
                
                $feedbackForms[$shop->getId()] = $this->createForm(FeedbackType::class, $feedback, [
                    'action' => $this->generateUrl('rate_shop', ['shopId' => $shop->getId()])
                ])->createView();
            }
        }

        return $this->render('shops/shops.html.twig', [
            'shopOwners' => $shopOwners,
            'feedback_forms' => $feedbackForms,
            'feedback_repo' => $feedbackRepository,
        ]);
    }

    #[Route('/shop/rate/{shopId}', name: 'rate_shop', methods: ['POST'])]
    public function rateShop(
        Request $request,
        int $shopId,
        UtilisateurRepository $utilisateurRepository,
        FeedbackRepository $feedbackRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Get fixed user (ID = 9)
        $fixedUser = $utilisateurRepository->find(9);
        if (!$fixedUser) {
            $this->addFlash('error', 'System user not configured!');
            return $this->redirectToRoute('app_shops');
        }
    
        // Validate shop owner
        $shop = $utilisateurRepository->find($shopId);
        if (!$shop || $shop->getRole() !== 'SHOPOWNER') {
            $this->addFlash('error', 'Invalid shop selected!');
            return $this->redirectToRoute('app_shops');
        }
    
        // Handle feedback submission
        $existingFeedback = $feedbackRepository->findOneByUserAndShop($fixedUser, $shop);
        $feedback = $existingFeedback ?? new Feedback();
        
        $form = $this->createForm(FeedbackType::class, $feedback);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Set fixed user and shop relationship
                $feedback->setUser($fixedUser);
                $feedback->setShop($shop);
                $feedback->setCreatedAt(new \DateTime());
    
                // Persist to database
                $entityManager->persist($feedback);
                $entityManager->flush();
    
                $this->addFlash('success', 'Rating submitted successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error saving rating: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid rating submission!');
        }
    
        return $this->redirectToRoute('app_shops');
    }
    
}