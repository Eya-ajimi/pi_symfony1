<?php

namespace App\Repository;

use App\Entity\LikedProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LikedProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LikedProduct::class);
    }

    // CREATE
    public function save(LikedProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    // READ
    public function findOneByUserAndProduct(int $userId, int $productId): ?LikedProduct
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.utilisateur = :userId')
            ->andWhere('l.produit = :productId')
            ->setParameter('userId', $userId)
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getOneOrNullResult();
    }
    

    public function findByUser(int $userId): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.utilisateur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('l.date_like', 'DESC')
            ->getQuery()
            ->getResult();
    }
    

    // UPDATE
    public function updateLikeDate(LikedProduct $likedProduct, \DateTimeInterface $newDate): void
    {
        $likedProduct->setDateLike($newDate);
        $this->getEntityManager()->flush();
    }

    // DELETE
    public function remove(LikedProduct $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function removeByUserAndProduct(int $userId, int $productId, bool $flush = false): bool
    {
        $likedProduct = $this->findOneByUserAndProduct($userId, $productId);
        if (!$likedProduct) {
            return false;
        }

        $this->remove($likedProduct, $flush);
        return true;
    }

    // Count likes for a product

    /** henee ya maria aandek pb mtaa id  */
    public function countLikesForProduct(int $productId): int
    {
        return $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('l.produit = :productId')
            ->setParameter('productId', $productId)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    

            public function findByProduct(int $productId): array
            {
                return $this->createQueryBuilder('l')
                    ->andWhere('l.produit = :productId')
                    ->setParameter('productId', $productId)
                    ->orderBy('l.date_like', 'DESC')
                    ->getQuery()
                    ->getResult();
            }
            

}