<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Enums\StatutCommande;
use App\Form\ShowDetailsType;
use App\Repository\CommandeRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CommandeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommandeController extends AbstractController
{
    #[Route('/shopOwner/showCommandePayee', name: 'app_show_commande_payee')]
    public function showCommandePayee(CommandeRepository $commandeRepository): Response
    {
        $utilisateur = $this->getUser();
        $todayCommands = $commandeRepository->findTodayPaidOrdersByShop($utilisateur->getId());
        $result = [];

        foreach ($todayCommands as $commande) {
            $result[] = [
                'numeroTicket' => $commande->getPaniers()[0]->getNumeroTicket(),
                'commandeId' => $commande->getId(),
                'date' => $commande->getDateCommande()->format('d-m-y'),
                'total' => $commande->getTotal(),
                'client' => $commande->getIdClient()->getNom() . " " . $commande->getIdClient()->getPrenom(),
                'paniers' => array_map(
                    fn(Panier $panier) => [
                        'produitNom' => $panier->getIdProduit()->getNom(),
                        'quantite' => $panier->getQuantite(),
                        'prixTotal' => $panier->getQuantite() * $panier->getIdProduit()->getPrix()
                    ],
                    $commande->getPaniers()->toArray()
                )
            ];
        }


        return $this->render('commande/index.html.twig', [
            'commandes' => $result,
            'utilisateurId' => $utilisateur->getId(),
        ]);
    }

    #[Route('/shopOwner/commandeDetails/{commandeId}/{shopId}', name: 'app_commande_details')]
    public function commandeDetails(int $commandeId, int $shopId, CommandeRepository $commandeRepository): Response
    {
        $commande = $commandeRepository->find($commandeId);

        if (!$commande) {
            throw $this->createNotFoundException('La commande n\'existe pas');
        }

        // Filtrer les paniers qui appartiennent au shop spécifié
        $paniersFiltered = $commande->getPaniers()->filter(
            fn(Panier $panier) => $panier->getIdProduit()->getShopId()->getId() == $shopId
        );

        return $this->render('commande/CommandeWithDetails.html.twig', [
            'commande' => [
                'ticketNumber' => $paniersFiltered[0]->getNumeroTicket(),
                'id' => $commande->getId(),
                'client' => [
                    'nom' => $commande->getIdClient()->getNom(),
                    'prenom' => $commande->getIdClient()->getPrenom(),

                ],
                'dateCommande' => $commande->getDateCommande(),
                'statut' => $commande->getStatut()->value,
                'total' => $commande->getTotal(),
                'paniers' => array_map(
                    fn(Panier $panier) => [
                        'produit' => [
                            'nom' => $panier->getIdProduit()->getNom(),
                            'prix' => $panier->getIdProduit()->getPrix()
                        ],
                        'quantite' => $panier->getQuantite()
                    ],
                    $paniersFiltered->toArray()
                ),
                'shopId' => $shopId
            ]
        ]);
    }

    #[Route('/shopOwner/CommandeConfirm/{commandeId}/{shopId}', name: 'app_commande_confirm')]
    public function confirmCommande(int $commandeId, int $shopId, CommandeRepository $commandeRepository, EntityManagerInterface $entityManager): Response
    {
        $commande = $commandeRepository->find($commandeId);

        if (!$commande) {
            throw $this->createNotFoundException('La commande n\'existe pas');
        }

        // Filtrer les paniers qui appartiennent au shop spécifié
        $paniersFiltered = $commande->getPaniers()->filter(
            fn(Panier $panier) => $panier->getIdProduit()->getShopId()->getId() === $shopId
        );

        // Marquer les paniers du shop comme "récupérés"
        foreach ($paniersFiltered as $panier) {
            $panier->setStatut(StatutCommande::recuperer);
        }

        // Vérifier s'il reste des paniers non récupérés
        $nonRecuperedItem = false;
        foreach ($commande->getPaniers() as $panier) {
            if ($panier->getStatut() === StatutCommande::payee) {
                $nonRecuperedItem = true;
                break;
            }
        }

        // Si tous les paniers sont récupérés, marquer la commande comme "récupérée"
        if (!$nonRecuperedItem) {
            $commande->setStatut(StatutCommande::recuperer);
        }

        $entityManager->flush();

        return $this->redirectToRoute('app_show_commande_payee');
    }


    #[Route('/client/CommandePayement/{clientId}', name: 'app_commande_payement')]
    public function payerCommande(
        int                    $clientId,
        CommandeRepository     $commandeRepository,
        EntityManagerInterface $entityManager,
        UtilisateurRepository  $utilisateurRepository
    ): Response
    {
        // 1. Récupération du client et vérification
        $client = $utilisateurRepository->find($clientId);
        if (!$client) {
            $this->addFlash('error', 'Client non trouvé.');
        }

        // 2. Récupération de la commande en cours
        $commandesEnCours = $commandeRepository->findBy([
            'idClient' => $clientId,
            'statut' => StatutCommande::enCours
        ]);


        if (empty($commandesEnCours)) {
            $this->addFlash('error', 'Aucune commande en cours trouvée.');
            return $this->redirectToRoute('app_show_panier');
        }
        else{

        $commandePrincipale = $commandesEnCours[0];

        // 3. Vérification du solde
        if ($client->getBalance() < $commandePrincipale->getTotal()) {
            $this->addFlash('error', 'Solde insuffisant.');
            return $this->redirectToRoute('app_show_panier');
                    }
        else{

       

        // 4. Groupement des paniers par shopOwner
        $paniersParShop = [];
        foreach ($commandePrincipale->getPaniers() as $panier) {
            $shopId = $panier->getIdProduit()->getShopId()->getId();
            $paniersParShop[$shopId][] = $panier;
        }

        // 5. Traitement par shop
        foreach ($paniersParShop as $shopId => $paniers) {
            // 1. Récupérer le shopOwner
            $shopOwner = $utilisateurRepository->find($shopId);
            $numeroTicket = $shopOwner->getNumeroTicket();


            // 3. Appliquer aux paniers
            foreach ($paniers as $panier) {
                $panier->setNumeroTicket($numeroTicket);
                $panier->setStatut(StatutCommande::payee);

                // 4. Mise à jour du stock
                $produit = $panier->getIdProduit();
                $produit->setStock($produit->getStock() - $panier->getQuantite());
            }
            $shopOwner->setNumeroTicket($numeroTicket + 1);
            // 5. Persister les modifications
            $entityManager->persist($shopOwner);
        }

        // 6. Mise à jour du client
        $client->setBalance($client->getBalance() - $commandePrincipale->getTotal());
        $client->setPoints($client->getPoints() + 100);
        $commandePrincipale->setStatut(StatutCommande::payee);
        $entityManager->flush();


        return $this->redirectToRoute('app_show_panier');
    }
    }
    }
}