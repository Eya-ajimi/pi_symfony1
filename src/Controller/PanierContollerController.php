<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Enums\StatutCommande;
use App\Repository\CommandeRepository;
use App\Repository\PanierRepository;
use App\Repository\ProduitRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PanierContollerController extends AbstractController
{
    public function __construct(
        private PanierRepository $panierRepository,
        private EntityManagerInterface $entityManager,
        private CommandeRepository $commandeRepository,
        private UtilisateurRepository $utilisateurRepository
    ) {}

    #[Route('/panier/contoller', name: 'app_show_panier')]
    public function showPanier(): Response
    {


        $utilisateur = new Utilisateur();
        $utilisateur->setId(7);
        $clientId=$utilisateur->getId();
        $commandeEnCours = $this->commandeRepository->findCommandeEnCours($utilisateur);
        if (!$commandeEnCours) {
            return $this->render('panier_contoller/index.html.twig', [
                'panierList' => [],
                'itemsNumber' => 0,
                'totalCommande' => 0,
                'clientId' => 0,
            ]);
        }
        else{
            $idCommandeEnCours = $commandeEnCours->getId();
            $panierList = $this->panierRepository->getPanierDetails($idCommandeEnCours);

            // Vérification des stocks et ajustement des quantités
            foreach ($panierList as $key => $panierDetail) {
                $panier = $panierDetail['panier'];
                $produit = $panier->getIdProduit();
                $quantitePanier = $panier->getQuantite();
                $stockProduit = $produit->getStock();

                if ($quantitePanier > $stockProduit) {
                    if ($stockProduit == 0) {
                        // Supprimer l'article du panier si le stock est épuisé
                        $this->entityManager->remove($panier);
                        unset($panierList[$key]);
                    } else {
                        // Mettre à jour la quantité dans le panier avec le stock disponible
                        $panier->setQuantite($stockProduit);

                        // Recalculer le prix total
                        $prixUnitaire = $panierDetail['produitDetails']['prixOriginal'];
                        if ($panierDetail['promotionDetails']['isActive']) {
                            $prixUnitaire = $prixUnitaire - ($prixUnitaire * $panierDetail['promotionDetails']['discountPercentage'] / 100);
                        }
                        $panier->setPrix($prixUnitaire * $stockProduit);
                    }
                }
            }

            // Persister les changements en base de données
            $this->entityManager->flush();

            // Réindexer le tableau après les éventuelles suppressions
            $panierList = array_values($panierList);
            $itemsNumber = count($panierList);
            $totalCommande = 0;
            foreach ($panierList as $panier) {
                $totalCommande += $panier['prixTotal'];
            }

            $commandeEnCours->setTotal($totalCommande);
            $this->entityManager->flush();


            return $this->render('panier_contoller/index.html.twig', [
                'panierList' => $panierList,
                'itemsNumber' => $itemsNumber,
                'totalCommande' => $totalCommande,
                'clientId' => $clientId,
            ]);
        }

    }

    #[Route('/panier/addQuantite/{idCommande}/{idProduit}', name: 'app_add_quantite')]
    public function addQuantite($idCommande, $idProduit): Response {
        $panierItem = $this->panierRepository->findBy([
            'idCommande' => $idCommande,
            'idProduit' => $idProduit
        ]);

        if (!$panierItem) {
            throw $this->createNotFoundException('Panier item not found');
        }

        $newQuantite = $panierItem[0]->getQuantite() + 1;

        if ($newQuantite > $panierItem[0]->getIdProduit()->getStock()) {
            // Ajout d'un message flash pour informer l'utilisateur
            $this->addFlash('error', 'La quantité demandée dépasse le stock disponible!');
            return $this->redirectToRoute('app_show_panier');
        }

        $panierItem[0]->setQuantite($newQuantite);

        // // Mise à jour du total de la commande
        // $newTotalCommande = $panierItem[0]->getIdCommande()->getTotal() + $panierItem[0]->getIdProduit()->getPrix();
        // $panierItem[0]->getIdCommande()->setTotal($newTotalCommande);

        $this->entityManager->flush();

        // Ajout d'un message flash pour confirmer l'action
        $this->addFlash('success', 'Quantité augmentée avec succès!');

        // Redirection vers la page du panier
        return $this->redirectToRoute('app_show_panier');
    }

    #[Route('/panier/diminuerQuantite/{idCommande}/{idProduit}', name: 'app_diminuer_quantite')]
    public function diminuerQuantite($idCommande, $idProduit): Response {
        $panierItem = $this->panierRepository->findBy([
            'idCommande' => $idCommande,
            'idProduit' => $idProduit
        ]);

        if (!$panierItem) {
            throw $this->createNotFoundException('Panier item not found');
        }

        $newQuantite = $panierItem[0]->getQuantite() - 1;

        if($newQuantite == 0){
            // Ajout d'un message flash pour informer l'utilisateur
            $this->addFlash('warning', 'La quantité minimum est atteinte. Pour supprimer l\'article, cliquez sur la croix.');
            return $this->redirectToRoute('app_show_panier');
        }

        $panierItem[0]->setQuantite($newQuantite);

        // Mise à jour du total de la commande
        // $newTotalCommande = $panierItem[0]->getIdCommande()->getTotal() - $panierItem[0]->getIdProduit()->getPrix();
        // $panierItem[0]->getIdCommande()->setTotal($newTotalCommande);

        $this->entityManager->flush();

        // Ajout d'un message flash pour confirmer l'action
        $this->addFlash('success', 'Quantité diminuée avec succès!');

        // Redirection vers la page du panier
        return $this->redirectToRoute('app_show_panier');
    }
    #[Route('/panier/deletePanier/{idCommande}/{idProduit}', name: 'app_delete_item')]
    public function deleteItem($idCommande, $idProduit): Response
    {
        // Récupérer l'item du panier
        $panierItem = $this->panierRepository->findOneBy([
            'idCommande' => $idCommande,
            'idProduit' => $idProduit
        ]);

        if (!$panierItem) {
            throw $this->createNotFoundException('Panier item not found');
        }

        // Calculer le nouveau total
        $commande = $panierItem->getIdCommande();
        $newTotalCommande = $commande->getTotal() - ($panierItem->getIdProduit()->getPrix() * $panierItem->getQuantite());
        $commande->setTotal($newTotalCommande);

        // Supprimer l'item du panier
        $this->entityManager->remove($panierItem);
        $this->entityManager->flush();

        // Vérifier si le panier est maintenant vide
        $count = $this->panierRepository->count(['idCommande' => $idCommande]);

        if ($count === 0) {
            // Supprimer la commande si le panier est vide
            $this->entityManager->remove($commande);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_show_panier');
    }


    #[Route('/panier/ajouterPanier/{idProduit}/{shopId}', name: 'app_add_item')]
    public function addItem($shopId,$idProduit, ProduitRepository $produitRepository): Response
    {
        // Récupération du produit
        $produit = $produitRepository->find($idProduit);
    
        if (!$produit) {
            throw $this->createNotFoundException('Produit non trouvé');
        }
           
        // Vérification du stock
        if ($produit->getStock() == 0) {
            $this->addFlash('error', 'Ce produit est hors stock');
            return $this->redirectToRoute('app_show_panier');
        }
    
        // Récupération de l'utilisateur
        $utilisateur = $this->utilisateurRepository->findOneById(7);
    
        // Recherche de la commande en cours
        $commandeEnCours = $this->commandeRepository->findCommandeEnCours($utilisateur);
    
        // Si pas de commande en cours, on en crée une nouvelle
        if (!$commandeEnCours) {
            $commandeEnCours = new Commande();
            $commandeEnCours->setIdClient($utilisateur);
            $commandeEnCours->setDateCommande(new \DateTime());
            $commandeEnCours->setStatut(StatutCommande::enCours);
            $commandeEnCours->setTotal(0);
            
            // Persist the new Commande first
            $this->entityManager->persist($commandeEnCours);
            $this->entityManager->flush(); // Optional: you could remove this flush and only keep the final one
        }
    
        // Vérification si le produit est déjà dans le panier
        $panierExist = $this->panierRepository->findOneBy([
            'idCommande' => $commandeEnCours->getId(),
            'idProduit' => $produit->getId()
        ]);
    
        if ($panierExist) {
            $this->addFlash('warning', 'Ce produit est déjà dans votre panier');
            return $this->redirectToRoute('shop_products',[
                'shopId'=>$shopId
            ]);
        }
    
        // Création d'un nouveau panier
        $panier = new Panier();
        $panier->setIdCommande($commandeEnCours);
        $panier->setIdProduit($produit);
        $panier->setQuantite(1);
        $panier->setStatut(StatutCommande::enCours);
        $this->entityManager->persist($panier);
    
        // Calcul du prix (avec promotion si applicable)
        $prix = $produit->getPrix();
        if ($produit->getPromotionId() && $produit->getPromotionId()->getDiscountPercentage() > 0) {
            $prix = $prix * (1 - ($produit->getPromotionId()->getDiscountPercentage() / 100));
        }
    
        // Mise à jour du total de la commande
        $commandeEnCours->setTotal($commandeEnCours->getTotal() + $prix);
    
        $this->entityManager->flush();
    
        $this->addFlash('success', 'Produit ajouté au panier avec succès');
        return $this->redirectToRoute('shop_products',[
            'shopId'=>$shopId
        ]);
    }
}