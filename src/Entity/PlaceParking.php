<?php
// src/Entity/PlaceParking.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PlaceParkingRepository;
#[ORM\Table(name: 'placeparking')]
#[ORM\Entity(repositoryClass: PlaceParkingRepository::class)]
class PlaceParking
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "IDENTITY")] 
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $zone = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = null;

    #[ORM\Column]
    private ?string $floor = null;

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getZone(): ?string
    {
        return $this->zone;
    }

    public function setZone(string $zone): self
    {
        $this->zone = $zone;
        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getFloor(): ?string
    {
        return $this->floor;
    }

    public function setFloor(string $floor): self
{
    $this->floor = $floor;
    return $this;
}
}