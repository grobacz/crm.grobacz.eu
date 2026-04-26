<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ContactType extends ObjectType
{
    private static ?ContactType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Contact',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($contact) {
                            return $contact->getId();
                        },
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($contact) {
                            return $contact->getName();
                        },
                    ],
                    'email' => [
                        'type' => Type::string(),
                        'resolve' => function ($contact) {
                            return $contact->getEmail();
                        },
                    ],
                    'phone' => [
                        'type' => Type::string(),
                        'resolve' => function ($contact) {
                            return $contact->getPhone();
                        },
                    ],
                    'title' => [
                        'type' => Type::string(),
                        'resolve' => function ($contact) {
                            return $contact->getTitle();
                        },
                    ],
                    'isPrimary' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => function ($contact) {
                            return (bool) $contact->isPrimary();
                        },
                    ],
                    'customer' => [
                        'type' => Type::nonNull(function () {
                            return CustomerType::getInstance();
                        }),
                        'resolve' => function ($contact) {
                            return $contact->getCustomer();
                        },
                    ],
                ];
            }
        ];

        parent::__construct($config);
    }

    public static function getInstance(): ContactType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
