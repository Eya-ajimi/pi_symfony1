<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\DiscountRepository;

#[ORM\Entity(repositoryClass: DiscountRepository::class)]
#[ORM\Table(name: 'discount')]
class Discount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: 'discount_percentage', type: 'decimal', precision: 5, scale: 2)]
    private string $discountPercentage;

    #[ORM\Column(name: 'start_date', type: 'date')]
    private \DateTimeInterface $startDate;

    #[ORM\Column(name: 'end_date', type: 'date')]
    private \DateTimeInterface $endDate;

    #[ORM\Column(name: 'shop_id', type: 'integer')]
    private int $shopId;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscountPercentage(): string
    {
        return $this->discountPercentage;
    }

    public function setDiscountPercentage(string $discountPercentage): self
    {
        $this->discountPercentage = $discountPercentage;
        return $this;
    }

    public function getStartDate(): \DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): \DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function getShopId(): int
    {
        return $this->shopId;
    }

    public function setShopId(int $shopId): self
    {
        $this->shopId = $shopId;
        return $this;
    }

    // Helper method to check if discount is currently active
    public function isActive(): bool
    {
        $now = new \DateTime();
        return $this->startDate <= $now && $this->endDate >= $now;
    }
}