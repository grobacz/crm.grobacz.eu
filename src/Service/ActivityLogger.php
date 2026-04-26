<?php

namespace App\Service;

use App\Entity\Activity;
use App\Repository\AppUserRepository;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;

class ActivityLogger
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AppUserRepository $userRepository,
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    public function log(string $entityType, string $action, ?string $entityId, string $message): Activity
    {
        $activity = new Activity();
        $activity->setEntityType($entityType);
        $activity->setAction($action);
        $activity->setEntityId($entityId);
        $activity->setMessage($message);

        $this->entityManager->persist($activity);
        $this->entityManager->flush();

        $activeUsers = $this->userRepository->findActive();
        $activeUserIds = array_map(fn($user) => $user->getId(), $activeUsers);

        if (!empty($activeUserIds)) {
            $this->notificationRepository->createForAllUsers($activity->getId(), $activeUserIds);
        }

        return $activity;
    }
}
