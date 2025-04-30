<?php
namespace App\Repository;

use App\Entity\Event;
use App\Entity\EventClient;
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
            ->leftJoin('e.likes', 'l')
            ->addSelect('l')
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
            ->leftJoin('e.likes', 'l')
            ->addSelect('l')
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
    public function findMonthlyParticipationStats(int $shopId): array
    {
        $currentYear = date('Y');

        $results = $this->createQueryBuilder('e')
            ->select([
                "MONTH(e.dateDebut) as month",
                "COUNT(ec.idClient) as participants",
                "COUNT(DISTINCT e.id) as events"
            ])
            ->leftJoin('e.eventClients', 'ec')
            ->where('e.organisateur = :shopId')
            ->andWhere("YEAR(e.dateDebut) = :year")
            ->setParameter('shopId', $shopId)
            ->setParameter('year', $currentYear)
            ->groupBy('month')
            ->orderBy('month', 'ASC')
            ->getQuery()
            ->getResult();

        // Format the results to include all months
        $monthlyStats = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthlyStats[$i] = [
                'month' => $i,
                'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                'participants' => 0,
                'events' => 0
            ];
        }

        foreach ($results as $result) {
            $month = $result['month'];
            $monthlyStats[$month]['participants'] = $result['participants'];
            $monthlyStats[$month]['events'] = $result['events'];
        }

        return array_values($monthlyStats);
    }
    public function countParticipants(int $eventId): int
    {
        return $this->_em->getRepository(EventClient::class)
            ->getTotalParticipantsCount($eventId);
    }

    public function findEventsWithLikeCount()
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.likes', 'l')
            ->addSelect('COUNT(l.id) as likeCount')
            ->groupBy('e.id')
            ->getQuery()
            ->getResult();
    }
    public function searchByName(string $query): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.description LIKE :query OR e.emplacement LIKE :query')
            ->setParameter('query', '%'.$query.'%')
            ->leftJoin('e.organisateur', 'o')
            ->addSelect('o')
            ->leftJoin('e.likes', 'l')
            ->addSelect('l')
            ->getQuery()
            ->getResult();
    }
}