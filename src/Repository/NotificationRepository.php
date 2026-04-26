<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    public function findByUserId(string $userId, int $limit = 50): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countUnreadByUserId(string $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.userId = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function markAsRead(string $notificationId): bool
    {
        $notification = $this->find($notificationId);
        if (!$notification) {
            return false;
        }

        $notification->setIsRead(true);
        $this->getEntityManager()->flush();
        return true;
    }

    public function markAllAsRead(string $userId): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        return (int) $qb->update(Notification::class, 'n')
            ->set('n.isRead', true)
            ->andWhere('n.userId = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->execute();
    }

    public function createForAllUsers(string $activityId, array $activeUserIds): void
    {
        foreach ($activeUserIds as $userId) {
            $notification = new Notification();
            $notification->setUserId($userId);
            $notification->setActivityId($activityId);
            $this->getEntityManager()->persist($notification);
        }

        if (!empty($activeUserIds)) {
            $this->getEntityManager()->flush();
        }
    }
}
