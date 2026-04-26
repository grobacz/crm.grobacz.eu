<?php

namespace App\GraphQL\Resolver;

use App\Repository\ActivityRepository;

class ActivityResolver
{
    public function __construct(
        private readonly ActivityRepository $activityRepository,
    ) {
    }

    public function resolveRecentActivities(array $args): array
    {
        $limit = $args['limit'] ?? 10;

        return $this->activityRepository->findRecent(max(1, min(50, (int) $limit)));
    }
}
