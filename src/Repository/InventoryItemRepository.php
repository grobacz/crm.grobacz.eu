<?php

namespace App\Repository;

use App\Entity\InventoryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class InventoryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InventoryItem::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('inventoryItem')
            ->leftJoin('inventoryItem.category', 'category')->addSelect('category')
            ->orderBy('inventoryItem.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query, int $limit = 5): array
    {
        $pattern = '%' . strtolower($query) . '%';

        return $this->createQueryBuilder('inventoryItem')
            ->andWhere('LOWER(inventoryItem.name) LIKE :query OR LOWER(inventoryItem.sku) LIKE :query')
            ->setParameter('query', $pattern)
            ->orderBy('inventoryItem.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySkuInsensitive(string $sku): ?InventoryItem
    {
        return $this->createQueryBuilder('inventoryItem')
            ->andWhere('LOWER(inventoryItem.sku) = LOWER(:sku)')
            ->setParameter('sku', $sku)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function skuExistsForAnotherInventoryItem(string $sku, ?string $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('inventoryItem')
            ->select('COUNT(inventoryItem.id)')
            ->andWhere('LOWER(inventoryItem.sku) = LOWER(:sku)')
            ->setParameter('sku', $sku);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('inventoryItem.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
