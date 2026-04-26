<?php

namespace App\GraphQL\Resolver;

use App\Repository\ActivityRepository;
use App\Repository\NotificationRepository;

class NotificationResolver
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly ActivityRepository $activityRepository
    ) {
    }

    public function resolveNotifications(array $args): array
    {
        $notifications = $this->notificationRepository->findByUserId(
            $args['userId'],
            $args['limit'] ?? 50
        );

        foreach ($notifications as $notification) {
            $activityId = $notification->getActivityId();
            if ($activityId !== null) {
                $activity = $this->activityRepository->find($activityId);
                $notification->setActivity($activity);
            }
        }

        return $notifications;
    }

    public function resolveUnreadNotificationCount(array $args): int
    {
        return $this->notificationRepository->countUnreadByUserId($args['userId']);
    }

    public function resolveMarkNotificationRead(array $args): bool
    {
        return $this->notificationRepository->markAsRead($args['id']);
    }

    public function resolveMarkAllNotificationsRead(array $args): bool
    {
        $this->notificationRepository->markAllAsRead($args['userId']);
        return true;
    }
}
