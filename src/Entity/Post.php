<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: "postes")] // Associer avec la table "postes" en base de données
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)] // Relation avec Utilisateur
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: 'text')]
    private string $contenu;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date_creation;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Commentaire::class, cascade: ['remove'])]
    private Collection $commentaires;

    public function __construct()
    {
        $this->date_creation = new \DateTime();
        $this->commentaires = new ArrayCollection();
    }

    // ✅ Ajoute les getters et setters nécessaires
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;
        return $this;
    }

    public function getContenu(): string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getDateCreation(): \DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }

    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }
}
