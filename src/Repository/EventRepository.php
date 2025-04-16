<?php
namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function findAllEvents()
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o')
            ->getQuery()
            ->getResult();
    }

    public function findEventsByDate(\DateTimeInterface $date)
    {
        $dateStr = $date->format('Y-m-d');
        
        return $this->createQueryBuilder('e')
            ->where(':date >= e.dateDebut')
            ->andWhere(':date <= e.dateFin')
            ->setParameter('date', $dateStr)
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o')
            ->getQuery()
            ->getResult();
    }

    public function deletePastEvents(): int
    {
        $today = new \DateTime();
        $todayStr = $today->format('Y-m-d');

        return $this->createQueryBuilder('e')
            ->delete()
            ->where('e.dateFin < :today')
            ->setParameter('today', $todayStr)
            ->getQuery()
            ->execute();
    }
}