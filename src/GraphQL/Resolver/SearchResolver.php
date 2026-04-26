<?php

namespace App\GraphQL\Resolver;

use App\Repository\CustomerRepository;
use App\Repository\DealRepository;
use App\Repository\InventoryItemRepository;
use App\Repository\LeadRepository;

class SearchResolver
{
    public function __construct(
        private readonly CustomerRepository $customerRepository,
        private readonly LeadRepository $leadRepository,
        private readonly DealRepository $dealRepository,
        private readonly InventoryItemRepository $inventoryItemRepository
    ) {
    }

    public function resolveSearch(array $args): array
    {
        $query = trim($args['query'] ?? '');
        $limit = $args['limit'] ?? 10;

        if ($query === '' || mb_strlen($query) < 2) {
            return [
                'customers' => [],
                'leads' => [],
                'deals' => [],
                'inventoryItems' => [],
                'totalResults' => 0,
            ];
        }

        $customers = array_map(
            fn($c) => [
                'id' => $c->getId(),
                'type' => 'customer',
                'name' => $c->getName(),
                'email' => $c->getEmail(),
                'subtitle' => $c->getCompany(),
            ],
            $this->customerRepository->search($query, $limit)
        );

        $leads = array_map(
            fn($l) => [
                'id' => $l->getId(),
                'type' => 'lead',
                'name' => $l->getName() ?? $l->getEmail() ?? 'Untitled lead',
                'email' => $l->getEmail(),
                'subtitle' => $l->getCompany(),
            ],
            $this->leadRepository->search($query, $limit)
        );

        $deals = array_map(
            fn($d) => [
                'id' => $d->getId(),
                'type' => 'deal',
                'name' => $d->getTitle(),
                'email' => null,
                'subtitle' => $d->getStatus(),
            ],
            $this->dealRepository->search($query, $limit)
        );

        $inventoryItems = array_map(
            fn($i) => [
                'id' => $i->getId(),
                'type' => 'inventoryItem',
                'name' => $i->getName(),
                'email' => null,
                'subtitle' => $i->getSku(),
            ],
            $this->inventoryItemRepository->search($query, $limit)
        );

        $totalResults = count($customers) + count($leads) + count($deals) + count($inventoryItems);

        return [
            'customers' => $customers,
            'leads' => $leads,
            'deals' => $deals,
            'inventoryItems' => $inventoryItems,
            'totalResults' => $totalResults,
        ];
    }
}
