<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EmailCampaignRecipientType extends ObjectType
{
    private static ?EmailCampaignRecipientType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'EmailCampaignRecipient',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getId(),
                ],
                'recipientType' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getRecipientType(),
                ],
                'recipientId' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getRecipientId(),
                ],
                'recipientName' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getRecipientName(),
                ],
                'recipientEmail' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getRecipientEmail(),
                ],
                'status' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getStatus(),
                ],
                'createdAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getCreatedAt()->format('c'),
                ],
                'updatedAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($recipient) => $recipient->getUpdatedAt()->format('c'),
                ],
                'sentAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($recipient) => $recipient->getSentAt()?->format('c'),
                ],
                'openedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($recipient) => $recipient->getOpenedAt()?->format('c'),
                ],
                'completedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($recipient) => $recipient->getCompletedAt()?->format('c'),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): EmailCampaignRecipientType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
