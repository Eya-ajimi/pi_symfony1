<?php
// src/Repository/EventRepository.php
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
    
    public function findByOrganizer(int $organizerId)
    {
        return $this->createQueryBuilder('e')
            ->join('e.organisateur', 'u')
            ->where('u.id = :organizerId')
            ->setParameter('organizerId', $organizerId)
            ->getQuery()
            ->getResult();
    }
}