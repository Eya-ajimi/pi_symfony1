<?php

namespace App\Entity;

use App\Enum\Role;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: 'utilisateur')]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $motDePasse;

    #[ORM\Column(type: 'string', length: 100)]
    private string $nom;

    #[ORM\Column(type: 'string', length: 100)]
    private string $prenom;

    #[ORM\Column(type: 'string', length: 255)]
    private string $adresse;

    #[ORM\Column(type: 'string', length: 20)]
    private string $telephone;

    #[ORM\Column(type: 'integer')]
    private int $points = 0;

    #[ORM\Column(type: 'integer', name: 'nombre_de_gain')]
    private int $nombreDeGain = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateInscription;

    #[ORM\Column(type: 'string', length: 50)]
    private string $statut = 'actif';

  
    #[ORM\Column(type: 'string', length: 20, enumType: Role::class, nullable: false)]
    private Role $role;

    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: true)]
    private ?Categorie $categorie = null;

   

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: 'profilepicture', type: 'blob', nullable: true)]
    private $profilePicture = null;

    #[ORM\Column(type: 'float')]
    private float $balance = 0.0;

    #[ORM\Column(name: 'numeroTicket', type: 'integer', nullable: true)]
    private ?int $numeroTicket = null;

    public function __construct()
    {
        $this->dateInscription = new \DateTimeImmutable();
        $this->role = Role::CLIENT;
    }
//reset password//
// src/Entity/Utilisateur.php
#[ORM\Column(name: "reset_token", type: "string", length: 100, nullable: true)]
private ?string $resetToken = null;

#[ORM\Column(name: "reset_token_expires_at", type: "datetime_immutable", nullable: true)]
private ?\DateTimeImmutable $resetTokenExpiresAt = null;

// Ajoutez ces méthodes
public function getResetToken(): ?string
{
    return $this->resetToken;
}

public function setResetToken(?string $resetToken): self
{
    $this->resetToken = $resetToken;
    return $this;
}

public function getResetTokenExpiresAt(): ?\DateTimeImmutable
{
    return $this->resetTokenExpiresAt;
}

public function setResetTokenExpiresAt(?\DateTimeImmutable $resetTokenExpiresAt): self
{
    $this->resetTokenExpiresAt = $resetTokenExpiresAt;
    return $this;
}

public function isResetTokenValid(): bool
{
    if (!$this->resetToken || !$this->resetTokenExpiresAt) {
        return false;
    }
    return new \DateTimeImmutable() < $this->resetTokenExpiresAt;
}
//fin reset password//
    #[ORM\PrePersist]
    public function setDefaultValues(): void
    {
        if (!isset($this->dateInscription)) {
            $this->dateInscription = new \DateTimeImmutable();
        }
        if (!isset($this->role)) {
            $this->role = Role::CLIENT;
        }
        if (!isset($this->statut)) {
            $this->statut = 'actif';
        }
    }

    public function getRoles(): array
    {
        // Ensure we always have a valid role
        if (!isset($this->role)) {
            $this->role = Role::CLIENT;
        }
        
        // Return the role in the format that Symfony expects
        return ['ROLE_' . $this->role->value];
    }

    public function __toString(): string
    {
        return sprintf(
            '%s [%s]', 
            $this->email, 
            $this->role->value
        );
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    // Getters and Setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->motDePasse;
    }

    public function setPassword(string $password): self
    {
        $this->motDePasse = $password;
        return $this;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getAdresse(): string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getTelephone(): string
    {
        return $this->telephone;
    }

    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;
        return $this;
    }

    public function getNombreDeGain(): int
    {
        return $this->nombreDeGain;
    }

    public function setNombreDeGain(int $nombreDeGain): self
    {
        $this->nombreDeGain = $nombreDeGain;
        return $this;
    }

    public function getDateInscription(): \DateTimeImmutable
    {
        return $this->dateInscription;
    }

    public function setDateInscription(\DateTimeInterface $dateInscription): self
    {
        if ($dateInscription instanceof \DateTime) {
            $this->dateInscription = \DateTimeImmutable::createFromMutable($dateInscription);
        } else {
            $this->dateInscription = $dateInscription;
        }
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getRole(): Role
    {
        return $this->role;
    }

    public function setRole(Role $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): self
    {
        $this->categorie = $categorie;
        return $this;
    }

   
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getProfilePicture()
    {
        return $this->profilePicture;
    }

    public function setProfilePicture($profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getBalance(): float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;
        return $this;
    }

    public function getNumeroTicket(): ?int
    {
        return $this->numeroTicket;
    }

    public function setNumeroTicket(?int $numeroTicket): self
    {
        $this->numeroTicket = $numeroTicket;
        return $this;
    }

    public function getFullName(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }
}