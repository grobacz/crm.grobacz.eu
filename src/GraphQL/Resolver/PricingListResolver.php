<?php

namespace App\GraphQL\Resolver;

use App\Entity\PricingList;
use App\Entity\PricingListItem;
use App\GraphQL\Exception\ValidationException;
use App\Repository\InventoryItemRepository;
use App\Repository\PricingListItemRepository;
use App\Repository\PricingListRepository;
use App\Service\ActivityLogger;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class PricingListResolver
{
    public function __construct(
        private readonly PricingListRepository $pricingListRepository,
        private readonly PricingListItemRepository $pricingListItemRepository,
        private readonly InventoryItemRepository $inventoryItemRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function resolvePricingLists(): array
    {
        return $this->pricingListRepository->findAllOrdered();
    }

    public function resolvePricingList(array $args): ?PricingList
    {
        return $this->pricingListRepository->find($args['id']);
    }

    public function resolvePricingListCount(): int
    {
        return $this->pricingListRepository->count([]);
    }

    public function resolveCreatePricingList(array $args): PricingList
    {
        $input = $this->normalizeListInput($args);
        $this->validateListInput($input);

        if ($input['isDefault']) {
            $this->pricingListRepository->clearDefaultFlagExcept();
        }

        $pricingList = new PricingList();
        $this->applyListInput($pricingList, $input);

        try {
            $this->entityManager->persist($pricingList);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['code'], $exception);
        }

        $this->activityLogger->log(
            'pricing_list',
            'created',
            $pricingList->getId(),
            sprintf('Pricing list "%s" was created.', $pricingList->getName())
        );

        return $pricingList;
    }

    public function resolveUpdatePricingList(array $args): PricingList
    {
        $pricingList = $this->pricingListRepository->find($args['id']);

        if ($pricingList === null) {
            throw new \Exception('Pricing list not found');
        }

        $input = $this->normalizeListInput([
            'name' => $args['name'] ?? $pricingList->getName(),
            'code' => $args['code'] ?? $pricingList->getCode(),
            'currency' => array_key_exists('currency', $args) ? $args['currency'] : $pricingList->getCurrency(),
            'description' => array_key_exists('description', $args) ? $args['description'] : $pricingList->getDescription(),
            'isDefault' => array_key_exists('isDefault', $args) ? $args['isDefault'] : $pricingList->isDefault(),
            'isActive' => array_key_exists('isActive', $args) ? $args['isActive'] : $pricingList->isActive(),
        ]);

        $this->validateListInput($input, $pricingList);

        if ($input['isDefault']) {
            $this->pricingListRepository->clearDefaultFlagExcept($pricingList->getId());
        }

        $this->applyListInput($pricingList, $input);
        $pricingList->setUpdatedAt(new \DateTime());

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['code'], $exception);
        }

        $this->activityLogger->log(
            'pricing_list',
            'updated',
            $pricingList->getId(),
            sprintf('Pricing list "%s" was updated.', $pricingList->getName())
        );

        return $pricingList;
    }

    public function resolveDeletePricingList(array $args): bool
    {
        $pricingList = $this->pricingListRepository->find($args['id']);

        if ($pricingList === null) {
            return false;
        }

        $pricingListId = $pricingList->getId();
        $pricingListName = $pricingList->getName();

        $this->entityManager->remove($pricingList);
        $this->entityManager->flush();

        $this->activityLogger->log(
            'pricing_list',
            'deleted',
            $pricingListId,
            sprintf('Pricing list "%s" was deleted.', $pricingListName)
        );

        return true;
    }

    public function resolveUpsertPricingListItem(array $args): PricingListItem
    {
        $input = $this->normalizeItemInput($args);
        $this->validateItemInput($input);

        $pricingList = $this->pricingListRepository->find($input['pricingListId']);
        $inventoryItem = $this->inventoryItemRepository->find($input['inventoryItemId']);

        if ($pricingList === null || $inventoryItem === null) {
            throw new \Exception('Pricing list item references are invalid');
        }

        $pricingListItem = $this->pricingListItemRepository->findOneByPricingListAndInventoryItem($pricingList, $inventoryItem);
        $action = 'updated';

        if ($pricingListItem === null) {
            $pricingListItem = new PricingListItem();
            $pricingListItem->setPricingList($pricingList);
            $pricingListItem->setInventoryItem($inventoryItem);
            $action = 'created';
            $this->entityManager->persist($pricingListItem);
        }

        $pricingListItem->setPrice($input['price']);
        $pricingListItem->setCompareAtPrice($input['compareAtPrice']);
        $pricingListItem->setUpdatedAt(new \DateTime());
        $this->entityManager->flush();

        $this->activityLogger->log(
            'pricing_list_item',
            $action,
            $pricingListItem->getId(),
            sprintf(
                'Price for "%s" in list "%s" was %s.',
                $inventoryItem->getName(),
                $pricingList->getName(),
                $action
            )
        );

        return $pricingListItem;
    }

    public function resolveDeletePricingListItem(array $args): bool
    {
        $pricingListItem = $this->pricingListItemRepository->find($args['id']);

        if ($pricingListItem === null) {
            return false;
        }

        $itemId = $pricingListItem->getId();
        $inventoryItemName = $pricingListItem->getInventoryItem()?->getName() ?? 'Unknown item';
        $pricingListName = $pricingListItem->getPricingList()?->getName() ?? 'Unknown pricing list';

        $this->entityManager->remove($pricingListItem);
        $this->entityManager->flush();

        $this->activityLogger->log(
            'pricing_list_item',
            'deleted',
            $itemId,
            sprintf('Price for "%s" in list "%s" was deleted.', $inventoryItemName, $pricingListName)
        );

        return true;
    }

    private function applyListInput(PricingList $pricingList, array $input): void
    {
        $pricingList->setName($input['name']);
        $pricingList->setCode($input['code']);
        $pricingList->setCurrency($input['currency']);
        $pricingList->setDescription($input['description']);
        $pricingList->setIsDefault($input['isDefault']);
        $pricingList->setIsActive($input['isActive']);
    }

    private function normalizeListInput(array $args): array
    {
        $description = array_key_exists('description', $args) && $args['description'] !== null
            ? trim((string) $args['description'])
            : null;

        return [
            'name' => isset($args['name']) ? trim((string) $args['name']) : '',
            'code' => isset($args['code']) ? strtoupper(trim((string) $args['code'])) : '',
            'currency' => isset($args['currency']) ? strtoupper(trim((string) $args['currency'])) : 'USD',
            'description' => $description === '' ? null : $description,
            'isDefault' => (bool) ($args['isDefault'] ?? false),
            'isActive' => !array_key_exists('isActive', $args) || (bool) $args['isActive'],
        ];
    }

    private function validateListInput(array $input, ?PricingList $existingPricingList = null): void
    {
        $fieldErrors = [];

        if ($input['name'] === '') {
            $fieldErrors['name'] = 'Pricing list name is required.';
        } elseif (mb_strlen($input['name']) > 120) {
            $fieldErrors['name'] = 'Pricing list name must be 120 characters or fewer.';
        }

        if ($input['code'] === '') {
            $fieldErrors['code'] = 'Pricing list code is required.';
        } elseif (mb_strlen($input['code']) > 80) {
            $fieldErrors['code'] = 'Pricing list code must be 80 characters or fewer.';
        } elseif (!preg_match('/^[A-Z0-9][A-Z0-9_-]*$/', $input['code'])) {
            $fieldErrors['code'] = 'Use uppercase letters, numbers, underscores, or dashes for pricing list code.';
        } elseif ($this->pricingListRepository->codeExistsForAnotherPricingList($input['code'], $existingPricingList?->getId())) {
            $fieldErrors['code'] = 'This code is already used by another pricing list.';
        }

        if (!preg_match('/^[A-Z]{3}$/', $input['currency'])) {
            $fieldErrors['currency'] = 'Currency must use a 3-letter ISO code.';
        }

        if ($input['description'] !== null && mb_strlen($input['description']) > 4000) {
            $fieldErrors['description'] = 'Description must be 4000 characters or fewer.';
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted pricing list fields.',
                $fieldErrors
            );
        }
    }

    private function normalizeItemInput(array $args): array
    {
        return [
            'pricingListId' => isset($args['pricingListId']) ? (string) $args['pricingListId'] : '',
            'inventoryItemId' => isset($args['inventoryItemId']) ? (string) $args['inventoryItemId'] : '',
            'price' => isset($args['price']) ? number_format((float) $args['price'], 2, '.', '') : '0.00',
            'compareAtPrice' => array_key_exists('compareAtPrice', $args) && $args['compareAtPrice'] !== null
                ? number_format((float) $args['compareAtPrice'], 2, '.', '')
                : null,
        ];
    }

    private function validateItemInput(array $input): void
    {
        $fieldErrors = [];

        if ($input['pricingListId'] === '' || $this->pricingListRepository->find($input['pricingListId']) === null) {
            $fieldErrors['pricingListId'] = 'Select a valid pricing list.';
        }

        if ($input['inventoryItemId'] === '' || $this->inventoryItemRepository->find($input['inventoryItemId']) === null) {
            $fieldErrors['inventoryItemId'] = 'Select a valid inventory item.';
        }

        if ((float) $input['price'] < 0) {
            $fieldErrors['price'] = 'Price cannot be negative.';
        }

        if ($input['compareAtPrice'] !== null && (float) $input['compareAtPrice'] < (float) $input['price']) {
            $fieldErrors['compareAtPrice'] = 'Compare-at price must be greater than or equal to the active price.';
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted pricing entry fields.',
                $fieldErrors
            );
        }
    }

    private function createConflictException(string $code, UniqueConstraintViolationException $exception): ValidationException
    {
        if ($this->pricingListRepository->findOneByCodeInsensitive($code) !== null) {
            return new ValidationException(
                'A pricing list with this code already exists.',
                [
                    'code' => 'This code is already used by another pricing list.',
                ],
                'conflict'
            );
        }

        return new ValidationException(
            'Unable to save the pricing list because the submitted data conflicts with an existing record.',
            [],
            'conflict'
        );
    }
}
