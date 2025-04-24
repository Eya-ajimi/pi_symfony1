<?php
// src/Repository/ReservationRepository.php
namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    public function findActiveReservationsForUser(int $userId): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->leftJoin('r.placeParking', 'p')
            ->addSelect('p')
            ->where('r.idUtilisateur = :userId')
            ->andWhere('r.statut = :status')
            ->setParameter('userId', $userId)
            ->setParameter('status', 'active')
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllReservationsForUser(int $userId): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.placeParking', 'p')
            ->addSelect('p')
            ->where('r.idUtilisateur = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('r.dateReservation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function createReservation(array $data): Reservation
    {
        $reservation = new Reservation();
        $reservation->setIdUtilisateur($data['userId']);
        $reservation->setDateReservation(new \DateTime($data['startDateTime']));
        $reservation->setDateExpiration(new \DateTime($data['endDateTime']));
        $reservation->setStatut('active');
        $reservation->setVehicleType($data['vehicleType']);
        $reservation->setCarWashType($data['carWashType'] ?? null);
        $reservation->setNotes($data['notes'] ?? null);
        $reservation->setPrice($data['price']);

        $entityManager = $this->getEntityManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        return $reservation;
    }

    public function cancelReservation(int $reservationId): bool
    {
        $reservation = $this->find($reservationId);
        
        if (!$reservation) {
            return false;
        }

        $reservation->setStatut('cancelled');
        
        $entityManager = $this->getEntityManager();
        $entityManager->persist($reservation);
        $entityManager->flush();

        return true;
    }
    public function findExpiredReservations(): array
    {
    return $this->createQueryBuilder('r')
        ->where('r.statut = :status')
        ->andWhere('r.dateExpiration < :now')
        ->setParameter('status', 'active')
        ->setParameter('now', new \DateTime())
        ->getQuery()
        ->getResult();
    }
}