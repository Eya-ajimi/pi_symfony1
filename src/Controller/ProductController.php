<?php

// src/Controller/ProductController.php
namespace App\Controller;

use App\Repository\ProduitRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/shop/{shopId}/products', name: 'shop_products')]
    public function showProducts(int $shopId, ProduitRepository $produitRepository): Response
    {
        // Fetch products for the specific shop owner (Utilisateur)
        $products = $produitRepository->findByShopId($shopId);

        return $this->render('product/shop_products.html.twig', [
            'products' => $products,
        ]);
    }
}
