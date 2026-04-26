<?php

namespace App\GraphQL\Resolver;

use App\Entity\InventoryItem;
use App\GraphQL\Exception\ValidationException;
use App\Repository\CategoryRepository;
use App\Repository\InventoryItemRepository;
use App\Service\ActivityLogger;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class InventoryResolver
{
    public function __construct(
        private readonly InventoryItemRepository $inventoryItemRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function resolveInventoryItems(): array
    {
        return $this->inventoryItemRepository->findAllOrdered();
    }

    public function resolveInventoryItem(array $args): ?InventoryItem
    {
        return $this->inventoryItemRepository->find($args['id']);
    }

    public function resolveInventoryItemCount(): int
    {
        return $this->inventoryItemRepository->count([]);
    }

    public function resolveCreateInventoryItem(array $args): InventoryItem
    {
        $input = $this->normalizeInput($args);
        $this->validateInput($input);

        $inventoryItem = new InventoryItem();
        $this->applyInput($inventoryItem, $input);

        try {
            $this->entityManager->persist($inventoryItem);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['sku'], $exception);
        }

        $this->activityLogger->log(
            'inventory_item',
            'created',
            $inventoryItem->getId(),
            sprintf('Inventory item "%s" (%s) was created.', $inventoryItem->getName(), $inventoryItem->getSku())
        );

        return $inventoryItem;
    }

    public function resolveUpdateInventoryItem(array $args): InventoryItem
    {
        $inventoryItem = $this->inventoryItemRepository->find($args['id']);

        if ($inventoryItem === null) {
            throw new \Exception('Inventory item not found');
        }

        $input = $this->normalizeInput([
            'sku' => $args['sku'] ?? $inventoryItem->getSku(),
            'name' => $args['name'] ?? $inventoryItem->getName(),
            'description' => array_key_exists('description', $args) ? $args['description'] : $inventoryItem->getDescription(),
            'unit' => array_key_exists('unit', $args) ? $args['unit'] : $inventoryItem->getUnit(),
            'stockQuantity' => array_key_exists('stockQuantity', $args) ? $args['stockQuantity'] : $inventoryItem->getStockQuantity(),
            'reservedQuantity' => array_key_exists('reservedQuantity', $args) ? $args['reservedQuantity'] : $inventoryItem->getReservedQuantity(),
            'reorderLevel' => array_key_exists('reorderLevel', $args) ? $args['reorderLevel'] : $inventoryItem->getReorderLevel(),
            'cost' => array_key_exists('cost', $args) ? $args['cost'] : $inventoryItem->getCost(),
            'isActive' => array_key_exists('isActive', $args) ? $args['isActive'] : $inventoryItem->isActive(),
            'categoryId' => array_key_exists('categoryId', $args) ? $args['categoryId'] : $inventoryItem->getCategory()?->getId(),
        ]);

        $this->validateInput($input, $inventoryItem);
        $this->applyInput($inventoryItem, $input);
        $inventoryItem->setUpdatedAt(new \DateTime());

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['sku'], $exception);
        }

        $this->activityLogger->log(
            'inventory_item',
            'updated',
            $inventoryItem->getId(),
            sprintf('Inventory item "%s" (%s) was updated.', $inventoryItem->getName(), $inventoryItem->getSku())
        );

        return $inventoryItem;
    }

    public function resolveDeleteInventoryItem(array $args): bool
    {
        $inventoryItem = $this->inventoryItemRepository->find($args['id']);

        if ($inventoryItem === null) {
            return false;
        }

        $inventoryItemId = $inventoryItem->getId();
        $inventoryItemLabel = sprintf('%s (%s)', $inventoryItem->getName(), $inventoryItem->getSku());

        $this->entityManager->remove($inventoryItem);
        $this->entityManager->flush();

        $this->activityLogger->log(
            'inventory_item',
            'deleted',
            $inventoryItemId,
            sprintf('Inventory item "%s" was deleted.', $inventoryItemLabel)
        );

        return true;
    }

    private function applyInput(InventoryItem $inventoryItem, array $input): void
    {
        $category = null;

        if ($input['categoryId'] !== null) {
            $category = $this->categoryRepository->find($input['categoryId']);
        }

        $inventoryItem->setSku($input['sku']);
        $inventoryItem->setName($input['name']);
        $inventoryItem->setDescription($input['description']);
        $inventoryItem->setUnit($input['unit']);
        $inventoryItem->setStockQuantity($input['stockQuantity']);
        $inventoryItem->setReservedQuantity($input['reservedQuantity']);
        $inventoryItem->setReorderLevel($input['reorderLevel']);
        $inventoryItem->setCost($input['cost']);
        $inventoryItem->setIsActive($input['isActive']);
        $inventoryItem->setCategory($category);
    }

    private function normalizeInput(array $args): array
    {
        $description = array_key_exists('description', $args) && $args['description'] !== null
            ? trim((string) $args['description'])
            : null;
        $unit = array_key_exists('unit', $args) && $args['unit'] !== null
            ? trim((string) $args['unit'])
            : 'unit';

        return [
            'sku' => isset($args['sku']) ? strtoupper(trim((string) $args['sku'])) : '',
            'name' => isset($args['name']) ? trim((string) $args['name']) : '',
            'description' => $description === '' ? null : $description,
            'unit' => $unit === '' ? 'unit' : strtolower($unit),
            'stockQuantity' => isset($args['stockQuantity']) ? (int) $args['stockQuantity'] : 0,
            'reservedQuantity' => isset($args['reservedQuantity']) ? (int) $args['reservedQuantity'] : 0,
            'reorderLevel' => isset($args['reorderLevel']) ? (int) $args['reorderLevel'] : 0,
            'cost' => array_key_exists('cost', $args) && $args['cost'] !== null
                ? number_format((float) $args['cost'], 2, '.', '')
                : null,
            'isActive' => !array_key_exists('isActive', $args) || (bool) $args['isActive'],
            'categoryId' => array_key_exists('categoryId', $args) && $args['categoryId'] !== null
                ? (string) $args['categoryId']
                : null,
        ];
    }

    private function validateInput(array $input, ?InventoryItem $existingInventoryItem = null): void
    {
        $fieldErrors = [];

        if ($input['sku'] === '') {
            $fieldErrors['sku'] = 'SKU is required.';
        } elseif (mb_strlen($input['sku']) > 120) {
            $fieldErrors['sku'] = 'SKU must be 120 characters or fewer.';
        } elseif (!preg_match('/^[A-Z0-9][A-Z0-9._-]*$/', $input['sku'])) {
            $fieldErrors['sku'] = 'Use uppercase letters, numbers, dots, underscores, or dashes for SKU.';
        } elseif ($this->inventoryItemRepository->skuExistsForAnotherInventoryItem($input['sku'], $existingInventoryItem?->getId())) {
            $fieldErrors['sku'] = 'This SKU is already used by another inventory item.';
        }

        if ($input['name'] === '') {
            $fieldErrors['name'] = 'Inventory item name is required.';
        } elseif (mb_strlen($input['name']) > 255) {
            $fieldErrors['name'] = 'Inventory item name must be 255 characters or fewer.';
        }

        if ($input['description'] !== null && mb_strlen($input['description']) > 4000) {
            $fieldErrors['description'] = 'Description must be 4000 characters or fewer.';
        }

        if ($input['unit'] === '') {
            $fieldErrors['unit'] = 'Unit is required.';
        } elseif (mb_strlen($input['unit']) > 50) {
            $fieldErrors['unit'] = 'Unit must be 50 characters or fewer.';
        }

        foreach (['stockQuantity', 'reservedQuantity', 'reorderLevel'] as $field) {
            if ($input[$field] < 0) {
                $fieldErrors[$field] = 'Quantity values cannot be negative.';
            }
        }

        if ($input['reservedQuantity'] > $input['stockQuantity']) {
            $fieldErrors['reservedQuantity'] = 'Reserved quantity cannot exceed stock quantity.';
        }

        if ($input['cost'] !== null && (float) $input['cost'] < 0) {
            $fieldErrors['cost'] = 'Cost cannot be negative.';
        }

        if ($input['categoryId'] !== null && $this->categoryRepository->find($input['categoryId']) === null) {
            $fieldErrors['categoryId'] = 'Selected category was not found.';
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted inventory fields.',
                $fieldErrors
            );
        }
    }

    private function createConflictException(string $sku, UniqueConstraintViolationException $exception): ValidationException
    {
        if ($this->inventoryItemRepository->findOneBySkuInsensitive($sku) !== null) {
            return new ValidationException(
                'An inventory item with this SKU already exists.',
                [
                    'sku' => 'This SKU is already used by another inventory item.',
                ],
                'conflict'
            );
        }

        return new ValidationException(
            'Unable to save the inventory item because the submitted data conflicts with an existing record.',
            [],
            'conflict'
        );
    }
}
