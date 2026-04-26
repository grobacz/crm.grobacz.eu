<?php

namespace App\Repository;

use App\Entity\InventoryItem;
use App\Entity\PricingList;
use App\Entity\PricingListItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PricingListItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PricingListItem::class);
    }

    public function findOneByPricingListAndInventoryItem(PricingList $pricingList, InventoryItem $inventoryItem): ?PricingListItem
    {
        return $this->createQueryBuilder('pricingListItem')
            ->andWhere('pricingListItem.pricingList = :pricingList')
            ->andWhere('pricingListItem.inventoryItem = :inventoryItem')
            ->setParameter('pricingList', $pricingList)
            ->setParameter('inventoryItem', $inventoryItem)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
