<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AppUserType extends ObjectType
{
    private static ?AppUserType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'AppUser',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($user) => $user->getId(),
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($user) => $user->getName(),
                ],
                'email' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($user) => $user->getEmail(),
                ],
                'role' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($user) => $user->getRole(),
                ],
                'status' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($user) => $user->getStatus(),
                ],
                'avatarColor' => [
                    'type' => Type::string(),
                    'resolve' => fn($user) => $user->getAvatarColor(),
                ],
                'initials' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($user) => $user->getInitials(),
                ],
                'createdAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($user) => $user->getCreatedAt()?->format('c'),
                ],
                'updatedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($user) => $user->getUpdatedAt()?->format('c'),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): AppUserType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
