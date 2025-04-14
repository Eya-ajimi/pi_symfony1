<?php

namespace App\Entity;


use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: "utilisateur")]
class Utilisateur implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "nom", type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(name: "prenom", type: "string", length: 255)]
    private string $prenom;

    #[ORM\Column(name: "email", type: "string", length: 255, unique: true)]
    private string $email;

    #[ORM\Column(name: "mot_de_passe", type: "string", length: 255)]
    private string $motDePasse;

    #[ORM\Column(name: "points", type: "integer")]
    private int $points = 0;

    #[ORM\Column(name: "nombre_de_gain", type: "integer")]
    private int $nombreDeGain = 0;

    #[ORM\Column(name: "adresse", type: "string", length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(name: "telephone", type: "string", length: 15, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: "date_inscription", type: "datetime")]
    private \DateTime $dateInscription;

    #[ORM\Column(name: "statut", type: "string", length: 50)]
    private string $statut;

    #[ORM\Column(name: "role", type: "string", columnDefinition: "ENUM('SHOPOWNER','ADMIN','CLIENT','UTILISATEUR')")]
    private string $role;

    #[ORM\Column(name: "id_categorie", type: "integer", nullable: true)]
    private ?int $idCategorie = null;

    #[ORM\Column(name: "reset_token", type: "integer", nullable: true)]
    private ?int $resetToken = null;

    #[ORM\Column(name: "description", type: "text", nullable: true)]
    private ?string $description = null;

    #[ORM\Column(name: "profilepicture", type: "blob", nullable: true)]
    private $profilePicture = null;

    #[ORM\Column(name: "balance", type: "float")]
    private float $balance = 0;

    #[ORM\Column(name: "numeroTicket", type: "integer", nullable: true)]
    private ?int $numeroTicket = null;


    /**
     * @var Collection<int, EventClient>
     */
    

    public function __construct()
    {
        $this->dateInscription = new \DateTime();
    }

    // Getters and Setters
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }
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

    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }

    public function getIdCategorie(): ?int { return $this->idCategorie; }
    public function setIdCategorie(?int $idCategorie): self { $this->idCategorie = $idCategorie; return $this; }

    public function getResetToken(): ?int { return $this->resetToken; }
    public function setResetToken(?int $resetToken): self { $this->resetToken = $resetToken; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getProfilePicture()
    {
        if (is_resource($this->profilePicture)) {
            return stream_get_contents($this->profilePicture);
        }
        return $this->profilePicture;
    }

    public function setProfilePicture($profilePicture): self { $this->profilePicture = $profilePicture; return $this; }

    public function getBalance(): float { return $this->balance; }
    public function setBalance(float $balance): self { $this->balance = $balance; return $this; }

    public function getNumeroTicket(): ?int { return $this->numeroTicket; }
    public function setNumeroTicket(?int $numeroTicket): self { $this->numeroTicket = $numeroTicket; return $this; }


    // UserInterface methods
    public function getRoles(): array { return [$this->role]; }
    public function eraseCredentials(): void {}
    public function getUserIdentifier(): string { return $this->email; }

    // Optional methods
    public function getPassword(): string { return $this->motDePasse; }
    public function getSalt(): ?string { return null; }
    public function getUsername(): string { return $this->email; }

    

    
}