<?php

namespace App\GraphQL\Resolver;

use App\Repository\AppSettingRepository;
use Doctrine\ORM\EntityManagerInterface;

class SettingResolver
{
    public function __construct(
        private readonly AppSettingRepository $settingRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolveSettings(): array
    {
        return $this->settingRepository->findAll();
    }

    public function resolveSetting(array $args): ?object
    {
        return $this->settingRepository->findOneByKey($args['key']);
    }

    public function resolveUpdateSetting(array $args): object
    {
        return $this->settingRepository->upsert(
            $args['key'],
            $args['value'],
            $args['description'] ?? null
        );
    }

    public function resolveUpdateSettings(array $args): array
    {
        $results = [];
        foreach ($args['settings'] as $settingInput) {
            $results[] = $this->settingRepository->upsert(
                $settingInput['key'],
                $settingInput['value'],
                $settingInput['description'] ?? null
            );
        }
        return $results;
    }

    public function resolveDeleteSetting(array $args): bool
    {
        $setting = $this->settingRepository->findOneByKey($args['key']);

        if (!$setting) {
            return false;
        }

        $this->entityManager->remove($setting);
        $this->entityManager->flush();

        return true;
    }
}
