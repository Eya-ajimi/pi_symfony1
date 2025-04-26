<?php

// src/Controller/ProductController.php
namespace App\Controller;

use App\Entity\LikedProduct;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use App\Entity\Produit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\DiscountRepository;

class ProductController extends AbstractController
{
    #[Route('client/shop/{shopId}/products', name: 'shop_products')]
    public function showProducts(
        Request $request,
        int $shopId,
        ProduitRepository $produitRepository,
        DiscountRepository $discountRepository
    ): Response {
        $name = $request->query->get('name');
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
        $promotion = $request->query->get('promotion');

        $products = $produitRepository->findByFilters($shopId, $name, $minPrice, $maxPrice, $promotion);
        $activeDiscount = $discountRepository->findActiveDiscountForShop($shopId);

        return $this->render('product/shop_products.html.twig', [
            'products' => $products,
            'shopId' => $shopId,
            'activeDiscount' => $activeDiscount
        ]);
    }


  

#[Route('/product/{id}/like', name: 'product_like', methods: ['POST'])]
public function toggleLike(
    Produit $produit,
    EntityManagerInterface $em
): JsonResponse {
    $user = $this->getUser();

    if (!$user) {
        return new JsonResponse(['error' => 'Unauthorized'], 403);
    }

    $existingLike = $em->getRepository(LikedProduct::class)->findOneBy([
        'produit' => $produit,
        'utilisateur' => $user
    ]);

    if ($existingLike) {
        $em->remove($existingLike);
        $produit->setLikeCount($produit->getLikeCount() - 1);  // Decrement like count
        $em->flush();
        return new JsonResponse(['liked' => false]);
    } else {
        $like = new LikedProduct();
        $like->setProduit($produit);
        $like->setUtilisateur($user);
        $like->setDateLike(new \DateTime());

        $em->persist($like);
        $produit->setLikeCount($produit->getLikeCount() + 1);  // Increment like count
        $em->flush();
        return new JsonResponse(['liked' => true]);
    }
}



}
