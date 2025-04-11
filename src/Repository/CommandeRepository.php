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

    public function createNewCommande(Utilisateur $client): Commande
    {
        $commande = new Commande();
        $commande->setIdClient($client);
        $commande->setDateCommande(new \DateTime());
        $commande->setStatut(StatutCommande::enCours);
        $commande->setTotal(0);

        $this->getEntityManager()->persist($commande);
        $this->getEntityManager()->flush();

        return $commande;
    }

    public function updateTotal(Commande $commande, float $montant): void
    {
        $commande->setTotal($commande->getTotal() + $montant);
        $this->getEntityManager()->persist($commande);
        $this->getEntityManager()->flush();
    }




}
