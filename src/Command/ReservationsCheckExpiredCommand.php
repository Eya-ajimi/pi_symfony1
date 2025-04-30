<?php

namespace App\Command;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'reservations:check-expired',
    description: 'Checks and updates expired parking reservations',
)]
class ReservationsCheckExpiredCommand extends Command
{
    public function __construct(
        private ReservationRepository $reservationRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
       
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
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
            $io->success(sprintf('Updated %d expired reservations.', count($expiredReservations)));
        } else {
            $io->note('No expired reservations found.');
        }

        return Command::SUCCESS;
    }
}