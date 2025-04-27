<?php

//// src/Repository/ProduitRepository.php
//namespace App\Repository;
//
//use App\Entity\Produit;
//use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
//use Doctrine\Persistence\ManagerRegistry;
//use App\Repository\DiscountRepository;

// src/Repository/ProduitRepository.php
namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    // Custom method to find products by shopId
    public function findByShopId(int $shopId)
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.shopId', 'u') // Join with Utilisateur entity
            ->andWhere('u.id = :shopId')
            ->setParameter('shopId', $shopId)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }


    public function findByFilters($shopId, $name = null, $minPrice = null, $maxPrice = null, $promotion = null)
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.shopId', 'u')
            ->andWhere('u.id = :shopId')
            ->setParameter('shopId', $shopId);

        if ($name) {
            $qb->andWhere('LOWER(p.nom) LIKE LOWER(:name)')
                ->setParameter('name', '%' . $name . '%');
        }

        if ($minPrice !== null && $minPrice !== '') {
            $qb->andWhere('p.prix >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null && $maxPrice !== '') {
            $qb->andWhere('p.prix <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        // Bonus pour promo plus tard

        return $qb->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Create (persist) a product
    public function save(Produit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // Remove (delete) a product
    public function remove(Produit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }




    // Find a single product by ID
    public function findProduit(int $id): ?Produit
    {
        return $this->find($id);
    }

    // Find all products
    public function findAllProducts(): array
    {
        return $this->findAll();
    }


    //fazet el likes
    public function findByShopIdWithLikeCounts(int $shopId): array
    {
        // First get products by shop
        $products = $this->createQueryBuilder('p')
            ->join('p.shopId', 's')
            ->where('s.id = :shopId')
            ->setParameter('shopId', $shopId)
            ->getQuery()
            ->getResult();

        if (empty($products)) {
            return [];
        }

        // Get like counts for these products
        $productIds = array_map(fn($p) => $p->getId(), $products);

        $likeCounts = $this->getEntityManager()
            ->createQuery('
            SELECT p.id as productId, COUNT(lp.id) as likeCount
            FROM App\Entity\LikedProduct lp
            JOIN lp.produit p
            WHERE p.id IN (:ids)
            GROUP BY p.id
        ')
            ->setParameter('ids', $productIds)
            ->getResult();

        // Convert to [productId => likeCount] format
        $countsMap = array_column($likeCounts, 'likeCount', 'productId');

        // Assign counts to products
        foreach ($products as $product) {
            $product->setLikeCount($countsMap[$product->getId()] ?? 0);
        }

        return $products;
    }


    public function removeWithRelations(Produit $product, bool $flush = true): void
    {
        $em = $this->getEntityManager();

        // 1. Handle related entities (modify according to your actual entity names)
        // If you have a LikedProduct entity but no repository:
        $likedProducts = $em->createQuery(
            'SELECT lp FROM App\Entity\LikedProduct lp WHERE lp.product = :product'
        )->setParameter('product', $product)
            ->getResult();

        foreach ($likedProducts as $likedProduct) {
            $em->remove($likedProduct);
        }

        // 3. Remove the product
        $em->remove($product);

        if ($flush) {
            $em->flush();
        }
    }

    public function findTopLikedProducts(int $shopId, int $limit = 4): array
    {
        // First get products with their like counts
        $products = $this->findByShopIdWithLikeCounts($shopId);

        // Sort products by like count (descending)
        usort($products, function ($a, $b) {
            return $b->getLikeCount() <=> $a->getLikeCount();
        });

        // Return only the top $limit products (now 5 by default)
        return array_slice($products, 0, $limit);
    }

    // src/Repository/ProduitRepository.php
    // src/Repository/ProduitRepository.php

    public function findTop10SoldProductsByShop(int $shopId): array
    {
        return $this->createQueryBuilder('p')
            ->select('p as product, SUM(pan.quantite) as totalQuantity')
            ->join('p.paniers', 'pan')
            ->join('App\Entity\Commande', 'c', 'WITH', 'pan.idCommande = c.id')
            ->where('p.shopId = :shopId')
            ->andWhere('c.statut IN (:statuses)')  // Changed to IN clause
            ->setParameter('shopId', $shopId)
            ->setParameter('statuses', ['payee', 'recuperer'])  // Both statuses
            ->groupBy('p.id')
            ->orderBy('totalQuantity', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

    }

    public function findByLowStockAndShop(int $shopId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.shopId = :shopId')
            ->andWhere('p.stock <= 10')
            ->andWhere('p.stock > 0')
            ->setParameter('shopId', $shopId)
            ->orderBy('p.stock', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByOutOfStockAndShop(int $shopId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.shopId = :shopId')
            ->andWhere('p.stock = 0')
            ->setParameter('shopId', $shopId)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

}


