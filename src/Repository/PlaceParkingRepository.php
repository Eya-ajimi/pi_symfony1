<?php
// src/Repository/PlaceParkingRepository.php
namespace App\Repository;

use App\Entity\PlaceParking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PlaceParkingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlaceParking::class);
    }

    public function findByFloor(int $floor): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.floor = :floor')
            ->setParameter('floor', $floor)
            ->getQuery()
            ->getResult();
    }

    public function countAvailableSpots(int $floor): int
{
    return $this->createQueryBuilder('p')
        ->select('count(p.id)')
        ->andWhere('p.floor = :floor')
        ->andWhere('p.statut = :statut')  // Changed to :statut
        ->setParameter('floor', $floor)
        ->setParameter('statut', 'free')  // Changed to 'free' to match your system
        ->getQuery()
        ->getSingleScalarResult();
}

    public function countTotalSpots(int $floor): int
    {
        return $this->createQueryBuilder('p')
            ->select('count(p.id)')
            ->andWhere('p.floor = :floor')
            ->setParameter('floor', $floor)
            ->getQuery()
            ->getSingleScalarResult();
    }
}