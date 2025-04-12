<?php
namespace App\Repository;

use App\Entity\EventClient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventClient::class);
    }

    public function isParticipating(int $clientId, int $eventId): bool
    {
        $result = $this->createQueryBuilder('ec')
            ->where('ec.client = :clientId AND ec.event = :eventId')
            ->setParameter('clientId', $clientId)
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getOneOrNullResult();

        return $result !== null;
    }

    public function addParticipation(EventClient $eventClient): void
    {
        $this->getEntityManager()->persist($eventClient);
        $this->getEntityManager()->flush();
    }
}