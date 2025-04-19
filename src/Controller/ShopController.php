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
use App\Enums\Role;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;

class ShopController extends AbstractController
{
    #[Route('/shops', name: 'app_shops')]
    public function index(
        UtilisateurRepository $utilisateurRepository,
        FeedbackRepository $feedbackRepository,
        Request $request,
        FormFactoryInterface $formFactory
    ): Response {
        $shopOwners = $utilisateurRepository->findAllShopOwners();
        $fixedUser = $utilisateurRepository->find(7);

        $feedbackForms = [];
        $isRatedList = [];

        if ($fixedUser) {
            foreach ($shopOwners as $shop) {
                $existingFeedback = $feedbackRepository->findOneByUserAndShop($fixedUser, $shop);
                $feedback = $existingFeedback ?? new Feedback();

                $feedbackForms[$shop->getId()] = $formFactory->createNamed(
                    'feedback_' . $shop->getId(),
                    FeedbackType::class,
                    $feedback,
                    ['action' => $this->generateUrl('rate_shop', ['shopId' => $shop->getId()])]
                )->createView();

                $isRatedList[$shop->getId()] = $existingFeedback !== null;
            }
        }

        return $this->render('shops/shops.html.twig', [
            'shopOwners' => $shopOwners,
            'feedback_forms' => $feedbackForms,
            'feedback_repo' => $feedbackRepository,
            'isRatedList' => $isRatedList,
            'fixedUser' => $fixedUser,
        ]);
    }

    #[Route('/shop/rate/{shopId}', name: 'rate_shop', methods: ['POST'])]
    public function rateShop(
        Request $request,
        int $shopId,
        UtilisateurRepository $utilisateurRepository,
        FeedbackRepository $feedbackRepository,
        EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory
    ): Response {
        $fixedUser = $utilisateurRepository->find(7);
        $shop = $utilisateurRepository->find($shopId);
    
        

        if (!$fixedUser || !$shop || $shop->getRole() !== Role::SHOPOWNER) {

            $this->addFlash('error', 'Invalid user or shop.');
            return $this->redirectToRoute('app_shops');
        }

        $existingFeedback = $feedbackRepository->findOneByUserAndShop($fixedUser, $shop);
        $feedback = $existingFeedback ?? new Feedback();

        $form = $formFactory->createNamed(
            'feedback_' . $shopId,
            FeedbackType::class,
            $feedback
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $feedback->setUser($fixedUser);
            $feedback->setShop($shop);
            $feedback->setCreatedAt(new \DateTime());

            $entityManager->persist($feedback);
            $entityManager->flush();

            $this->addFlash('success', 'Rating submitted successfully!');
            return $this->redirectToRoute('app_shops');
        }

        // If form is invalid, re-render the page with errors for that specific form
        $shopOwners = $utilisateurRepository->findAllShopOwners();
        $feedbackForms = [];
        $isRatedList = [];

        foreach ($shopOwners as $s) {
            if ($s->getId() == $shopId) {
                // Use the invalid form (with errors)
                $feedbackForms[$s->getId()] = $form->createView();
            } else {
                // Create empty forms for other shops
                $existing = $feedbackRepository->findOneByUserAndShop($fixedUser, $s);
                $f = $existing ?? new Feedback();

                $feedbackForms[$s->getId()] = $formFactory->createNamed(
                    'feedback_' . $s->getId(),
                    FeedbackType::class,
                    $f,
                    ['action' => $this->generateUrl('rate_shop', ['shopId' => $s->getId()])]
                )->createView();

                $isRatedList[$s->getId()] = $existing !== null;
            }
        }

        return $this->render('shops/shops.html.twig', [
            'shopOwners' => $shopOwners,
            'feedback_forms' => $feedbackForms,
            'feedback_repo' => $feedbackRepository,
            'isRatedList' => $isRatedList,
            'fixedUser' => $fixedUser,
        ]);
    }

    #[Route('/shop/delete-rating/{shopId}', name: 'delete_rating', methods: ['POST'])]
    public function deleteRating(
        int $shopId,
        UtilisateurRepository $utilisateurRepository,
        FeedbackRepository $feedbackRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $utilisateurRepository->find(7);
        $shop = $utilisateurRepository->find($shopId);

        if ($user && $shop) {
            $rating = $feedbackRepository->findOneByUserAndShop($user, $shop);
            if ($rating) {
                $entityManager->remove($rating);
                $entityManager->flush();
                $this->addFlash('success', 'Rating deleted.');
            } else {
                $this->addFlash('error', 'No rating found to delete.');
            }
        } else {
            $this->addFlash('error', 'Invalid user or shop.');
        }

        return $this->redirectToRoute('app_shops');
    }
}