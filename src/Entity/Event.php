<?php
namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name:"idOrganisateur",nullable: false)]
    private ?Utilisateur $organisateur = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    #[ORM\Column(name:"dateDebut",length: 255)]
    private ?string $dateDebut = null;

    #[ORM\Column(name:"dateFin",length: 255)]
    private ?string $dateFin = null;

    #[ORM\Column(length: 255)]
    private ?string $emplacement = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventClient::class)]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    // Getters and setters...
    public function getId(): ?int { return $this->id; }
    public function getOrganisateur(): ?Utilisateur { return $this->organisateur; }
    public function setOrganisateur(?Utilisateur $organisateur): self { $this->organisateur = $organisateur; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): self { $this->description = $description; return $this; }
    public function getDateDebut(): ?string { return $this->dateDebut; }
    public function setDateDebut(string $dateDebut): self { $this->dateDebut = $dateDebut; return $this; }
    public function getDateFin(): ?string { return $this->dateFin; }
    public function setDateFin(string $dateFin): self { $this->dateFin = $dateFin; return $this; }
    public function getEmplacement(): ?string { return $this->emplacement; }
    public function setEmplacement(string $emplacement): self { $this->emplacement = $emplacement; return $this; }

    /**
     * @return Collection<int, EventClient>
     */
    public function getParticipations(): Collection { return $this->participations; }
    public function addParticipation(EventClient $participation): self {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setEvent($this);
        }
        return $this;
    }
    public function removeParticipation(EventClient $participation): self {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getEvent() === $this) {
                $participation->setEvent(null);
            }
        }
        return $this;
    }
}