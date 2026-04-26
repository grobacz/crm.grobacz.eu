<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PricingListItemType extends ObjectType
{
    private static ?PricingListItemType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'PricingListItem',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingListItem) => $pricingListItem->getId(),
                    ],
                    'price' => [
                        'type' => Type::nonNull(Type::float()),
                        'resolve' => fn($pricingListItem) => (float) $pricingListItem->getPrice(),
                    ],
                    'compareAtPrice' => [
                        'type' => Type::float(),
                        'resolve' => fn($pricingListItem) => $pricingListItem->getCompareAtPrice() !== null
                            ? (float) $pricingListItem->getCompareAtPrice()
                            : null,
                    ],
                    'createdAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingListItem) => $pricingListItem->getCreatedAt()?->format('c'),
                    ],
                    'updatedAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingListItem) => $pricingListItem->getUpdatedAt()?->format('c'),
                    ],
                    'pricingList' => [
                        'type' => PricingListType::getInstance(),
                        'resolve' => fn($pricingListItem) => $pricingListItem->getPricingList(),
                    ],
                    'inventoryItem' => [
                        'type' => InventoryItemType::getInstance(),
                        'resolve' => fn($pricingListItem) => $pricingListItem->getInventoryItem(),
                    ],
                ];
            },
        ];

        parent::__construct($config);
    }

    public static function getInstance(): PricingListItemType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
