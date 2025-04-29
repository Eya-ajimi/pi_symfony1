<?php
namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventClient;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventClient::class);
    }

    public function isParticipating(int $userId, int $eventId): bool
    {
        $result = $this->createQueryBuilder('ec')
            ->select('COUNT(ec.idClient)')
            ->where('ec.idClient = :userId')
            ->andWhere('ec.idEvent = :eventId')
            ->setParameter('userId', $userId)
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function addParticipation(EventClient $eventClient): void
    {
        $this->getEntityManager()->persist($eventClient);
        $this->getEntityManager()->flush();
    }
    public function getTotalParticipantsCount(int $eventId): int
    {
        $result = $this->createQueryBuilder('ec')
            ->select('SUM(ec.places) as total')
            ->where('ec.idEvent = :eventId')
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (int)$result : 0;
    }
}