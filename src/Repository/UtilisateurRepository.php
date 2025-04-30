<?php


// src/Repository/UtilisateurRepository.php

namespace App\Repository;

use App\Entity\Utilisateur;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Enums\Role;
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

    /*** azouz */
    public function findOneByEmail(string $email): ?Utilisateur
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Met à jour le mot de passe hashé
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Utilisateur) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setMotDePasse($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * Sauvegarde un utilisateur en base de données
     */
    public function save(Utilisateur $user, bool $flush = true): void
    {
        $this->getEntityManager()->persist($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un utilisateur
     */
    public function remove(Utilisateur $user, bool $flush = true): void
    {
        $this->getEntityManager()->remove($user);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
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


    /*filter*/
    public function findFilteredShops(?string $search, array $categories, ?int $rating): array
{
    $qb = $this->createQueryBuilder('u')
        ->leftJoin('u.categorie', 'c')
        ->where('u.role = :role')
        ->setParameter('role', Role::SHOPOWNER);

    if ($search) {
        $qb->andWhere('LOWER(u.nom) LIKE :search OR LOWER(u.prenom) LIKE :search')
            ->setParameter('search', '%' . strtolower($search) . '%');
    }

    if (!empty($categories)) {
        $qb->andWhere('c.nom IN (:categories)')
            ->setParameter('categories', $categories);
    }

    if ($rating) {
        $qb->leftJoin('u.receivedFeedbacks', 'f')
            ->groupBy('u.id')
            ->having('AVG(f.rating) >= :rating')
            ->setParameter('rating', $rating);
    }
    
    return $qb->getQuery()->getResult();
}


//partie maria

}
