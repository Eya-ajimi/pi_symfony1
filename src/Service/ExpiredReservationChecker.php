<?php
namespace App\Service;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ExpiredReservationChecker
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function checkExpiredReservations(): void
    {
        $expiredReservations = $this->reservationRepository->findExpiredReservations();
        
        foreach ($expiredReservations as $reservation) {
            $reservation->setStatut('expired');
            $spot = $reservation->getPlaceParking();
            if ($spot) {
                $spot->setStatut('free');
                $this->entityManager->persist($spot);
            }
            $this->entityManager->persist($reservation);
        }

        if (count($expiredReservations) > 0) {
            $this->entityManager->flush();
        }
    }
}