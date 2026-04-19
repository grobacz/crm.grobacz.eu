<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CustomerType extends ObjectType
{
    public function __construct()
    {
        $config = [
            'name' => 'Customer',
            'fields' => [
                'id' => Type::nonNull(Type::string()),
                'name' => Type::nonNull(Type::string()),
                'email' => Type::nonNull(Type::string()),
                'phone' => Type::string(),
                'company' => Type::string(),
            ]
        ];

        parent::__construct($config);
    }
}
