<?php

// src/Entity/Produit.php
namespace App\Entity;
use App\Repository\ProduitRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    // Reference to the Utilisateur entity (Shop owner)
    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "shopId", referencedColumnName: "id", nullable: false)]
    private ?Utilisateur $shopOwner = null;

    #[ORM\Column(name: "nom", type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "promotionId", type: "integer", nullable: true)]
    private ?int $promotionId = null;

    #[ORM\Column(name: "stock", type: "integer")]
    private int $stock;

    #[ORM\Column(name: "prix", type: "decimal", precision: 10, scale: 2)]
    private string $prix;

    #[ORM\Column(name: "image_url", type: "string", length: 255, nullable: true)]
    private ?string $imageUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    // Getter and setter for shopOwner (shopId)
    public function getShopOwner(): ?Utilisateur
    {
        return $this->shopOwner;
    }

    public function setShopOwner(?Utilisateur $shopOwner): self
    {
        $this->shopOwner = $shopOwner;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getPromotionId(): ?int
    {
        return $this->promotionId;
    }

    public function setPromotionId(?int $promotionId): self
    {
        $this->promotionId = $promotionId;
        return $this;
    }

    public function getStock(): int
    {
        return $this->stock;
    }

    public function setStock(int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    public function getPrix(): string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): self
    {
        $this->imageUrl = $imageUrl;
        return $this;
    }
}
