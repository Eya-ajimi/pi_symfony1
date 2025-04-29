<?php

namespace App\Controller;

use App\Repository\LikedProductRepository;
use App\Repository\ProduitRepository;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;

class LikedProductAndRecommendationController extends AbstractController
{
    private LikedProductRepository $likedProductRepository;
    private ProduitRepository $produitRepository;
    private PaginatorInterface $paginator;

    public function __construct(
        LikedProductRepository $likedProductRepository,
        ProduitRepository $produitRepository,
        PaginatorInterface $paginator
    ) {
        $this->likedProductRepository = $likedProductRepository;
        $this->produitRepository = $produitRepository;
        $this->paginator = $paginator;
    }

    #[Route('client/liked/productUser', name: 'app_liked_product_and_recommendation')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer une QueryBuilder au lieu d'un tableau pour les liked products
        $likedProductsQuery = $this->likedProductRepository->createQueryBuilderWithProducts($user);

        // Paginer avec la requête
        $paginatedLikedProducts = $this->paginator->paginate(
            $likedProductsQuery,
            $request->query->getInt('liked_page', 1),
            6,
            ['pageParameterName' => 'liked_page']
        );

        // Extraire les produits des LikedProduct pour l'analyse
        $likedProducts = [];
        $likedCategories = [];
        $likedDescriptions = [];

        foreach ($paginatedLikedProducts as $likedProductEntity) {
            $produit = $likedProductEntity->getProduit();
            if ($produit) {
                $likedProducts[] = $produit;

                if ($produit->getDescription()) {
                    $likedDescriptions[] = $produit->getDescription();
                }

                if ($produit->getShopId() && $produit->getShopId()->getCategorie()) {
                    $categorieId = $produit->getShopId()->getCategorie()->getIdCategorie();
                    if (!in_array($categorieId, $likedCategories)) {
                        $likedCategories[] = $categorieId;
                    }
                }
            }
        }

        // Obtenir tous les produits disponibles
        $allProducts = $this->produitRepository->findAll();

        // Obtenir les recommandations
        $recommendedProducts = $this->getRecommendations(
            $likedProducts,
            $allProducts,
            $likedDescriptions,
            $likedCategories
        );

        // Créer une requête à partir du tableau pour permettre la pagination
        $recommendedProductsQuery = $this->produitRepository->createQueryBuilder('p')
            ->where('p.id IN (:ids)')
            ->setParameter('ids', array_map(function($product) {
                return $product->getId();
            }, $recommendedProducts))
            ->getQuery();

        // Paginer les recommandations
        $paginatedRecommendedProducts = $this->paginator->paginate(
            $recommendedProductsQuery,
            $request->query->getInt('recommended_page', 1),
            6,
            ['pageParameterName' => 'recommended_page']
        );

        return $this->render('liked_product_and_recommendation/index.html.twig', [
            'controller_name' => 'LikedProductAndRecommendationController',
            'likedProducts' => $paginatedLikedProducts,
            'recommendedProducts' => $paginatedRecommendedProducts
        ]);
    }

    private function getRecommendations(array $likedProducts, array $allProducts, array $likedDescriptions, array $likedCategories): array
    {
        if (empty($likedProducts)) {
            shuffle($allProducts);
            return array_slice($allProducts, 0, 5);
        }

        $combinedDescription = implode(' ', $likedDescriptions);
        $keywords = $this->extractKeywords($combinedDescription);

        $scoredProducts = [];
        $likedProductIds = array_map(function($product) {
            return $product->getId();
        }, $likedProducts);

        foreach ($allProducts as $product) {
            if (in_array($product->getId(), $likedProductIds)) {
                continue;
            }

            $description = $product->getDescription() ?? '';
            $name = $product->getNom() ?? '';

            $textScore = $this->calculateSimilarityScore($keywords, $description . ' ' . $name);

            $categoryScore = 0;
            if ($product->getShopId() && $product->getShopId()->getCategorie() &&
                in_array($product->getShopId()->getCategorie()->getIdCategorie(), $likedCategories)) {
                $categoryScore = 5;
            }

            $totalScore = $textScore + $categoryScore;

            if ($totalScore > 0) {
                $scoredProducts[] = [
                    'product' => $product,
                    'score' => $totalScore
                ];
            }
        }

        usort($scoredProducts, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $recommendedProducts = array_map(function($item) {
            return $item['product'];
        }, $scoredProducts);

        return array_slice($recommendedProducts, 0, 20);
    }

    private function extractKeywords(string $text): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
        $words = preg_split('/\s+/', $text);

        $stopWords = ['le', 'la', 'les', 'un', 'une', 'des', 'et', 'ou', 'de', 'du', 'au', 'aux',
            'ce', 'ces', 'cette', 'à', 'en', 'par', 'pour', 'sur', 'avec', 'sans', 'dans','the','this','a','and','on','in','for','his','her'];

        $wordCount = [];
        foreach ($words as $word) {
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                if (!isset($wordCount[$word])) {
                    $wordCount[$word] = 0;
                }
                $wordCount[$word]++;
            }
        }

        arsort($wordCount);
        return array_slice(array_keys($wordCount), 0, 20);
    }

    private function calculateSimilarityScore(array $keywords, string $text): float
    {
        $text = strtolower($text);
        $score = 0;

        foreach ($keywords as $keyword) {
            $count = substr_count($text, $keyword);
            $score += $count;

            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text)) {
                $score += 2;
            }
        }

        return $score;
    }
}