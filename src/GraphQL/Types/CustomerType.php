<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CustomerType extends ObjectType
{
    private static ?CustomerType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Customer',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($customer) {
                            return $customer->getId();
                        },
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($customer) {
                            return $customer->getName();
                        },
                    ],
                    'email' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($customer) {
                            return $customer->getEmail();
                        },
                    ],
                    'phone' => [
                        'type' => Type::string(),
                        'resolve' => function ($customer) {
                            return $customer->getPhone();
                        },
                    ],
                    'company' => [
                        'type' => Type::string(),
                        'resolve' => function ($customer) {
                            return $customer->getCompany();
                        },
                    ],
                    'status' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($customer) {
                            return $customer->getStatus();
                        },
                    ],
                    'isVip' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => function ($customer) {
                            return $customer->isVip();
                        },
                    ],
                    'createdAt' => [
                        'type' => Type::string(),
                        'resolve' => function ($customer) {
                            return $customer->getCreatedAt()->format('c');
                        },
                    ],
                    'updatedAt' => [
                        'type' => Type::string(),
                        'resolve' => function ($customer) {
                            return $customer->getUpdatedAt()->format('c');
                        },
                    ],
                    'contacts' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(function () {
                            return ContactType::getInstance();
                        }))),
                        'resolve' => function ($customer) {
                            return $customer->getContacts()->getValues();
                        },
                    ],
                    'deals' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(function () {
                            return DealType::getInstance();
                        }))),
                        'resolve' => function ($customer) {
                            return $customer->getDeals()->getValues();
                        },
                    ],
                ];
            }
        ];

        parent::__construct($config);
    }

    public static function getInstance(): CustomerType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
