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
        ->andWhere('IDENTITY(d.shop) = :shopId') // Query by the shop's ID
        ->andWhere('d.startDate <= :today')
        ->andWhere('d.endDate >= :today')
        ->setParameter('shopId', $shopId)
        ->setParameter('today', $today->format('Y-m-d'))
        ->getQuery()
        ->getResult();
}

}