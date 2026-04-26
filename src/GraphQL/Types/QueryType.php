<?php

namespace App\GraphQL\Types;

use App\GraphQL\Resolver\ActivityResolver;
use App\GraphQL\Resolver\CallLogResolver;
use App\GraphQL\Resolver\CategoryResolver;
use App\GraphQL\Resolver\ContactResolver;
use App\GraphQL\Resolver\CustomerResolver;
use App\GraphQL\Resolver\DealResolver;
use App\GraphQL\Resolver\EmailCampaignResolver;
use App\GraphQL\Resolver\InventoryResolver;
use App\GraphQL\Resolver\LeadResolver;
use App\GraphQL\Resolver\NotificationResolver;
use App\GraphQL\Resolver\PricingListResolver;
use App\GraphQL\Resolver\SearchResolver;
use App\GraphQL\Resolver\SettingResolver;
use App\GraphQL\Resolver\UserResolver;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class QueryType extends ObjectType
{
    public function __construct(
        ActivityResolver $activityResolver,
        CallLogResolver $callLogResolver,
        CategoryResolver $categoryResolver,
        CustomerResolver $customerResolver,
        ContactResolver $contactResolver,
        DealResolver $dealResolver,
        EmailCampaignResolver $emailCampaignResolver,
        InventoryResolver $inventoryResolver,
        LeadResolver $leadResolver,
        PricingListResolver $pricingListResolver,
        SettingResolver $settingResolver,
        UserResolver $userResolver,
        NotificationResolver $notificationResolver,
        SearchResolver $searchResolver
    ) {
        $config = [
            'name' => 'Query',
            'fields' => [
                'hello' => [
                    'type' => Type::string(),
                    'args' => [
                        'name' => ['type' => Type::string()]
                    ],
                    'resolve' => function ($root, $args) {
                        return "Hello " . ($args['name'] ?? 'World');
                    }
                ],
                'recentActivities' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(ActivityType::getInstance()))),
                    'args' => [
                        'limit' => ['type' => Type::int()]
                    ],
                    'resolve' => fn($root, $args) => $activityResolver->resolveRecentActivities($args),
                ],
                'callLogs' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(CallLogType::getInstance()))),
                    'args' => [
                        'limit' => ['type' => Type::int()]
                    ],
                    'resolve' => fn($root, $args) => $callLogResolver->resolveCallLogs($args),
                ],
                'activeCall' => [
                    'type' => CallLogType::getInstance(),
                    'resolve' => fn() => $callLogResolver->resolveActiveCall(),
                ],
                'emailCampaigns' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(EmailCampaignType::getInstance()))),
                    'args' => [
                        'limit' => ['type' => Type::int()]
                    ],
                    'resolve' => fn($root, $args) => $emailCampaignResolver->resolveEmailCampaigns($args),
                ],
                'emailCampaign' => [
                    'type' => EmailCampaignType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $emailCampaignResolver->resolveEmailCampaign($args),
                ],
                'customers' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(CustomerType::getInstance()))),
                    'resolve' => fn() => $customerResolver->resolveCustomers(),
                ],
                'customerCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $customerResolver->resolveCustomerCount(),
                ],
                'customer' => [
                    'type' => CustomerType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $customerResolver->resolveCustomer($args),
                ],
                'contacts' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(ContactType::getInstance()))),
                    'resolve' => fn() => $contactResolver->resolveContacts(),
                ],
                'contactCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $contactResolver->resolveContactCount(),
                ],
                'contact' => [
                    'type' => ContactType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $contactResolver->resolveContact($args),
                ],
                'deals' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(DealType::getInstance()))),
                    'resolve' => fn() => $dealResolver->resolveDeals(),
                ],
                'dealCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $dealResolver->resolveDealCount(),
                ],
                'deal' => [
                    'type' => DealType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $dealResolver->resolveDeal($args),
                ],
                'leads' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(LeadType::getInstance()))),
                    'resolve' => fn() => $leadResolver->resolveLeads(),
                ],
                'leadCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $leadResolver->resolveLeadCount(),
                ],
                'lead' => [
                    'type' => LeadType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $leadResolver->resolveLead($args),
                ],
                'categories' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(CategoryType::getInstance()))),
                    'resolve' => fn() => $categoryResolver->resolveCategories(),
                ],
                'categoryCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $categoryResolver->resolveCategoryCount(),
                ],
                'category' => [
                    'type' => CategoryType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $categoryResolver->resolveCategory($args),
                ],
                'inventoryItems' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(InventoryItemType::getInstance()))),
                    'resolve' => fn() => $inventoryResolver->resolveInventoryItems(),
                ],
                'inventoryItemCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $inventoryResolver->resolveInventoryItemCount(),
                ],
                'inventoryItem' => [
                    'type' => InventoryItemType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $inventoryResolver->resolveInventoryItem($args),
                ],
                'pricingLists' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(PricingListType::getInstance()))),
                    'resolve' => fn() => $pricingListResolver->resolvePricingLists(),
                ],
                'pricingListCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $pricingListResolver->resolvePricingListCount(),
                ],
                'pricingList' => [
                    'type' => PricingListType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $pricingListResolver->resolvePricingList($args),
                ],
                'settings' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(AppSettingType::getInstance()))),
                    'resolve' => fn() => $settingResolver->resolveSettings(),
                ],
                'setting' => [
                    'type' => AppSettingType::getInstance(),
                    'args' => [
                        'key' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $settingResolver->resolveSetting($args),
                ],
                'users' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(AppUserType::getInstance()))),
                    'resolve' => fn() => $userResolver->resolveUsers(),
                ],
                'activeUsers' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(AppUserType::getInstance()))),
                    'resolve' => fn() => $userResolver->resolveActiveUsers(),
                ],
                'user' => [
                    'type' => AppUserType::getInstance(),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $userResolver->resolveUser($args),
                ],
                'userCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => fn() => $userResolver->resolveUserCount(),
                ],
                'notifications' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(NotificationType::getInstance()))),
                    'args' => [
                        'userId' => ['type' => Type::nonNull(Type::string())],
                        'limit' => ['type' => Type::int()],
                    ],
                    'resolve' => fn($root, $args) => $notificationResolver->resolveNotifications($args),
                ],
                'unreadNotificationCount' => [
                    'type' => Type::nonNull(Type::int()),
                    'args' => [
                        'userId' => ['type' => Type::nonNull(Type::string())]
                    ],
                    'resolve' => fn($root, $args) => $notificationResolver->resolveUnreadNotificationCount($args),
                ],
                'search' => [
                    'type' => Type::nonNull(SearchResultsType::getInstance()),
                    'args' => [
                        'query' => ['type' => Type::nonNull(Type::string())],
                        'limit' => ['type' => Type::int()],
                    ],
                    'resolve' => fn($root, $args) => $searchResolver->resolveSearch($args),
                ],
            ]
        ];

        parent::__construct($config);
    }
}
