<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\ProduitRepository;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

final class PointsController extends AbstractController
{
    #[Route('/points', name: 'app_points')]
    public function index(
        Request $request,
        ProduitRepository $produitRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $currentPoints = $user->getPoints();
        $maxPoints = 2000;
        $progressPercentage = min(100, ($currentPoints / $maxPoints) * 100);
        
        // Handle reward claim
        $rewardProduct = null;
        $showReward = false;
        if ($currentPoints >= $maxPoints) {
            if ($request->query->get('spin') === '1') {
                $rewardProduct = $this->handleRewardClaim($user, $produitRepository, $entityManager);
                $currentPoints = $user->getPoints(); // Refresh points
                $progressPercentage = 0;
                $showReward = true;
            }
        }

        return $this->render('home_page/points.html.twig', [
            'currentPoints' => $currentPoints,
            'maxPoints' => $maxPoints,
            'progressPercentage' => $progressPercentage,
            'nextReward' => $this->calculateNextReward($currentPoints),
            'canClaimReward' => $currentPoints >= $maxPoints && !$showReward,
            'rewardProduct' => $rewardProduct,
            'showReward' => $showReward,
        ]);
    }

    private function handleRewardClaim(
        Utilisateur $user,
        ProduitRepository $produitRepository,
        EntityManagerInterface $entityManager
    ) {
        // Get available products with stock > 0
        $products = $produitRepository->createQueryBuilder('p')
            ->where('p.stock > 0')
            ->getQuery()
            ->getResult();
        
            if (count($products) > 0) {
                $rewardProduct = $products[array_rand($products)];
                
                // Decrease stock by 1
                $rewardProduct->setStock($rewardProduct->getStock() - 1);
            
                // Reset user points
                $user->setPoints(0);
            
                $entityManager->flush();
                
                return $rewardProduct;
            }
            
        return null;
    }

    private function calculateNextReward(int $currentPoints): string
    {
        if ($currentPoints < 200) return "200 points - Small Gift";
        if ($currentPoints < 400) return "400 points - Medium Reward";
        if ($currentPoints < 2000) return "2000 points - Premium Reward";
        return "Max level reached!";
    }
}