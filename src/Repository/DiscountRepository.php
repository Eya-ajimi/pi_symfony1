<?php

// src/Repository/DiscountRepository.php

namespace App\Repository;

use App\Entity\Discount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Discount|null find($id, $lockMode = null, $lockVersion = null)
 * @method Discount|null findOneBy(array $criteria, array $orderBy = null)
 * @method Discount[]    findAll()
 * @method Discount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DiscountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discount::class);
    }

    // Custom methods to find discounts for a specific shop
    public function findByShopId(int $shopId)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.shopId = :shopId')
            ->setParameter('shopId', $shopId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveDiscountForShop(int $shopId, \DateTimeInterface $date)
{
    return $this->createQueryBuilder('d')
        ->where('d.shop_id = :shopId')
        ->andWhere('d.start_date <= :date')
        ->andWhere('d.end_date >= :date')
        ->setParameter('shopId', $shopId)
        ->setParameter('date', $date)
        ->setMaxResults(1)
        ->getQuery()
        ->getOneOrNullResult();
}
}
