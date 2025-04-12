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

    public function findEventsByDate(string $date)
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateDebut <= :date AND e.dateFin >= :date')
            ->setParameter('date', $date)
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o')
            ->getQuery()
            ->getResult();
    }
}