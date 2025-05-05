<?php
// src/Repository/ParkingAssignmentRepository.php
namespace App\Repository;

use App\Entity\ParkingAssignment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParkingAssignment>
 */
class ParkingAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParkingAssignment::class);
    }

    /**
     * Finds the most recent parking assignment for a given phone number.
     *
     * @param string $phoneNumber The phone number to search for.
     * @return ParkingAssignment|null The latest assignment or null if none found.
     */
    public function findLatestByPhoneNumber(string $phoneNumber): ?ParkingAssignment
    {
        // Normalize phone number for search (same logic as in setter)
        $normalizedPhoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        return $this->createQueryBuilder('pa')
            ->andWhere('pa.phoneNumber = :phone')
            ->setParameter('phone', $normalizedPhoneNumber)
            ->orderBy('pa.scannedAt', 'DESC') // Order by most recent first
            ->setMaxResults(1)               // Get only the latest one
            ->getQuery()
            ->getOneOrNullResult(); // Return one result or null
    }

    // Add save/remove methods if needed (often handled by EntityManager directly in controller)
    // public function save(ParkingAssignment $entity, bool $flush = false): void
    // {
    //     $this->getEntityManager()->persist($entity);
    //     if ($flush) {
    //         $this->getEntityManager()->flush();
    //     }
    // }
    // public function remove(ParkingAssignment $entity, bool $flush = false): void
    // {
    //     $this->getEntityManager()->remove($entity);
    //     if ($flush) {
    //         $this->getEntityManager()->flush();
    //     }
    // }
}