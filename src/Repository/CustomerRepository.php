<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmailInsensitive(string $email): ?Customer
    {
        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function emailExistsForAnotherCustomer(string $email, ?string $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('LOWER(c.email) = LOWER(:email)')
            ->setParameter('email', $email);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('c.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function search(string $query, int $limit = 5): array
    {
        $pattern = '%' . strtolower($query) . '%';

        return $this->createQueryBuilder('c')
            ->andWhere('LOWER(c.name) LIKE :query OR LOWER(c.email) LIKE :query OR LOWER(c.company) LIKE :query')
            ->setParameter('query', $pattern)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCampaignSegment(string $segment): array
    {
        $queryBuilder = $this->createQueryBuilder('c')
            ->andWhere('c.email IS NOT NULL');

        if ($segment === 'vip') {
            $queryBuilder->andWhere('c.isVip = :isVip')->setParameter('isVip', true);
        } else {
            $queryBuilder->andWhere('c.status = :status')->setParameter('status', $segment);
        }

        return $queryBuilder
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
