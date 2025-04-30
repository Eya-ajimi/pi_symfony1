<?php

// src/Repository/FeedbackRepository.php
namespace App\Repository;

use App\Entity\Feedback;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FeedbackRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Feedback::class);
    }

    public function findOneByUserAndShop(Utilisateur $user, Utilisateur $shop): ?Feedback
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.user = :user')
            ->andWhere('f.shop = :shop')
            ->setParameter('user', $user)
            ->setParameter('shop', $shop)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
    public function countByShop(Utilisateur $shop): int
    {
        return $this->count(['shop' => $shop]);
    }
    
    public function getAverageRatingValue(Utilisateur $shop): float
    {
        $result = $this->createQueryBuilder('f')
            ->select('AVG(f.rating) as average')
            ->where('f.shop = :shop')
            ->setParameter('shop', $shop)
            ->getQuery()
            ->getSingleScalarResult();
    
        return $result ? (float) $result : 0;
    }
    
    public function getRatingDistribution(Utilisateur $shop): array
    {
        $result = $this->createQueryBuilder('f')
            ->select('f.rating, COUNT(f.id) as count')
            ->where('f.shop = :shop')
            ->setParameter('shop', $shop)
            ->groupBy('f.rating')
            ->orderBy('f.rating', 'DESC')
            ->getQuery()
            ->getResult();

        // Initialize with zeros for all possible ratings
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

        // Fill with actual counts
        foreach ($result as $row) {
            $distribution[(int) $row['rating']] = (int) $row['count'];
        }

        return $distribution;
    }
    
}