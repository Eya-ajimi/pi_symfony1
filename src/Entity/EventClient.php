<?php
namespace App\Entity;

use App\Repository\EventClientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'eventclient')]
#[ORM\Entity(repositoryClass: EventClientRepository::class)]
class EventClient
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'eventClients')]
    #[ORM\JoinColumn(name: 'idEvent', referencedColumnName: 'id', nullable: false)]
    private ?Event $idEvent = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'eventClients')]
    #[ORM\JoinColumn(name: 'idClient', referencedColumnName: 'id', nullable: false)]
    private ?Utilisateur $idClient = null;

    #[ORM\Column(length: 255)]
    private ?string $date = null;

    public function getDate(): ?string { return $this->date; }
    public function setDate(string $date): self { $this->date = $date; return $this; }

    public function getIdClient(): ?Utilisateur
    {
        return $this->idClient;
    }

    public function setIdClient(?Utilisateur $idClient): static
    {
        $this->idClient = $idClient;
        return $this;
    }

    public function getIdEvent(): ?Event
    {
        return $this->idEvent;
    }

    public function setIdEvent(?Event $idEvent): static
    {
        $this->idEvent = $idEvent;
        return $this;
    }
}