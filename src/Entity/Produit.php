<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'shopId', referencedColumnName: 'id')]
    private ?Utilisateur $shop = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: Discount::class)]
    #[ORM\JoinColumn(name: "promotionId", referencedColumnName: "id")]
    private ?Discount $discount = null;

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_url = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShop(): ?Utilisateur
    {
        return $this->shop;
    }

    public function setShop(?Utilisateur $shop): static
    {
        $this->shop = $shop;

        return $this;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }

    public function setDiscount(?Discount $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getImage_url(): ?string
    {
        return $this->image_url;
    }

    public function setImage_url(?string $image_url): static
    {
        $this->image_url = $image_url;

        return $this;
    }



    // As a model View 

    public function getDiscountPercentage(): ?float
    {
        try {
            return $this->discount && $this->discount->getId()
                ? (float) $this->discount->getDiscountPercentage()
                : null;
        } catch (\Doctrine\ORM\EntityNotFoundException $e) {
            return null;
        }
    }

    public function getDiscountedPrice(): string
    {
        $percentage = $this->getDiscountPercentage();
        if ($percentage === null) {
            return $this->prix;
        }

        $discountedPrice = (float) $this->prix * (1 - ($percentage / 100));
        return number_format($discountedPrice, 2);
    }



    //fazet el likes 
    private ?int $likeCount = null;

    public function getLikeCount(): ?int
    {
        return $this->likeCount;
    }

    public function setLikeCount(int $likeCount): self
    {
        $this->likeCount = $likeCount;
        return $this;
    }
}