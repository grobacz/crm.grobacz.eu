<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CallLogType extends ObjectType
{
    private static ?CallLogType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'CallLog',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getId(),
                ],
                'targetType' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getTargetType(),
                ],
                'targetId' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getTargetId(),
                ],
                'targetName' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getTargetName(),
                ],
                'targetPhone' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getTargetPhone(),
                ],
                'status' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getStatus(),
                ],
                'startedAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($callLog) => $callLog->getStartedAt()->format('c'),
                ],
                'endedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($callLog) => $callLog->getEndedAt()?->format('c'),
                ],
                'durationSeconds' => [
                    'type' => Type::int(),
                    'resolve' => fn($callLog) => $callLog->getDurationSeconds(),
                ],
                'isActive' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'resolve' => fn($callLog) => $callLog->isActive(),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): CallLogType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
