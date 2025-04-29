<?php

namespace App\Controller;

use App\Entity\Panier;
use App\Entity\Utilisateur;
use App\Enums\StatutCommande;
use App\Form\ShowDetailsType;
use App\Repository\CommandeRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Mime\Email;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class CommandeController extends AbstractController
{
    #[Route('/shopOwner/commands', name: 'commands')]
    public function commands(CommandeRepository $commandeRepository, Request $request): Response
    {
        $currentId = $this->getUser()->getId();

        // Get filter date from request or use today
        $filterDate = null;
        if ($request->query->has('date')) {
            try {
                $filterDate = new \DateTime($request->query->get('date'));
            } catch (\Exception $e) {
                // If invalid date is provided, default to today
                $filterDate = new \DateTime();
            }
        } else {
            $filterDate = new \DateTime();
        }

        // Check if the selected date is today
        $today = new \DateTime();
        $isToday = $filterDate->format('Y-m-d') === $today->format('Y-m-d');

        // Get paid orders for the selected date
        $filteredCommands = $commandeRepository->findPaidOrdersByShopAndDate($currentId, $filterDate);

        $result = [];
        foreach ($filteredCommands as $commande) {
            $result[] = [
                'numeroTicket' => $commande->getPaniers()->first()?->getNumeroTicket(),
                'commandeId' => $commande->getId(),
                'date' => $commande->getDateCommande()->format('d-m-y'),
                'total' => $commande->getTotal(),
                'client' => $commande->getIdClient()->getNom() . " " . $commande->getIdClient()->getPrenom(),
            ];
        }
        usort($result, function ($a, $b) {
            return $a['numeroTicket'] <=> $b['numeroTicket'];
        });

        return $this->render('maria_templates/commands.html.twig', [
            'commands' => $result,
            'utilisateurId' => $currentId,
            'selected_date' => $filterDate,
            'is_today' => $isToday,
        ]);
    }
    #[Route('/shopOwner/shopCommandeDetails/{commandeId}/{shopId}', name: 'app_shopCommande_details')]
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

        return $this->render('maria_templates/CommandeWithDetails.html.twig', [
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

        return $this->redirectToRoute('commands');
    }


    #[Route('/client/CommandePayement/{clientId}', name: 'app_commande_payement')]
    public function payerCommande(
        int                    $clientId,
        CommandeRepository     $commandeRepository,
        UtilisateurRepository  $utilisateurRepository,
        UrlGeneratorInterface  $urlGenerator
    ): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET']);
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
        } else {

            $commandePrincipale = $commandesEnCours[0];
            $panierStripe = array_map(
                function (Panier $panier) {
                    $produit = $panier->getIdProduit();
                    $prix = $produit->getPrix();

                    // Vérifier si le produit a une promotion
                    if ($produit->getPromotionId() !== null) {
                        $promotion = $produit->getPromotionId();
                        $aujourdHui = new \DateTime();

                        if ($promotion->getStartDate() <= $aujourdHui && $promotion->getEndDate() >= $aujourdHui) {
                            // Appliquer la réduction
                            $discountPercentage = $promotion->getDiscountPercentage(); // Exemple: 20 pour 20%
                            $prix = $prix - ($prix * ($discountPercentage / 100));
                        }
                    }

                    return [
                        'name' => $produit->getNom(),
                        'description' => $produit->getDescription(),
                        'quantity' => $panier->getQuantite(),
                        'price' => $prix
                    ];
                },
                $commandePrincipale->getPaniers()->toArray()
            );

            $lineItems = array_map(function ($product) {
                return [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $product['name'],
                            'description' => $product['description'],
                        ],
                        'unit_amount' => (int)($product['price'] * 100),
                    ],
                    'quantity' => $product['quantity'],
                ];
            }, $panierStripe);


            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'invoice_creation' => [
                    'enabled' => true // Active la génération de facture
                ],
                'success_url' => $urlGenerator->generate('proceed_payment_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $urlGenerator->generate('app_payment_cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            return $this->redirect($session->url);


        }
    }

    #[Route('/client/payment/success', name: 'proceed_payment_success')]
    public function success(
        CommandeRepository $commandeRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        Environment $twig
    ): Response
    {
        $user = $this->getUser();
        $commandesEnCours = $commandeRepository->findOneBy([
            'idClient' => $user->getId(),
            'statut' => StatutCommande::enCours
        ]);

        $paniersParShop = [];
        $ticketDataByShop = [];

        // Organisation des paniers par boutique
        foreach ($commandesEnCours->getPaniers() as $panier) {
            $shopId = $panier->getIdProduit()->getShopId()->getId();
            $paniersParShop[$shopId][] = $panier;
        }

        // Traitement par shop
        foreach ($paniersParShop as $shopId => $paniers) {
            // Récupérer le shopOwner
            $shopOwner = $utilisateurRepository->find($shopId);
            $numeroTicket = $shopOwner->getNumeroTicket();
            if($numeroTicket){
                $numeroTicket = $shopOwner->getNumeroTicket();
            }
            else{
                $numeroTicket ++;
            }
            $shopName = $shopOwner->getNom(); // Assurez-vous que cette méthode existe

            // Préparer les données pour le PDF
            $ticketData = [
                'shopName' => $shopName,
                'ticketNumber' => $numeroTicket,
                'products' => [],
                'totalCommande' => 0
            ];

            // Appliquer aux paniers
            foreach ($paniers as $panier) {
                $panier->setNumeroTicket($numeroTicket);
                $panier->setStatut(StatutCommande::payee);

                // Mise à jour du stock
                $produit = $panier->getIdProduit();
                $produit->setStock($produit->getStock() - $panier->getQuantite());

                // Ajouter les infos du produit pour le PDF
                $ticketData['products'][] = [
                    'nomProduit' => $produit->getNom(), // Assurez-vous que cette méthode existe
                    'description' => $produit->getDescription(), // Assurez-vous que cette méthode existe
                    'quantity' => $panier->getQuantite(),
                    'prix' => $produit->getPrix(),
                    'total' => $produit->getPrix() * $panier->getQuantite()
                ];

                $ticketData['totalCommande'] += $produit->getPrix() * $panier->getQuantite();
            }

            $shopOwner->setNumeroTicket($numeroTicket + 1);
            $entityManager->persist($shopOwner);

            // Stocker les données du ticket pour cette boutique
            $ticketDataByShop[$shopId] = $ticketData;
        }

        // Mise à jour du client
        $user->setPoints($user->getPoints() + 100);
        $commandesEnCours->setStatut(StatutCommande::payee);
        $entityManager->flush();

        // Génération et envoi des PDFs par email
        $this->sendOrderConfirmationEmail($user, $ticketDataByShop, $mailer, $twig);

        return $this->render('commande/success.html.twig', [
            'commandeId' => $commandesEnCours->getId(),
            'points' => $user->getPoints(),
            'ticketDataByShop' => $ticketDataByShop
        ]);
    }

    /**
     * Envoie l'email de confirmation avec les tickets PDF
     */
    private function sendOrderConfirmationEmail(
        $user,
        array $ticketDataByShop,
        MailerInterface $mailer,
        Environment $twig
    ): void {
        // Préparer le logo en base64
        $logoPath = $this->getParameter('kernel.project_dir') . '/public/logo/innoMall_logo_pi.png';
        $logoBase64 = base64_encode(file_get_contents($logoPath));

        // Créer un nouvel email
        $email = (new Email())
            ->from('Innomall.esprit@gmail.com')
            ->to(/*$user->getEmail()*/'houssemjribi111@gmail.com')
            ->subject('Confirmation de votre commande InnoMall')
            ->html($twig->render('commande/order_confirmation.html.twig', [
                'user' => $user,
                'ticketCount' => count($ticketDataByShop)
            ]));

        // Générer et attacher chaque PDF de ticket
        foreach ($ticketDataByShop as $shopId => $ticketData) {
            // Générer le HTML du ticket avec le logo en base64
            $ticketHtml = $twig->render('commande/ticket.html.twig', [
                'ticketData' => $ticketData,
                'logoBase64' => $logoBase64
            ]);

            // Convertir en PDF
            $dompdf = new Dompdf();
            $options = $dompdf->getOptions();
            $options->setIsHtml5ParserEnabled(true);
            $options->setIsRemoteEnabled(true);
            $dompdf->setOptions($options);

            $dompdf->loadHtml($ticketHtml);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfContent = $dompdf->output();

            // Attacher le PDF à l'email
            $email->attach(
                $pdfContent,
                'ticket_' . $ticketData['shopName'] . '_' . $ticketData['ticketNumber'] . '.pdf',
                'application/pdf'
            );
        }

        // Envoyer l'email
        $mailer->send($email);
    }

    #[Route('/client/payment/cancel', name: 'app_payment_cancel')]
    public function cancel(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}