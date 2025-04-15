<?php
// src/Repository/ScheduleRepository.php
namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Utilisateur;


class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function findByShopId(int $shopId): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('<u class="utilisateur"></u> = :shopId')
            ->setParameter('shopId', $shopId)
            ->orderBy('s.dayOfWeek', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function save(Schedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Schedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}