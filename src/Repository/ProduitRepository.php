<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
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

    // Find all products by shop ID
    public function findByShopId(int $shopId): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.shop', 's')
            ->andWhere('s.id = :shopId')
            ->setParameter('shopId', $shopId)
            ->getQuery()
            ->getResult();
    }

    // Find a single product by ID
    public function findOneById(int $id): ?Produit
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
            ->join('p.shop', 's')
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

}