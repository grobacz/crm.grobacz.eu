<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class SearchResultsType extends ObjectType
{
    private static ?SearchResultsType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'SearchResults',
            'fields' => [
                'customers' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(SearchResultItemType::getInstance()))),
                    'resolve' => fn($results) => $results['customers'],
                ],
                'leads' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(SearchResultItemType::getInstance()))),
                    'resolve' => fn($results) => $results['leads'],
                ],
                'deals' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(SearchResultItemType::getInstance()))),
                    'resolve' => fn($results) => $results['deals'],
                ],
                'inventoryItems' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(SearchResultItemType::getInstance()))),
                    'resolve' => fn($results) => $results['inventoryItems'],
                ],
                'totalResults' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($results) => $results['totalResults'],
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): SearchResultsType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
