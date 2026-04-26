<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class InventoryItemType extends ObjectType
{
    private static ?InventoryItemType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'InventoryItem',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getId(),
                    ],
                    'sku' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getSku(),
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getName(),
                    ],
                    'description' => [
                        'type' => Type::string(),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getDescription(),
                    ],
                    'unit' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getUnit(),
                    ],
                    'stockQuantity' => [
                        'type' => Type::nonNull(Type::int()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getStockQuantity(),
                    ],
                    'reservedQuantity' => [
                        'type' => Type::nonNull(Type::int()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getReservedQuantity(),
                    ],
                    'availableQuantity' => [
                        'type' => Type::nonNull(Type::int()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getAvailableQuantity(),
                    ],
                    'reorderLevel' => [
                        'type' => Type::nonNull(Type::int()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getReorderLevel(),
                    ],
                    'cost' => [
                        'type' => Type::float(),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getCost() !== null ? (float) $inventoryItem->getCost() : null,
                    ],
                    'isActive' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->isActive(),
                    ],
                    'createdAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getCreatedAt()?->format('c'),
                    ],
                    'updatedAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getUpdatedAt()?->format('c'),
                    ],
                    'category' => [
                        'type' => CategoryType::getInstance(),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getCategory(),
                    ],
                    'pricingListItems' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(PricingListItemType::getInstance()))),
                        'resolve' => fn($inventoryItem) => $inventoryItem->getPricingListItems()->getValues(),
                    ],
                ];
            },
        ];

        parent::__construct($config);
    }

    public static function getInstance(): InventoryItemType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
