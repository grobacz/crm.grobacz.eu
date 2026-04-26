<?php

namespace App\Repository;

use App\Entity\CallLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CallLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallLog::class);
    }

    public function findActiveCall(): ?CallLog
    {
        return $this->createQueryBuilder('callLog')
            ->andWhere('callLog.endedAt IS NULL')
            ->andWhere('callLog.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('callLog.startedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findRecent(int $limit = 25): array
    {
        return $this->createQueryBuilder('callLog')
            ->orderBy('callLog.startedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
