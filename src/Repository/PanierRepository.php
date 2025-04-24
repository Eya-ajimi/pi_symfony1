<?php

namespace App\Repository;

use App\Entity\Panier;
use App\Entity\Commande;
use App\Entity\Produit;
use App\Enums\StatutCommande;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PanierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Panier::class);
    }

    public function produitExisteDansPanier(Commande $commande, Produit $produit): bool
    {
        $result = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.idCommande = :commande')
            ->andWhere('p.idProduit = :produit')
            ->setParameter('commande', $commande)
            ->setParameter('produit', $produit)
            ->getQuery()
            ->getSingleScalarResult();

        return $result > 0;
    }

    public function ajouterProduit(Commande $commande, Produit $produit): void
    {
        $panier = new Panier();
        $panier->setIdCommande($commande);
        $panier->setIdProduit($produit);
        $panier->setQuantite(1);
        $panier->setStatut(StatutCommande::enCours);

        $this->getEntityManager()->persist($panier);
        $this->getEntityManager()->flush();
    }


    //je peux l'utiliser directement avec findByIdCommande
    public function getPanierDetails(int $commandeId): array
    {
        $today = new \DateTime();

        $results = $this->createQueryBuilder('p')
            ->select(
                'p', // L'objet Panier complet
                'pr.id as produitId',
                'pr.nom',
                'pr.description',
                'pr.stock',
                'pr.prix as prixOriginal',
                'pr.image_url',
                'd.discountPercentage as discountPercentage',
                'd.startDate as discountStartDate',
                'd.endDate as discountEndDate'
            )
            ->join('p.idProduit', 'pr')
            ->leftJoin('pr.promotionId', 'd')
            ->where('p.idCommande = :commandeId')
            ->setParameter('commandeId', $commandeId)
            ->getQuery()
            ->getResult();

        // Traitement des résultats pour appliquer la logique de promotion
        $paniers = [];
        foreach ($results as $result) {
            // $result[0] contient l'objet Panier, les autres champs sont dans les clés nommées
            $panier = $result[0];
            $prixProduit = $result['prixOriginal'];
            $quantite = $panier->getQuantite();
            $prixTotal = $prixProduit * $quantite;

            // Vérifier si la promotion est active
            $isPromotionActive = ($result['discountPercentage'] !== null &&
                $result['discountStartDate'] <= $today &&
                $result['discountEndDate'] >= $today);

            if ($isPromotionActive) {
                $prixReduit = $prixProduit - ($prixProduit * $result['discountPercentage'] / 100);
                $prixTotal = $prixReduit * $quantite;
            }

            $paniers[] = [
                'panier' => $panier, // L'objet Panier complet
                'produitDetails' => [
                    'id' => $result['produitId'],
                    'nom' => $result['nom'],
                    'description' => $result['description'],
                    'stock' => $result['stock'],
                    'prixOriginal' => $prixProduit,
                    'image_url' => $result['image_url'],
                ],
                'promotionDetails' => [
                    'discountPercentage' => $result['discountPercentage'],
                    'startDate' => $result['discountStartDate'],
                    'endDate' => $result['discountEndDate'],
                    'isActive' => $isPromotionActive
                ],
                'prixTotal' => $prixTotal,
                'quantite' => $quantite
            ];
        }

        return $paniers;
    }



}