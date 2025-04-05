<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }



    public function findByLatestPosts(): array
{
    return $this->createQueryBuilder('p')
        ->orderBy('p.date_creation', 'DESC')
        ->getQuery()
        ->getResult();
}

public function findAllWithComments()
{
    return $this->createQueryBuilder('p')
        ->addSelect('c', 'u', 'sc', 'su')
        ->leftJoin('p.commentaires', 'c')
        ->leftJoin('c.utilisateur', 'u')
        ->leftJoin('c.sousCommentaires', 'sc')
        ->leftJoin('sc.utilisateur', 'su')
        ->orderBy('p.date_creation', 'DESC')
        ->addOrderBy('c.date_creation', 'ASC')
        ->addOrderBy('sc.date_creation', 'ASC')
        ->getQuery()
        ->getResult();
}

}
