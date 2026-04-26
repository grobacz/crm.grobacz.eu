<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CategoryType extends ObjectType
{
    private static ?CategoryType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Category',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($category) => $category->getId(),
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($category) => $category->getName(),
                    ],
                    'slug' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($category) => $category->getSlug(),
                    ],
                    'description' => [
                        'type' => Type::string(),
                        'resolve' => fn($category) => $category->getDescription(),
                    ],
                    'sortOrder' => [
                        'type' => Type::nonNull(Type::int()),
                        'resolve' => fn($category) => $category->getSortOrder(),
                    ],
                    'createdAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($category) => $category->getCreatedAt()?->format('c'),
                    ],
                    'updatedAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($category) => $category->getUpdatedAt()?->format('c'),
                    ],
                    'parent' => [
                        'type' => self::getInstance(),
                        'resolve' => fn($category) => $category->getParent(),
                    ],
                    'children' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(self::getInstance()))),
                        'resolve' => fn($category) => $category->getChildren()->getValues(),
                    ],
                    'inventoryItems' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(InventoryItemType::getInstance()))),
                        'resolve' => fn($category) => $category->getInventoryItems()->getValues(),
                    ],
                ];
            },
        ];

        parent::__construct($config);
    }

    public static function getInstance(): CategoryType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
