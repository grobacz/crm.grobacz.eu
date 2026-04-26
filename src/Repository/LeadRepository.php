<?php

namespace App\Repository;

use App\Entity\Lead;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class LeadRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Lead::class);
    }

    public function findAll(): array
    {
        return $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByEmailInsensitive(string $email): ?Lead
    {
        return $this->createQueryBuilder('l')
            ->andWhere('LOWER(l.email) = LOWER(:email)')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function emailExistsForAnotherLead(string $email, ?string $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->andWhere('LOWER(l.email) = LOWER(:email)')
            ->setParameter('email', $email);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('l.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function search(string $query, int $limit = 5): array
    {
        $pattern = '%' . strtolower($query) . '%';

        return $this->createQueryBuilder('l')
            ->andWhere('LOWER(l.name) LIKE :query OR LOWER(l.email) LIKE :query OR LOWER(l.company) LIKE :query')
            ->setParameter('query', $pattern)
            ->orderBy('l.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByCampaignSegment(string $segment): array
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.status = :status')
            ->andWhere('l.email IS NOT NULL')
            ->setParameter('status', $segment)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
