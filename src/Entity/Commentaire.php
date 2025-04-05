<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "commentaires")]
class Commentaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'commentaires')]
    #[ORM\JoinColumn(nullable: false)]
    private Post $post;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Utilisateur $utilisateur;

    #[ORM\Column(type: 'text')]
    private string $contenu;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $date_creation;

    #[ORM\OneToMany(mappedBy: 'commentaire', targetEntity: SousCommentaire::class, cascade: ['remove'])]
    #[ORM\JoinColumn(name: "commentaire_id")] // Spécifie le nom de la colonne FK
    private Collection $sousCommentaires;

    public function __construct()
    {
        $this->date_creation = new \DateTime();
        $this->sousCommentaires = new ArrayCollection();
    }

    // ✅ Getters et Setters

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPost(): Post
    {
        return $this->post;
    }

    public function setPost(Post $post): self
    {
        $this->post = $post;
        return $this;
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

    public function getSousCommentaires(): Collection
    {
        return $this->sousCommentaires;
    }


   
}
