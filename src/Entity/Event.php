<?php
// src/Entity/Event.php
namespace App\Entity;


use App\Entity\Utilisateur;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: "event")]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: "idOrganisateur", referencedColumnName: "id")]
    private Utilisateur $organisateur;

    #[ORM\Column(name: "nomOrganisateur", type: "string", length: 50)]
    private string $nomOrganisateur;

    #[ORM\Column(type: "text")]
    private string $description;

    #[ORM\Column(name: "dateDebut", type: "string", length: 10)]
    private ?string $dateDebut = null;

    #[ORM\Column(name: "dateFin", type: "string", length: 10)]
    private ?string $dateFin = null;

    #[ORM\Column(type: "string", length: 50)]
    private string $emplacement;

    #[ORM\Column(name: "max_participants", type: "integer", nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\OneToMany(targetEntity: EventClient::class, mappedBy: 'idEvent')]
    private Collection $eventClients;

    #[ORM\OneToMany(targetEntity: EventLike::class, mappedBy: 'event')]
    private Collection $likes;

    public function __construct()
    {
        $this->eventClients = new ArrayCollection();
        $this->likes = new ArrayCollection();
    }

    public function getEventClients(): Collection
    {
        return $this->eventClients;
    }

    public function addEventClient(EventClient $eventClient): self
    {
        if (!$this->eventClients->contains($eventClient)) {
            $this->eventClients[] = $eventClient;
            $eventClient->setIdEvent($this);
        }

        return $this;
    }

    public function removeEventClient(EventClient $eventClient): self
    {
        if ($this->eventClients->removeElement($eventClient)) {
            // set the owning side to null (unless already changed)
            if ($eventClient->getIdEvent() === $this) {
                $eventClient->setIdEvent(null);
            }
        }

        return $this;
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganisateur(): Utilisateur
    {
        return $this->organisateur;
    }
    public function setOrganisateur(Utilisateur $organisateur): self
    {
        $this->organisateur = $organisateur;
        return $this;
    }

    public function getNomOrganisateur(): string
    {
        return $this->nomOrganisateur;
    }
    public function setNomOrganisateur(string $nomOrganisateur): self
    {
        $this->nomOrganisateur = $nomOrganisateur;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }



    public function getDateDebut(): ?DateTimeImmutable
    {
        if (empty($this->dateDebut)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $this->dateDebut);
        return $date === false ? null : $date;
    }
    public function setDateDebut(DateTimeImmutable|string|null $date): self
    {
        if ($date instanceof DateTimeImmutable) {
            $this->dateDebut = $date->format('Y-m-d');
        } elseif (is_string($date) && !empty($date)) {
            $this->dateDebut = $date;
        } else {
            $this->dateDebut = null;
        }
        return $this;
    }

    public function getDateFin(): ?DateTimeImmutable
    {
        if (empty($this->dateFin)) {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $this->dateFin);
        return $date === false ? null : $date;
    }
    public function setDateFin(DateTimeImmutable|string|null $date): self
    {
        if ($date instanceof DateTimeImmutable) {
            $this->dateFin = $date->format('Y-m-d');
        } elseif (is_string($date) && !empty($date)) {
            $this->dateFin = $date;
        } else {
            $this->dateFin = null;
        }
        return $this;
    }

    public function getDateDebutString(): ?string
    {
        return $this->dateDebut;
    }

    public function getDateFinString(): ?string
    {
        return $this->dateFin;
    }
    public function getEmplacement(): string
    {
        return $this->emplacement;
    }
    public function setEmplacement(string $emplacement): self
    {
        $this->emplacement = $emplacement;
        return $this;
    }
    public function getMaxParticipants(): ?int
    {
        return $this->maxParticipants;
    }

    public function setMaxParticipants(?int $maxParticipants): self
    {
        $this->maxParticipants = $maxParticipants;
        return $this;
    }
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(EventLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes[] = $like;
            $like->setEvent($this);
        }
        return $this;
    }

    public function removeLike(EventLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getEvent() === $this) {
                $like->setEvent(null);
            }
        }
        return $this;
    }

    public function isLikedByUser(Utilisateur $user): bool
    {
        return $this->likes->exists(function($key, $like) use ($user) {
            return $like->getUser()->getId() === $user->getId();
        });
    }

    public function getLikeCount(): int
    {
        return $this->likes->count();
    }
}