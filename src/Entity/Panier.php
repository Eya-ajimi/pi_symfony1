<?php

namespace App\Entity;

use App\Enums\StatutCommande;
use App\Repository\PanierRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PanierRepository::class)]
class Panier
{
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'paniers')]
    #[ORM\JoinColumn(name: 'idCommande', referencedColumnName: 'id')]
    private ?Commande $idCommande = null;
    #[ORM\Id]
    #[ORM\ManyToOne(inversedBy: 'paniers')]
    #[ORM\JoinColumn(name: 'idProduit', referencedColumnName: 'id')]

    private ?Produit $idProduit = null;

    #[ORM\Column]
    private ?int $quantite = null;

    #[ORM\Column(name: 'numeroTicket', nullable: true)]
    private ?int $numeroTicket = null;

    #[ORM\Column(enumType: StatutCommande::class)]
    private ?StatutCommande $statut = null;



    public function getIdCommande(): ?Commande
    {
        return $this->idCommande;
    }

    public function setIdCommande(?Commande $idCommande): static
    {
        $this->idCommande = $idCommande;

        return $this;
    }

    public function getIdProduit(): ?Produit
    {
        return $this->idProduit;
    }

    public function setIdProduit(?Produit $idProduit): static
    {
        $this->idProduit = $idProduit;

        return $this;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getNumeroTicket(): ?int
    {
        return $this->numeroTicket;
    }

    public function setNumeroTicket(?int $numeroTicket): static
    {
        $this->numeroTicket = $numeroTicket;

        return $this;
    }

    public function getStatut(): ?StatutCommande
    {
        return $this->statut;
    }

    public function setStatut(StatutCommande $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
