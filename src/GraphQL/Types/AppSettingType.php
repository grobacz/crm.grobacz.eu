<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class AppSettingType extends ObjectType
{
    private static ?AppSettingType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'AppSetting',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($setting) => $setting->getId(),
                ],
                'settingKey' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($setting) => $setting->getSettingKey(),
                ],
                'settingValue' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($setting) => $setting->getSettingValue(),
                ],
                'description' => [
                    'type' => Type::string(),
                    'resolve' => fn($setting) => $setting->getDescription(),
                ],
                'createdAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($setting) => $setting->getCreatedAt()?->format('c'),
                ],
                'updatedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($setting) => $setting->getUpdatedAt()?->format('c'),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): AppSettingType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
