<?php

namespace App\GraphQL\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class EmailCampaignType extends ObjectType
{
    private static ?EmailCampaignType $instance = null;

    public function __construct()
    {
        $config = [
            'name' => 'EmailCampaign',
            'fields' => [
                'id' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getId(),
                ],
                'name' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getName(),
                ],
                'subject' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getSubject(),
                ],
                'content' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getContent(),
                ],
                'targetType' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getTargetType(),
                ],
                'targetSegment' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getTargetSegment(),
                ],
                'status' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getStatus(),
                ],
                'createdAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getCreatedAt()->format('c'),
                ],
                'updatedAt' => [
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => fn($campaign) => $campaign->getUpdatedAt()->format('c'),
                ],
                'startedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($campaign) => $campaign->getStartedAt()?->format('c'),
                ],
                'completedAt' => [
                    'type' => Type::string(),
                    'resolve' => fn($campaign) => $campaign->getCompletedAt()?->format('c'),
                ],
                'recipientCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($campaign) => $campaign->getRecipientCount(),
                ],
                'newCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($campaign) => $campaign->getRecipientStatusCounts()['new'],
                ],
                'sendingCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($campaign) => $campaign->getRecipientStatusCounts()['sending'],
                ],
                'openedCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($campaign) => $campaign->getRecipientStatusCounts()['opened'],
                ],
                'completedCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($campaign) => $campaign->getRecipientStatusCounts()['completed'],
                ],
                'progressPercent' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn($campaign) => $campaign->getProgressPercent(),
                ],
                'recipients' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(EmailCampaignRecipientType::getInstance()))),
                    'resolve' => fn($campaign) => $campaign->getRecipients()->getValues(),
                ],
            ],
        ];

        parent::__construct($config);
    }

    public static function getInstance(): EmailCampaignType
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
