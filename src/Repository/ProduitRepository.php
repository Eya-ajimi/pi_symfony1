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

        // Get like counts for these products
        $productIds = array_map(fn($p) => $p->getId(), $products);

        $likeCounts = $this->getEntityManager()
            ->createQuery('
                SELECT lp.productId, COUNT(lp.id) as count 
                FROM App\Entity\LikedProduct lp 
                WHERE lp.productId IN (:ids) 
                GROUP BY lp.productId
            ')
            ->setParameter('ids', $productIds)
            ->getResult();

        // Convert to [productId => count] format
        $countsMap = array_column($likeCounts, 'count', 'productId');

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
}


