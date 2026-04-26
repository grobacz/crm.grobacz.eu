<?php

namespace App\GraphQL\Resolver;

use App\Entity\AppUser;
use App\GraphQL\Exception\ValidationException;
use App\Repository\AppUserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserResolver
{
    private const ALLOWED_ROLES = ['admin', 'manager', 'user'];
    private const ALLOWED_STATUSES = ['active', 'inactive'];

    public function __construct(
        private readonly AppUserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolveUsers(): array
    {
        return $this->userRepository->findAll();
    }

    public function resolveActiveUsers(): array
    {
        return $this->userRepository->findActive();
    }

    public function resolveUser(array $args): ?AppUser
    {
        return $this->userRepository->find($args['id']);
    }

    public function resolveUserCount(): int
    {
        return $this->userRepository->count([]);
    }

    public function resolveCreateUser(array $args): AppUser
    {
        $input = $this->normalizeInput($args);
        $this->validateInput($input);

        $user = new AppUser();
        $user->setName($input['name']);
        $user->setEmail($input['email']);
        $user->setRole($input['role']);
        $user->setStatus($input['status']);
        $user->setAvatarColor($input['avatarColor']);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function resolveUpdateUser(array $args): AppUser
    {
        $user = $this->userRepository->find($args['id']);

        if (!$user) {
            throw new \Exception('User not found');
        }

        $input = $this->normalizeInput([
            'name' => array_key_exists('name', $args) ? $args['name'] : $user->getName(),
            'email' => array_key_exists('email', $args) ? $args['email'] : $user->getEmail(),
            'role' => array_key_exists('role', $args) ? $args['role'] : $user->getRole(),
            'status' => array_key_exists('status', $args) ? $args['status'] : $user->getStatus(),
            'avatarColor' => array_key_exists('avatarColor', $args) ? $args['avatarColor'] : $user->getAvatarColor(),
        ]);

        $this->validateInput($input, $user);

        $user->setName($input['name']);
        $user->setEmail($input['email']);
        $user->setRole($input['role']);
        $user->setStatus($input['status']);
        $user->setAvatarColor($input['avatarColor']);
        $user->setUpdatedAt(new \DateTime());

        $this->entityManager->flush();

        return $user;
    }

    public function resolveDeleteUser(array $args): bool
    {
        $user = $this->userRepository->find($args['id']);

        if (!$user) {
            return false;
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }

    private function normalizeInput(array $args): array
    {
        return [
            'name' => isset($args['name']) ? trim((string) $args['name']) : '',
            'email' => isset($args['email']) ? trim((string) $args['email']) : '',
            'role' => isset($args['role']) ? trim((string) $args['role']) : 'user',
            'status' => isset($args['status']) ? trim((string) $args['status']) : 'active',
            'avatarColor' => $args['avatarColor'] ?? null,
        ];
    }

    private function validateInput(array $input, ?AppUser $existingUser = null): void
    {
        $fieldErrors = [];

        if ($input['name'] === '') {
            $fieldErrors['name'] = 'Name is required.';
        } elseif (mb_strlen($input['name']) > 255) {
            $fieldErrors['name'] = 'Name must be 255 characters or fewer.';
        }

        if ($input['email'] === '') {
            $fieldErrors['email'] = 'Email is required.';
        } elseif (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = 'Enter a valid email address.';
        } elseif ($this->userRepository->emailExistsForAnotherUser($input['email'], $existingUser?->getId())) {
            $fieldErrors['email'] = 'This email is already used by another user.';
        }

        if (!in_array($input['role'], self::ALLOWED_ROLES, true)) {
            $fieldErrors['role'] = 'Role must be admin, manager, or user.';
        }

        if (!in_array($input['status'], self::ALLOWED_STATUSES, true)) {
            $fieldErrors['status'] = 'Status must be active or inactive.';
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted fields.',
                $fieldErrors
            );
        }
    }
}
