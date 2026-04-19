<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class QueryType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Query',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'args' => [
                        'name' => ['type' => Type::string()]
                    ],
                    'resolve' => function ($root, $args) {
                        return "Hello " . ($args['name'] ?? 'World');
                    }
                ]
            ]
        ];

        parent::__construct($config);
    }
}
