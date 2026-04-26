<?php

namespace App\GraphQL\Types;

use App\GraphQL\Resolver\CategoryResolver;
use App\GraphQL\Resolver\CallLogResolver;
use App\GraphQL\Resolver\ContactResolver;
use App\GraphQL\Resolver\CustomerResolver;
use App\GraphQL\Resolver\DealResolver;
use App\GraphQL\Resolver\EmailCampaignResolver;
use App\GraphQL\Resolver\InventoryResolver;
use App\GraphQL\Resolver\LeadResolver;
use App\GraphQL\Resolver\NotificationResolver;
use App\GraphQL\Resolver\PricingListResolver;
use App\GraphQL\Resolver\SettingResolver;
use App\GraphQL\Resolver\UserResolver;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class MutationType extends ObjectType
{
    public function __construct(
        CategoryResolver $categoryResolver,
        CallLogResolver $callLogResolver,
        CustomerResolver $customerResolver,
        ContactResolver $contactResolver,
        DealResolver $dealResolver,
        EmailCampaignResolver $emailCampaignResolver,
        InventoryResolver $inventoryResolver,
        LeadResolver $leadResolver,
        PricingListResolver $pricingListResolver,
        SettingResolver $settingResolver,
        UserResolver $userResolver,
        NotificationResolver $notificationResolver
    ) {
        $settingInputType = new InputObjectType([
            'name' => 'SettingInput',
            'fields' => [
                'key' => ['type' => Type::nonNull(Type::string())],
                'value' => ['type' => Type::nonNull(Type::string())],
                'description' => ['type' => Type::string()],
            ],
        ]);

        $config = [
            'name' => 'Mutation',
            'fields' => [
                'createCustomer' => [
                    'type' => Type::nonNull(CustomerType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'email' => ['type' => Type::nonNull(Type::string())],
                        'phone' => ['type' => Type::string()],
                        'company' => ['type' => Type::string()],
                        'status' => ['type' => Type::string()],
                        'isVip' => ['type' => Type::boolean()],
                    ],
                    'resolve' => fn($root, $args) => $customerResolver->resolveCreateCustomer($args),
                ],
                'updateCustomer' => [
                    'type' => Type::nonNull(CustomerType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::string()],
                        'email' => ['type' => Type::string()],
                        'phone' => ['type' => Type::string()],
                        'company' => ['type' => Type::string()],
                        'status' => ['type' => Type::string()],
                        'isVip' => ['type' => Type::boolean()],
                    ],
                    'resolve' => fn($root, $args) => $customerResolver->resolveUpdateCustomer($args),
                ],
                'deleteCustomer' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $customerResolver->resolveDeleteCustomer($args),
                ],
                'createContact' => [
                    'type' => Type::nonNull(ContactType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'email' => ['type' => Type::string()],
                        'phone' => ['type' => Type::string()],
                        'title' => ['type' => Type::string()],
                        'isPrimary' => ['type' => Type::boolean()],
                        'customerId' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $contactResolver->resolveCreateContact($args),
                ],
                'updateContact' => [
                    'type' => Type::nonNull(ContactType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::string()],
                        'email' => ['type' => Type::string()],
                        'phone' => ['type' => Type::string()],
                        'title' => ['type' => Type::string()],
                        'isPrimary' => ['type' => Type::boolean()],
                    ],
                    'resolve' => fn($root, $args) => $contactResolver->resolveUpdateContact($args),
                ],
                'deleteContact' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $contactResolver->resolveDeleteContact($args),
                ],
                'createDeal' => [
                    'type' => Type::nonNull(DealType::getInstance()),
                    'args' => [
                        'title' => ['type' => Type::nonNull(Type::string())],
                        'value' => ['type' => Type::float()],
                        'status' => ['type' => Type::string()],
                        'closeDate' => ['type' => Type::string()],
                        'customerId' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $dealResolver->resolveCreateDeal($args),
                ],
                'updateDeal' => [
                    'type' => Type::nonNull(DealType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'title' => ['type' => Type::string()],
                        'value' => ['type' => Type::float()],
                        'status' => ['type' => Type::string()],
                        'closeDate' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $dealResolver->resolveUpdateDeal($args),
                ],
                'deleteDeal' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $dealResolver->resolveDeleteDeal($args),
                ],
                'createLead' => [
                    'type' => Type::nonNull(LeadType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::string()],
                        'email' => ['type' => Type::string()],
                        'phone' => ['type' => Type::string()],
                        'company' => ['type' => Type::string()],
                        'status' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $leadResolver->resolveCreateLead($args),
                ],
                'updateLead' => [
                    'type' => Type::nonNull(LeadType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::string()],
                        'email' => ['type' => Type::string()],
                        'phone' => ['type' => Type::string()],
                        'company' => ['type' => Type::string()],
                        'status' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $leadResolver->resolveUpdateLead($args),
                ],
                'deleteLead' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $leadResolver->resolveDeleteLead($args),
                ],
                'startCall' => [
                    'type' => Type::nonNull(CallLogType::getInstance()),
                    'args' => [
                        'targetType' => ['type' => Type::nonNull(Type::string())],
                        'targetId' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $callLogResolver->resolveStartCall($args),
                ],
                'stopCall' => [
                    'type' => Type::nonNull(CallLogType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $callLogResolver->resolveStopCall($args),
                ],
                'createEmailCampaign' => [
                    'type' => Type::nonNull(EmailCampaignType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'subject' => ['type' => Type::nonNull(Type::string())],
                        'content' => ['type' => Type::nonNull(Type::string())],
                        'targetType' => ['type' => Type::nonNull(Type::string())],
                        'targetSegment' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $emailCampaignResolver->resolveCreateEmailCampaign($args),
                ],
                'createCategory' => [
                    'type' => Type::nonNull(CategoryType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'slug' => ['type' => Type::nonNull(Type::string())],
                        'description' => ['type' => Type::string()],
                        'sortOrder' => ['type' => Type::int()],
                        'parentId' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $categoryResolver->resolveCreateCategory($args),
                ],
                'updateCategory' => [
                    'type' => Type::nonNull(CategoryType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::string()],
                        'slug' => ['type' => Type::string()],
                        'description' => ['type' => Type::string()],
                        'sortOrder' => ['type' => Type::int()],
                        'parentId' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $categoryResolver->resolveUpdateCategory($args),
                ],
                'deleteCategory' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $categoryResolver->resolveDeleteCategory($args),
                ],
                'createInventoryItem' => [
                    'type' => Type::nonNull(InventoryItemType::getInstance()),
                    'args' => [
                        'sku' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'description' => ['type' => Type::string()],
                        'unit' => ['type' => Type::string()],
                        'stockQuantity' => ['type' => Type::int()],
                        'reservedQuantity' => ['type' => Type::int()],
                        'reorderLevel' => ['type' => Type::int()],
                        'cost' => ['type' => Type::float()],
                        'isActive' => ['type' => Type::boolean()],
                        'categoryId' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $inventoryResolver->resolveCreateInventoryItem($args),
                ],
                'updateInventoryItem' => [
                    'type' => Type::nonNull(InventoryItemType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'sku' => ['type' => Type::string()],
                        'name' => ['type' => Type::string()],
                        'description' => ['type' => Type::string()],
                        'unit' => ['type' => Type::string()],
                        'stockQuantity' => ['type' => Type::int()],
                        'reservedQuantity' => ['type' => Type::int()],
                        'reorderLevel' => ['type' => Type::int()],
                        'cost' => ['type' => Type::float()],
                        'isActive' => ['type' => Type::boolean()],
                        'categoryId' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $inventoryResolver->resolveUpdateInventoryItem($args),
                ],
                'deleteInventoryItem' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $inventoryResolver->resolveDeleteInventoryItem($args),
                ],
                'createPricingList' => [
                    'type' => Type::nonNull(PricingListType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'code' => ['type' => Type::nonNull(Type::string())],
                        'currency' => ['type' => Type::string()],
                        'description' => ['type' => Type::string()],
                        'isDefault' => ['type' => Type::boolean()],
                        'isActive' => ['type' => Type::boolean()],
                    ],
                    'resolve' => fn($root, $args) => $pricingListResolver->resolveCreatePricingList($args),
                ],
                'updatePricingList' => [
                    'type' => Type::nonNull(PricingListType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::string()],
                        'code' => ['type' => Type::string()],
                        'currency' => ['type' => Type::string()],
                        'description' => ['type' => Type::string()],
                        'isDefault' => ['type' => Type::boolean()],
                        'isActive' => ['type' => Type::boolean()],
                    ],
                    'resolve' => fn($root, $args) => $pricingListResolver->resolveUpdatePricingList($args),
                ],
                'deletePricingList' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $pricingListResolver->resolveDeletePricingList($args),
                ],
                'upsertPricingListItem' => [
                    'type' => Type::nonNull(PricingListItemType::getInstance()),
                    'args' => [
                        'pricingListId' => ['type' => Type::nonNull(Type::string())],
                        'inventoryItemId' => ['type' => Type::nonNull(Type::string())],
                        'price' => ['type' => Type::nonNull(Type::float())],
                        'compareAtPrice' => ['type' => Type::float()],
                    ],
                    'resolve' => fn($root, $args) => $pricingListResolver->resolveUpsertPricingListItem($args),
                ],
                'deletePricingListItem' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $pricingListResolver->resolveDeletePricingListItem($args),
                ],
                'updateSetting' => [
                    'type' => Type::nonNull(AppSettingType::getInstance()),
                    'args' => [
                        'key' => ['type' => Type::nonNull(Type::string())],
                        'value' => ['type' => Type::nonNull(Type::string())],
                        'description' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $settingResolver->resolveUpdateSetting($args),
                ],
                'updateSettings' => [
                    'type' => Type::nonNull(Type::listOf(Type::nonNull(AppSettingType::getInstance()))),
                    'args' => [
                        'settings' => ['type' => Type::nonNull(Type::listOf(Type::nonNull($settingInputType)))],
                    ],
                    'resolve' => fn($root, $args) => $settingResolver->resolveUpdateSettings($args),
                ],
                'deleteSetting' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'key' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $settingResolver->resolveDeleteSetting($args),
                ],
                'createUser' => [
                    'type' => Type::nonNull(AppUserType::getInstance()),
                    'args' => [
                        'name' => ['type' => Type::nonNull(Type::string())],
                        'email' => ['type' => Type::nonNull(Type::string())],
                        'role' => ['type' => Type::string()],
                        'status' => ['type' => Type::string()],
                        'avatarColor' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $userResolver->resolveCreateUser($args),
                ],
                'updateUser' => [
                    'type' => Type::nonNull(AppUserType::getInstance()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                        'name' => ['type' => Type::string()],
                        'email' => ['type' => Type::string()],
                        'role' => ['type' => Type::string()],
                        'status' => ['type' => Type::string()],
                        'avatarColor' => ['type' => Type::string()],
                    ],
                    'resolve' => fn($root, $args) => $userResolver->resolveUpdateUser($args),
                ],
                'deleteUser' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $userResolver->resolveDeleteUser($args),
                ],
                'markNotificationRead' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'id' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $notificationResolver->resolveMarkNotificationRead($args),
                ],
                'markAllNotificationsRead' => [
                    'type' => Type::nonNull(Type::boolean()),
                    'args' => [
                        'userId' => ['type' => Type::nonNull(Type::string())],
                    ],
                    'resolve' => fn($root, $args) => $notificationResolver->resolveMarkAllNotificationsRead($args),
                ],
            ]
        ];

        parent::__construct($config);
    }
}
