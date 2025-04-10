<?php

// src/Repository/ProduitRepository.php
namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\DiscountRepository;

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
            ->innerJoin('p.shopOwner', 'u') // Join with Utilisateur entity
            ->andWhere('u.id = :shopId')
            ->setParameter('shopId', $shopId)
            ->orderBy('p.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findWithFilters(
        int $shopId,
        string $searchTerm = '',
        ?float $minPrice = null,
        ?float $maxPrice = null,
        
    ) {
        $qb = $this->createQueryBuilder('p')
            ->where('p.shop = :shopId')
            ->setParameter('shopId', $shopId);
    
        if (!empty($searchTerm)) {
            $qb->andWhere('p.nom LIKE :searchTerm')
               ->setParameter('searchTerm', '%'.$searchTerm.'%');
        }
    
        if ($minPrice !== null) {
            $qb->andWhere('p.prix >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }
    
        if ($maxPrice !== null) {
            $qb->andWhere('p.prix <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }
    
        
    
        return $qb->getQuery()->getResult();
    }
}


