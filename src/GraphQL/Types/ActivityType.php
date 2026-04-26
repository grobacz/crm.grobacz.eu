<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class ActivityType extends ObjectType
{
    private static ?ActivityType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Activity',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function ($activity) {
                        return $activity->getId();
                    },
                ],
                'entityType' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function ($activity) {
                        return $activity->getEntityType();
                    },
                ],
                'action' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function ($activity) {
                        return $activity->getAction();
                    },
                ],
                'entityId' => [
                    'type' => Type::string(),
                    'resolve' => function ($activity) {
                        return $activity->getEntityId();
                    },
                ],
                'message' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function ($activity) {
                        return $activity->getMessage();
                    },
                ],
                'createdAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function ($activity) {
                        return $activity->getCreatedAt()->format('c');
                    },
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): ActivityType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
