<?php
namespace App\Repository;

use App\Entity\EventLike;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EventLikeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EventLike::class);
    }

    public function addLike(EventLike $like): void
    {
        $this->getEntityManager()->persist($like);
        $this->getEntityManager()->flush();
    }

    public function removeLike(EventLike $like): void
    {
        $this->getEntityManager()->remove($like);
        $this->getEntityManager()->flush();
    }

    public function getMostLikedEvents(int $limit = 5): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('e.id as eventId', 'e.nomOrganisateur', 'e.description', 'e.dateDebut', 'e.dateFin', 'e.emplacement', 'COUNT(el.id) as likeCount')
            ->from(EventLike::class, 'el')
            ->join('el.event', 'e')
            ->groupBy('e.id')
            ->orderBy('likeCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}