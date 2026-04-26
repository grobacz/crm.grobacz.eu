<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllOrdered(): array
    {
        return $this->createQueryBuilder('category')
            ->leftJoin('category.parent', 'parent')->addSelect('parent')
            ->orderBy('category.sortOrder', 'ASC')
            ->addOrderBy('category.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlugInsensitive(string $slug): ?Category
    {
        return $this->createQueryBuilder('category')
            ->andWhere('LOWER(category.slug) = LOWER(:slug)')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function slugExistsForAnotherCategory(string $slug, ?string $excludedId = null): bool
    {
        $queryBuilder = $this->createQueryBuilder('category')
            ->select('COUNT(category.id)')
            ->andWhere('LOWER(category.slug) = LOWER(:slug)')
            ->setParameter('slug', $slug);

        if ($excludedId !== null) {
            $queryBuilder
                ->andWhere('category.id != :excludedId')
                ->setParameter('excludedId', $excludedId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }
}
