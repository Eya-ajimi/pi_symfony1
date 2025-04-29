<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
use App\Enums\Role;
use App\Enums\StatutCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findCommandeEnCours(Utilisateur $client): ?Commande
    {
        return $this->findOneBy([
            'idClient' => $client,
            'statut' => StatutCommande::enCours
        ]);
    }

    public function findPaidOrdersByShopAndDate(int $shopId, \DateTime $date)
    {
        $formattedDate = $date->format('Y-m-d');

        return $this->createQueryBuilder('c')
            ->innerJoin('c.paniers', 'p') // Only orders with items
            ->innerJoin('p.idProduit', 'prod') // Join with product
            ->innerJoin('c.idClient', 'client') // Join with client
            ->addSelect('p') // Load items
            ->addSelect('prod') // Load products
            ->addSelect('client') // Load client data
            ->where('c.statut = :statut')
            ->andWhere('p.statut = :statut') // Add condition on item status
            ->andWhere('c.dateCommande = :filterDate')
            ->andWhere('prod.shopId = :shopId') // Filter by shop
            ->setParameter('statut', StatutCommande::payee)
            ->setParameter('filterDate', $formattedDate)
            ->setParameter('shopId', $shopId)
            ->orderBy('c.dateCommande', 'DESC') // Most recent orders first
            ->getQuery()
            ->getResult();
    }


    public function findWeeklyShopStatistics(\DateTimeInterface $dateInWeek): array
    {
        $weekStart = (clone $dateInWeek)->modify('monday this week')->setTime(0, 0);
        $weekEnd = (clone $weekStart)->modify('sunday this week')->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->select([
                'shop.id as shopId',
                'shop.nom as shopName',
                'COUNT(c.id) as commandeCount'
            ])
            ->join('c.paniers', 'p')
            ->join('p.idProduit', 'prod')
            ->join('prod.shopId', 'shop')
            ->where('c.statut = :statut')
            ->andWhere('c.dateCommande BETWEEN :start AND :end')
            ->andWhere('shop.role = :role')
            ->setParameter('statut', StatutCommande::payee->value)
            ->setParameter('start', $weekStart->format('Y-m-d'))
            ->setParameter('end', $weekEnd->format('Y-m-d'))
            ->setParameter('role', Role::SHOPOWNER->value)
            ->groupBy('shop.id')
            ->getQuery()
            ->getResult();
    }
    public function findDailyShopSales(int $shopId, \DateTimeInterface $dateInWeek): array
    {
        $weekStart = (clone $dateInWeek)->modify('monday this week')->setTime(0, 0);
        $weekEnd = (clone $weekStart)->modify('sunday this week')->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->select([
                "SUBSTRING(c.dateCommande, 1, 10) as day",
                'COUNT(c.id) as salesCount'
            ])
            ->join('c.paniers', 'p')
            ->join('p.idProduit', 'prod')
            ->where('prod.shopId = :shopId')
            ->andWhere('c.statut = :statut')
            ->andWhere('c.dateCommande BETWEEN :start AND :end')
            ->setParameter('shopId', $shopId)
            ->setParameter('statut', StatutCommande::payee)
            ->setParameter('start', $weekStart->format('Y-m-d'))
            ->setParameter('end', $weekEnd->format('Y-m-d'))
            ->groupBy('day')
            ->orderBy('day', 'ASC')
            ->getQuery()
            ->getResult();
    }

}
