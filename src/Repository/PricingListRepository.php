<?php

namespace App\Repository;

use App\Entity\PricingList;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PricingListRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PricingList::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('pricingList')
            ->orderBy('pricingList.isDefault', 'DESC')
            ->addOrderBy('pricingList.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByCodeInsensitive(string $code): ?PricingList
    {
        return $this->createQueryBuilder('pricingList')
            ->andWhere('LOWER(pricingList.code) = LOWER(:code)')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function codeExistsForAnotherPricingList(string $code, ?string $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('pricingList')
            ->select('COUNT(pricingList.id)')
            ->andWhere('LOWER(pricingList.code) = LOWER(:code)')
            ->setParameter('code', $code);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('pricingList.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    public function clearDefaultFlagExcept(?string $excludedId = null): void
    {
        $queryBuilder = $this->createQueryBuilder('pricingList')
            ->update()
            ->set('pricingList.isDefault', ':isDefault')
            ->setParameter('isDefault', false)
            ->andWhere('pricingList.isDefault = :currentlyDefault')
            ->setParameter('currentlyDefault', true);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('pricingList.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        $queryBuilder->getQuery()->execute();
    }
}
