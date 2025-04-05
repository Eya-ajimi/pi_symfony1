<?php

namespace App\Entity;
use App\Enum\RoleEnum;


use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class Utilisateur implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 255)]
    private string $prenom;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 255)]
    private string $motDePasse;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $points = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $nombreDeGain = 0;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(type: 'string', length: 15, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $dateInscription;

    #[ORM\Column(type: 'string', length: 50)]
    private string $statut;

    #[ORM\Column(type: 'string', enumType: RoleEnum::class)]
    private RoleEnum $role;

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    public function getMotDePasse(): string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }

    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): self { $this->points = $points; return $this; }

    public function getNombreDeGain(): int { return $this->nombreDeGain; }
    public function setNombreDeGain(int $nombreDeGain): self { $this->nombreDeGain = $nombreDeGain; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

    public function getDateInscription(): \DateTime { return $this->dateInscription; }
    public function setDateInscription(\DateTime $dateInscription): self { $this->dateInscription = $dateInscription; return $this; }

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

    public function getRole(): RoleEnum { return $this->role; }
    public function setRole(RoleEnum $role): self { $this->role = $role; return $this; }

    // Security methods
    public function getRoles(): array { return [$this->role->value]; }
    public function eraseCredentials(): void {}
    public function getUserIdentifier(): string { return $this->email; }
}
