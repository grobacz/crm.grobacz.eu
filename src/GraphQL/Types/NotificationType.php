<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class NotificationType extends ObjectType
{
    private static ?NotificationType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Notification',
            'fields' => function () {
                return [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($notification) => $notification->getId(),
                    ],
                    'userId' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($notification) => $notification->getUserId(),
                    ],
                    'activityId' => [
                        'type' => Type::string(),
                        'resolve' => fn($notification) => $notification->getActivityId(),
                    ],
                    'isRead' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => fn($notification) => $notification->isRead(),
                    ],
                    'createdAt' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => fn($notification) => $notification->getCreatedAt()->format('c'),
                    ],
                    'activity' => [
                        'type' => ActivityType::getInstance(),
                        'resolve' => fn($notification) => $notification->getActivity(),
                    ],
                ];
            },
        ];

        parent::__construct($config);
    }

    public static function getInstance(): NotificationType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
