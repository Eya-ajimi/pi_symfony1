<?php

namespace App\Entity;

use App\Enums\Role;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: UtilisateurRepository::class)]
#[ORM\Table(name: "utilisateur")]
#[ORM\UniqueConstraint(name: 'UNIQ_EMAIL', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "nom", type: "string", length: 255)]
    private string $nom;

    #[ORM\Column(name: "prenom", type: "string", length: 255)]
    private string $prenom;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
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

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $dateInscription;

    #[ORM\Column(name: "statut", type: "string", length: 50)]
    private string $statut='actif';


    #[ORM\Column(type: 'string', length: 20, enumType: Role::class, nullable: false)]
    private Role $role;


    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'utilisateurs')]
    #[ORM\JoinColumn(name: 'id_categorie', referencedColumnName: 'id_categorie', nullable: true)]
    private ?Categorie $categorie = null;


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

    


    #[ORM\OneToMany(mappedBy: 'utilisateur', targetEntity: LikedProduct::class, orphanRemoval: true)]
private Collection $likes;

public function getLikes(): Collection
{
    return $this->likes;
}



    /**
     * @var Collection<int, Produit>
     */
    #[ORM\OneToMany(targetEntity: Produit::class, mappedBy: 'shopId', orphanRemoval: true)]
    private Collection $produits;


  
    public function __construct()
    {
        $this->dateInscription = new \DateTimeImmutable();
        $this->role = Role::CLIENT;
        $this->produits = new ArrayCollection();
        $this->discounts = new ArrayCollection();
        $this->commandes = new ArrayCollection();
        $this->givenFeedbacks = new ArrayCollection();
        $this->receivedFeedbacks = new ArrayCollection();
    }



    /**
 * @return Collection<int, Feedback>
 */
public function getGivenFeedbacks(): Collection
{
    return $this->givenFeedbacks;
}

public function addGivenFeedback(Feedback $feedback): self
{
    if (!$this->givenFeedbacks->contains($feedback)) {
        $this->givenFeedbacks->add($feedback);
        $feedback->setUser($this);
    }
    return $this;
}

/**
 * @return Collection<int, Feedback>
 */
public function getReceivedFeedbacks(): Collection
{
    return $this->receivedFeedbacks;
}

public function addReceivedFeedback(Feedback $feedback): self
{
    if (!$this->receivedFeedbacks->contains($feedback)) {
        $this->receivedFeedbacks->add($feedback);
        $feedback->setShop($this);
    }
    return $this;
}


/**
     * @return Collection<int, Produit>
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(Produit $produit): static
    {
        if (!$this->produits->contains($produit)) {
            $this->produits->add($produit);
            $produit->setShopId($this);
        }

        return $this;
    }

    public function removeProduit(Produit $produit): static
    {
        if ($this->produits->removeElement($produit)) {
            // set the owning side to null (unless already changed)
            if ($produit->getShopId() === $this) {
                $produit->setShopId(null);
            }
        }

        return $this;
    }

//----------------------------------------------------------

/**
     * @var Collection<int, Discount>
     */
    #[ORM\OneToMany(targetEntity: Discount::class, mappedBy: 'shop_id')]
    private Collection $discounts;

    /**
     * @var Collection<int, Commande>
     */
    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'idClient')]
    private Collection $commandes;



    /**feedback */

    #[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'user')]
private Collection $givenFeedbacks;

#[ORM\OneToMany(targetEntity: Feedback::class, mappedBy: 'shop')]
private Collection $receivedFeedbacks;
		//---------------------------------

 /**
     * @return Collection<int, Discount>
     */
    public function getDiscounts(): Collection
    {
        return $this->discounts;
    }

    public function addDiscount(Discount $discount): static
    {
        if (!$this->discounts->contains($discount)) {
            $this->discounts->add($discount);
            $discount->setShopId($this);
        }

        return $this;
    }

    public function removeDiscount(Discount $discount): static
    {
        if ($this->discounts->removeElement($discount)) {
            // set the owning side to null (unless already changed)
            if ($discount->getShopId() === $this) {
                $discount->setShopId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): static
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setIdClient($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): static
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getIdClient() === $this) {
                $commande->setIdClient(null);
            }
        }

        return $this;
    }

    /*   les fonctions de aziz*/
    
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
    public function getId(): ?int { return $this->id; }
    public function setId(int $id): self { $this->id = $id; return $this; }

    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    


    public function getMotDePasse(): string { return $this->motDePasse; }
    public function setPassword(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }

    public function getPoints(): int { return $this->points; }
    public function setPoints(int $points): self { $this->points = $points; return $this; }

    public function getNombreDeGain(): int { return $this->nombreDeGain; }
    public function setNombreDeGain(int $nombreDeGain): self { $this->nombreDeGain = $nombreDeGain; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): self { $this->adresse = $adresse; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }

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

    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }

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
    
    public function isActive(): bool
    {
        return $this->statut === 'actif';
    }


    // Optional methods
    public function getPassword(): string { return $this->motDePasse; }
    public function getSalt(): ?string { return null; }

    public function getFullName(): string
    {
        return $this->prenom.' '.$this->nom;
    }

    /******** */
   

}