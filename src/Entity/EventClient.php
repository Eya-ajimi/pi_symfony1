<?php
namespace App\Entity;

use App\Repository\EventClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventClientRepository::class)]
class EventClient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Utilisateur $client = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(length: 255)]
    private ?string $date = null;

    // Getters and setters...
    public function getId(): ?int { return $this->id; }
    public function getClient(): ?Utilisateur { return $this->client; }
    public function setClient(?Utilisateur $client): self { $this->client = $client; return $this; }
    public function getEvent(): ?Event { return $this->event; }
    public function setEvent(?Event $event): self { $this->event = $event; return $this; }
    public function getDate(): ?string { return $this->date; }
    public function setDate(string $date): self { $this->date = $date; return $this; }
}