<?php
// src/Entity/Event.php
namespace App\Entity;

use App\Entity\Utilisateur;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

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
    private string $dateDebut;

    #[ORM\Column(name: "dateFin", type: "string", length: 10)]
    private string $dateFin;

    #[ORM\Column(type: "string", length: 50)]
    private string $emplacement;

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    
    public function getOrganisateur(): Utilisateur { return $this->organisateur; }
    public function setOrganisateur(Utilisateur $organisateur): self { 
        $this->organisateur = $organisateur; 
        return $this; 
    }

    public function getNomOrganisateur(): string { return $this->nomOrganisateur; }
    public function setNomOrganisateur(string $nomOrganisateur): self { 
        $this->nomOrganisateur = $nomOrganisateur; 
        return $this; 
    }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): self { 
        $this->description = $description; 
        return $this; 
    }

    public function getDateDebut(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('Y-m-d', $this->dateDebut);
    }

    public function setDateDebut(DateTimeImmutable $date): self
    {
        $this->dateDebut = $date->format('Y-m-d');
        return $this;
    }

    public function getDateFin(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('Y-m-d', $this->dateFin);
    }

    public function setDateFin(DateTimeImmutable $date): self
    {
        $this->dateFin = $date->format('Y-m-d');
        return $this;
    }
    public function getDateDebutString(): string
    {
        return $this->dateDebut;
    }

    public function getDateFinString(): string
    {
        return $this->dateFin;
    }
    public function getEmplacement(): string { return $this->emplacement; }
    public function setEmplacement(string $emplacement): self { 
        $this->emplacement = $emplacement; 
        return $this; 
    }
}