<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class PricingListType extends ObjectType
{
    private static ?PricingListType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'PricingList',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingList) => $pricingList->getId(),
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingList) => $pricingList->getName(),
                    ],
                    'code' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingList) => $pricingList->getCode(),
                    ],
                    'currency' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingList) => $pricingList->getCurrency(),
                    ],
                    'description' => [
                        'type' => Type::string(),
                        'resolve' => fn($pricingList) => $pricingList->getDescription(),
                    ],
                    'isDefault' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => fn($pricingList) => $pricingList->isDefault(),
                    ],
                    'isActive' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => fn($pricingList) => $pricingList->isActive(),
                    ],
                    'createdAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingList) => $pricingList->getCreatedAt()?->format('c'),
                    ],
                    'updatedAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($pricingList) => $pricingList->getUpdatedAt()?->format('c'),
                    ],
                    'items' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(PricingListItemType::getInstance()))),
                        'resolve' => fn($pricingList) => $pricingList->getItems()->getValues(),
                    ],
                ];
            },
        ];

        parent::__construct($config);
    }

    public static function getInstance(): PricingListType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
