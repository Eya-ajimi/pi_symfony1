<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Entity\Utilisateur;
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

    public function findTodayPaidOrdersByShop(int $shopId)
    {
        $today = (new \DateTime())->format('Y-m-d');
        return $this->createQueryBuilder('c')
            ->innerJoin('c.paniers', 'p') // Seulement les commandes avec paniers
            ->innerJoin('p.idProduit', 'prod') // Jointure avec produit
            ->addSelect('p') // Charge les paniers
            ->addSelect('prod') // Charge les produits
            ->where('c.statut = :statut')
            ->andWhere('p.statut = :statut') // Ajout de la condition sur le statut du panier
            ->andWhere('c.dateCommande = :today')
            ->andWhere('prod.shopId = :shopId') // Filtre par shop
            ->setParameter('statut', StatutCommande::payee)
            ->setParameter('today', $today)
            ->setParameter('shopId', $shopId) // Utilisation du paramètre shopId au lieu d'une valeur codée en dur
            ->getQuery()
            ->getResult();
    }






}
