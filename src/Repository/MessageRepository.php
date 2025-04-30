<?php
namespace App\Repository;
use App\Entity\Message;
use App\Entity\Utilisateur;  // This is the critical fix
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Proxy\Proxy;


class MessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function findConversationBetweenUsers($user1, $user2)
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    // src/Repository/MessageRepository.php

    public function findAdminConversations()
    {
        return $this->createQueryBuilder('m')
            ->where('m.recipient IS NULL AND m.isToAllAdmins = true')
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // src/Repository/MessageRepository.php

    public function findShopOwnerConversations($shopOwner)
    {
        // Handle both ID and object input
        if (is_numeric($shopOwner)) {
            $shopOwner = $this->getEntityManager()
                ->getRepository(Utilisateur::class)
                ->find($shopOwner);

            if (!$shopOwner) {
                throw new \InvalidArgumentException('Shop owner not found');
            }
        }

        // Accept both Utilisateur and Doctrine proxy instances
        if (!$shopOwner instanceof Utilisateur && !$shopOwner instanceof Proxy) {
            throw new \InvalidArgumentException(sprintf(
                'Expected Utilisateur instance or ID, got %s',
                is_object($shopOwner) ? get_class($shopOwner) : gettype($shopOwner)
            ));
        }

        return $this->createQueryBuilder('m')
            ->where('m.sender = :shopOwner OR (m.recipient IS NULL AND m.isToAllAdmins = true)')
            ->orWhere('m.recipient = :shopOwner')
            ->setParameter('shopOwner', $shopOwner)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // src/Repository/MessageRepository.php

    public function findMessagesToAllAdmins(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isToAllAdmins = :true')
            ->setParameter('true', true)
            ->orderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findConversationsBetween(Utilisateur $user1, Utilisateur $user2): array
    {
        return $this->createQueryBuilder('m')
            ->where('(m.sender = :user1 AND m.recipient = :user2) OR (m.sender = :user2 AND m.recipient = :user1)')
            ->setParameter('user1', $user1)
            ->setParameter('user2', $user2)
            ->orderBy('m.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    public function findMessagesVisibleToAdmins(Utilisateur $admin = null): array
{
    $qb = $this->createQueryBuilder('m')
        ->where('m.isToAllAdmins = true OR m.recipient = :admin')
        ->setParameter('admin', $admin)
        ->orderBy('m.createdAt', 'DESC');

    return $qb->getQuery()->getResult();
}


}