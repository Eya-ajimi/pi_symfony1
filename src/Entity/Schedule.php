<?php

namespace App\Entity;

use App\Enum\DayOfWeek;
use App\Repository\ScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
#[ORM\Table(name: 'schedule')]
class Schedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(
        type: 'string',
        length: 10,
        enumType: DayOfWeek::class
    )]
    private DayOfWeek $dayOfWeek;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $openingTime;

    #[ORM\Column(type: Types::TIME_MUTABLE)]
    private \DateTimeInterface $closingTime;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'ShopId', referencedColumnName: 'id')]
    private ?Utilisateur $shopId = null;

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDayOfWeek(): DayOfWeek
    {
        return $this->dayOfWeek;
    }

    public function setDayOfWeek(DayOfWeek $dayOfWeek): self
    {
        $this->dayOfWeek = $dayOfWeek;
        return $this;
    }

    public function getOpeningTime(): \DateTimeInterface
    {
        return $this->openingTime;
    }

    public function setOpeningTime(\DateTimeInterface $openingTime): self
    {
        $this->openingTime = $openingTime;
        return $this;
    }

    public function getClosingTime(): \DateTimeInterface
    {
        return $this->closingTime;
    }

    public function setClosingTime(\DateTimeInterface $closingTime): self
    {
        $this->closingTime = $closingTime;
        return $this;
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
}
