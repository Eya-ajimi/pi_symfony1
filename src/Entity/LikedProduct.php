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

    #[ORM\Column(name: 'utilisateurId', type: 'integer')]
    private int $userId;

    #[ORM\Column(name: 'produitId', type: 'integer')]
    private int $productId;

    #[ORM\Column(name: 'date_like', type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $dateLike;

    public function __construct()
    {
        $this->dateLike = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getDateLike(): \DateTimeInterface
    {
        return $this->dateLike;
    }

    public function setDateLike(\DateTimeInterface $dateLike): self
    {
        $this->dateLike = $dateLike;
        return $this;
    }
}