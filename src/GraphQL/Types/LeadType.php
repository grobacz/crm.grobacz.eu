<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class LeadType extends ObjectType
{
    private static ?LeadType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'Lead',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($lead) => $lead->getId(),
                ],
                'name' => [
                    'type' => Type::string(),
                    'resolve' => fn($lead) => $lead->getName(),
                ],
                'email' => [
                    'type' => Type::string(),
                    'resolve' => fn($lead) => $lead->getEmail(),
                ],
                'phone' => [
                    'type' => Type::string(),
                    'resolve' => fn($lead) => $lead->getPhone(),
                ],
                'company' => [
                    'type' => Type::string(),
                    'resolve' => fn($lead) => $lead->getCompany(),
                ],
                'status' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($lead) => $lead->getStatus(),
                ],
                'createdAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($lead) => $lead->getCreatedAt()?->format('c'),
                ],
                'updatedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($lead) => $lead->getUpdatedAt()?->format('c'),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): LeadType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
