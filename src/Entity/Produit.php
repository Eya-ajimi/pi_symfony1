<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'shopId', referencedColumnName: 'id')]
    private ?Utilisateur $shopId = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;


    #[ORM\ManyToOne(targetEntity: Discount::class)]
    #[ORM\JoinColumn(name: "promotionId", referencedColumnName: "id")]
    private ?Discount $promotionId = null;  

    #[ORM\Column]
    private ?int $stock = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $prix = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $image_url = null;

    /**
     * @ORM\OneToMany(targetEntity="LikedProduct", mappedBy="product", cascade={"remove"})
     */
    private $likedProducts;
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShopId(): ?Utilisateur
    {
        return $this->shopId;
    }

    // In Produit.php - This is WRONG// In Produit.php
    public function setShopId(?Utilisateur $shopId): static
    {
        $this->shopId = $shopId;  // Correct - sets the property
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

    public function getPromotionId(): ?Discount
    {
        return $this->promotionId;
    }

    public function setPromotionId(?Discount $discount): static
    {
        $this->promotionId = $discount;

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
            return $this->promotionId && $this->promotionId->getId()
                ? (float) $this->promotionId->getDiscountPercentage()
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




    // PARTIE HOUSSEM 


    /**
     * @var Collection<int, Panier>
     */
    #[ORM\OneToMany(targetEntity: Panier::class, mappedBy: 'idProduit')]
    private Collection $paniers;


    /**
     * @return Collection<int, Panier>
     */
    public function getPaniers(): Collection
    {
        return $this->paniers;
    }

    public function addPanier(Panier $panier): static
    {
        if (!$this->paniers->contains($panier)) {
            $this->paniers->add($panier);
            $panier->setIdProduit($this);
        }

        return $this;
    }

    public function removePanier(Panier $panier): static
    {
        if ($this->paniers->removeElement($panier)) {
            // set the owning side to null (unless already changed)
            if ($panier->getIdProduit() === $this) {
                $panier->setIdProduit(null);
            }
        }

        return $this;
    }
}