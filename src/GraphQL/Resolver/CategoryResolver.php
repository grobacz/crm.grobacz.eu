<?php

namespace App\GraphQL\Resolver;

use App\Entity\Category;
use App\GraphQL\Exception\ValidationException;
use App\Repository\CategoryRepository;
use App\Service\ActivityLogger;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class CategoryResolver
{
    public function __construct(
        private readonly CategoryRepository $categoryRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function resolveCategories(): array
    {
        return $this->categoryRepository->findAllOrdered();
    }

    public function resolveCategory(array $args): ?Category
    {
        return $this->categoryRepository->find($args['id']);
    }

    public function resolveCategoryCount(): int
    {
        return $this->categoryRepository->count([]);
    }

    public function resolveCreateCategory(array $args): Category
    {
        $input = $this->normalizeInput($args);
        $this->validateInput($input);

        $category = new Category();
        $this->applyInput($category, $input);

        try {
            $this->entityManager->persist($category);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['slug'], $exception);
        }

        $this->activityLogger->log(
            'category',
            'created',
            $category->getId(),
            sprintf('Category "%s" was created.', $category->getName())
        );

        return $category;
    }

    public function resolveUpdateCategory(array $args): Category
    {
        $category = $this->categoryRepository->find($args['id']);

        if ($category === null) {
            throw new \Exception('Category not found');
        }

        $input = $this->normalizeInput([
            'name' => $args['name'] ?? $category->getName(),
            'slug' => $args['slug'] ?? $category->getSlug(),
            'description' => array_key_exists('description', $args) ? $args['description'] : $category->getDescription(),
            'sortOrder' => array_key_exists('sortOrder', $args) ? $args['sortOrder'] : $category->getSortOrder(),
            'parentId' => array_key_exists('parentId', $args) ? $args['parentId'] : $category->getParent()?->getId(),
        ]);

        $this->validateInput($input, $category);
        $this->applyInput($category, $input);
        $category->setUpdatedAt(new \DateTime());

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['slug'], $exception);
        }

        $this->activityLogger->log(
            'category',
            'updated',
            $category->getId(),
            sprintf('Category "%s" was updated.', $category->getName())
        );

        return $category;
    }

    public function resolveDeleteCategory(array $args): bool
    {
        $category = $this->categoryRepository->find($args['id']);

        if ($category === null) {
            return false;
        }

        foreach ($category->getChildren() as $child) {
            $child->setParent(null);
        }

        foreach ($category->getInventoryItems() as $inventoryItem) {
            $inventoryItem->setCategory(null);
        }

        $categoryId = $category->getId();
        $categoryName = $category->getName();

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->activityLogger->log(
            'category',
            'deleted',
            $categoryId,
            sprintf('Category "%s" was deleted.', $categoryName)
        );

        return true;
    }

    private function applyInput(Category $category, array $input): void
    {
        $parent = null;

        if ($input['parentId'] !== null) {
            $parent = $this->categoryRepository->find($input['parentId']);
        }

        $category->setName($input['name']);
        $category->setSlug($input['slug']);
        $category->setDescription($input['description']);
        $category->setSortOrder($input['sortOrder']);
        $category->setParent($parent);
    }

    private function normalizeInput(array $args): array
    {
        $name = isset($args['name']) ? trim((string) $args['name']) : '';
        $slug = isset($args['slug']) ? trim((string) $args['slug']) : '';
        $description = array_key_exists('description', $args) && $args['description'] !== null
            ? trim((string) $args['description'])
            : null;

        return [
            'name' => $name,
            'slug' => strtolower($slug),
            'description' => $description === '' ? null : $description,
            'sortOrder' => isset($args['sortOrder']) ? (int) $args['sortOrder'] : 0,
            'parentId' => array_key_exists('parentId', $args) && $args['parentId'] !== null
                ? (string) $args['parentId']
                : null,
        ];
    }

    private function validateInput(array $input, ?Category $existingCategory = null): void
    {
        $fieldErrors = [];

        if ($input['name'] === '') {
            $fieldErrors['name'] = 'Category name is required.';
        } elseif (mb_strlen($input['name']) > 120) {
            $fieldErrors['name'] = 'Category name must be 120 characters or fewer.';
        }

        if ($input['slug'] === '') {
            $fieldErrors['slug'] = 'Category slug is required.';
        } elseif (mb_strlen($input['slug']) > 160) {
            $fieldErrors['slug'] = 'Category slug must be 160 characters or fewer.';
        } elseif (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $input['slug'])) {
            $fieldErrors['slug'] = 'Use lowercase letters, numbers, and single dashes only.';
        } elseif ($this->categoryRepository->slugExistsForAnotherCategory($input['slug'], $existingCategory?->getId())) {
            $fieldErrors['slug'] = 'This slug is already used by another category.';
        }

        if ($input['description'] !== null && mb_strlen($input['description']) > 2000) {
            $fieldErrors['description'] = 'Description must be 2000 characters or fewer.';
        }

        if ($input['sortOrder'] < 0) {
            $fieldErrors['sortOrder'] = 'Sort order cannot be negative.';
        }

        if ($input['parentId'] !== null) {
            $parentCategory = $this->categoryRepository->find($input['parentId']);

            if ($parentCategory === null) {
                $fieldErrors['parentId'] = 'Parent category was not found.';
            } elseif ($existingCategory !== null && $parentCategory->getId() === $existingCategory->getId()) {
                $fieldErrors['parentId'] = 'A category cannot be its own parent.';
            }
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted category fields.',
                $fieldErrors
            );
        }
    }

    private function createConflictException(string $slug, UniqueConstraintViolationException $exception): ValidationException
    {
        if ($this->categoryRepository->findOneBySlugInsensitive($slug) !== null) {
            return new ValidationException(
                'A category with this slug already exists.',
                [
                    'slug' => 'This slug is already used by another category.',
                ],
                'conflict'
            );
        }

        return new ValidationException(
            'Unable to save the category because the submitted data conflicts with an existing record.',
            [],
            'conflict'
        );
    }
}
