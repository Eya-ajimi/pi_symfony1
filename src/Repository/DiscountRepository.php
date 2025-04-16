<?php

namespace App\Repository;

use App\Entity\Discount;
use App\Entity\Utilisateur;    
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DiscountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discount::class);
    }

    public function save(Discount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Discount $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findActiveDiscountsForShop(int $shopId): array
    {
        $today = new \DateTime();
        return $this->createQueryBuilder('d')
            ->andWhere('d.shop = :shopId')
            ->andWhere('d.startDate <= :today')
            ->andWhere('d.endDate >= :today')
            ->setParameter('shopId', $shopId)
            ->setParameter('today', $today->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findActiveDiscountsByShop(Utilisateur $shop): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.shop = :shop')
            ->andWhere('d.endDate >= :today')
            ->setParameter('shop', $shop)
            ->setParameter('today', new \DateTime())
            ->orderBy('d.discountPercentage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}