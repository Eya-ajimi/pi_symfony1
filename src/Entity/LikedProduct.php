<?php

namespace App\Entity;

use App\Repository\LikedProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LikedProductRepository::class)]
#[ORM\Table(name: 'likeproduit')] // Matches your table name
#[ORM\UniqueConstraint(name: 'user_product_unique', columns: ['utilisateurId', 'produitId'])]
class LikedProduct
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "utilisateurId", referencedColumnName: "id", nullable: false)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: "produitId", referencedColumnName: "id", nullable: false)]
    private ?Produit $produit = null;

    #[ORM\Column(name: "date_like", type: 'datetime')]
    private ?\DateTimeInterface $date_like = null;

    // Getters & Setters...

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;
        return $this;
    }

    public function getDateLike(): ?\DateTimeInterface
    {
        return $this->date_like;
    }

    public function setDateLike(\DateTimeInterface $dateLike): self
    {
        $this->date_like = $dateLike;
        return $this;
    }
}