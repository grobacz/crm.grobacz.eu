<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class SearchResultItemType extends ObjectType
{
    private static ?SearchResultItemType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'SearchResultItem',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($item) => $item['id'],
                ],
                'type' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($item) => $item['type'],
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($item) => $item['name'],
                ],
                'email' => [
                    'type' => Type::string(),
                    'resolve' => fn($item) => $item['email'] ?? null,
                ],
                'subtitle' => [
                    'type' => Type::string(),
                    'resolve' => fn($item) => $item['subtitle'] ?? null,
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): SearchResultItemType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
