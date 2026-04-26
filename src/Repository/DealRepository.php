<?php

namespace App\Repository;

use App\Entity\Deal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deal::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query, int $limit = 5): array
    {
        $pattern = '%' . strtolower($query) . '%';

        return $this->createQueryBuilder('d')
            ->andWhere('LOWER(d.title) LIKE :query')
            ->setParameter('query', $pattern)
            ->orderBy('d.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCustomer(string $customerId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.customer = :customerId')
            ->setParameter('customerId', $customerId)
            ->orderBy('d.closeDate', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
