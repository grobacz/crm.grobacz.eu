<?php

namespace App\Repository;

use App\Entity\EmailCampaign;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EmailCampaignRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailCampaign::class);
    }

    public function findRecent(int $limit = 20): array
    {
        return $this->createQueryBuilder('campaign')
            ->orderBy('campaign.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findProcessable(int $limit = 10): array
    {
        return $this->createQueryBuilder('campaign')
            ->andWhere('campaign.status IN (:statuses)')
            ->setParameter('statuses', ['new', 'sending'])
            ->orderBy('campaign.createdAt', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
