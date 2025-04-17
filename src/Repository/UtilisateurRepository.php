<?php


// src/Repository/UtilisateurRepository.php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function findAllShopOwners()
    {
        return $this->createQueryBuilder('u')
            ->where('u.role = :role')
            ->setParameter('role', 'SHOPOWNER')
            ->leftJoin('u.categorie', 'c')
            ->addSelect('c')
            ->getQuery()
            ->getResult();
    }
    public function findutilisateurbyid(int $id): ?Utilisateur
    {
        return $this->createQueryBuilder('u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
    /**
     * Trouve les utilisateurs par rôle
     */
    public function findByRole(string $role): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', $role)
            ->getQuery()
            ->getResult();
    }
    // Alternative version that returns the full Categorie object
    public function getCategorieByUserId(int $userId): ?Categorie
    {
        $user = $this->createQueryBuilder('u')
            ->select('u', 'c')
            ->leftJoin('u.categorie', 'c')
            ->where('u.id = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();

        return $user ? $user->getCategorie() : null;
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
}
