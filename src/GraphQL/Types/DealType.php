<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class DealType extends ObjectType
{
    private static ?DealType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Deal',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($deal) {
                            return $deal->getId();
                        },
                    ],
                    'title' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => function ($deal) {
                            return $deal->getTitle();
                        },
                    ],
                    'value' => [
                        'type' => Type::float(),
                        'resolve' => function ($deal) {
                            $value = $deal->getValue();

                            return $value !== null ? (float) $value : null;
                        },
                    ],
                    'status' => [
                        'type' => Type::string(),
                        'resolve' => function ($deal) {
                            return $deal->getStatus();
                        },
                    ],
                    'closeDate' => [
                        'type' => Type::string(),
                        'resolve' => function ($deal) {
                            $closeDate = $deal->getCloseDate();
                            return $closeDate ? $closeDate->format('Y-m-d') : null;
                        },
                    ],
                    'createdAt' => [
                        'type' => Type::string(),
                        'resolve' => function ($deal) {
                            return $deal->getCreatedAt()?->format('c');
                        },
                    ],
                    'customer' => [
                        'type' => Type::nonNull(function () {
                            return CustomerType::getInstance();
                        }),
                        'resolve' => function ($deal) {
                            return $deal->getCustomer();
                        },
                    ],
                ];
            }
        ];

        parent::__construct($config);
    }

    public static function getInstance(): DealType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
