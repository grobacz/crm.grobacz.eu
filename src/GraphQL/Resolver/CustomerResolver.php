<?php

namespace App\GraphQL\Resolver;

use App\Entity\Customer;
use App\GraphQL\Exception\ValidationException;
use App\Repository\CustomerRepository;
use App\Service\ActivityLogger;
use App\Service\InputValidator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class CustomerResolver
{
    private const ALLOWED_STATUSES = ['active', 'inactive'];

    private CustomerRepository $customerRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        CustomerRepository $customerRepository,
        EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger
    ) {
        $this->customerRepository = $customerRepository;
        $this->entityManager = $entityManager;
    }

    public function resolveCustomers(): array
    {
        return $this->customerRepository->findAll();
    }

    public function resolveCustomer(array $args): ?Customer
    {
        return $this->customerRepository->find($args['id']);
    }

    public function resolveCustomerCount(): int
    {
        return $this->customerRepository->count([]);
    }

    public function resolveCreateCustomer(array $args): Customer
    {
        $input = $this->normalizeCustomerInput($args);
        $this->validateCustomerInput($input);

        $customer = new Customer();
        $customer->setName($input['name']);
        $customer->setEmail($input['email']);
        $customer->setPhone($input['phone']);
        $customer->setCompany($input['company']);
        $customer->setStatus($input['status']);
        $customer->setIsVip($input['isVip']);

        try {
            $this->entityManager->persist($customer);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['email'], $exception);
        }

        $this->activityLogger->log(
            'customer',
            'created',
            $customer->getId(),
            sprintf('Customer "%s" was created.', $customer->getName())
        );

        return $customer;
    }

    public function resolveUpdateCustomer(array $args): Customer
    {
        $customer = $this->customerRepository->find($args['id']);

        if (!$customer) {
            throw new \Exception('Customer not found');
        }

        $originalValues = [
            'name' => $customer->getName(),
            'email' => $customer->getEmail(),
            'phone' => $customer->getPhone(),
            'company' => $customer->getCompany(),
            'status' => $customer->getStatus(),
            'isVip' => $customer->isVip(),
        ];

        $input = $this->normalizeCustomerInput([
            'name' => $args['name'] ?? $customer->getName(),
            'email' => $args['email'] ?? $customer->getEmail(),
            'phone' => array_key_exists('phone', $args) ? $args['phone'] : $customer->getPhone(),
            'company' => array_key_exists('company', $args) ? $args['company'] : $customer->getCompany(),
            'status' => array_key_exists('status', $args) ? $args['status'] : $customer->getStatus(),
            'isVip' => array_key_exists('isVip', $args) ? $args['isVip'] : $customer->isVip(),
        ]);

        $this->validateCustomerInput($input, $customer);

        $customer->setName($input['name']);
        $customer->setEmail($input['email']);
        $customer->setPhone($input['phone']);
        $customer->setCompany($input['company']);
        $customer->setStatus($input['status']);
        $customer->setIsVip($input['isVip']);

        $customer->setUpdatedAt(new \DateTime());

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['email'], $exception);
        }

        $this->activityLogger->log(
            'customer',
            'updated',
            $customer->getId(),
            $this->buildCustomerUpdateMessage($customer->getName(), $originalValues, $input)
        );

        return $customer;
    }

    public function resolveDeleteCustomer(array $args): bool
    {
        $customer = $this->customerRepository->find($args['id']);

        if (!$customer) {
            return false;
        }

        $customerId = $customer->getId();
        $customerName = $customer->getName();

        $this->entityManager->remove($customer);
        $this->entityManager->flush();
        $this->activityLogger->log(
            'customer',
            'deleted',
            $customerId,
            sprintf('Customer "%s" was deleted.', $customerName)
        );

        return true;
    }

    private function normalizeCustomerInput(array $args): array
    {
        $phone = InputValidator::trimString($args['phone'] ?? null);
        $company = InputValidator::trimString($args['company'] ?? null);

        return [
            'name' => InputValidator::trimString($args['name'] ?? '') ?? '',
            'email' => InputValidator::normalizeEmail($args['email'] ?? '') ?? '',
            'phone' => $phone,
            'company' => $company,
            'status' => isset($args['status']) && $args['status'] !== null
                ? strtolower(InputValidator::trimString($args['status']) ?? '')
                : 'active',
            'isVip' => (bool) ($args['isVip'] ?? false),
        ];
    }

    private function validateCustomerInput(array $input, ?Customer $existingCustomer = null): void
    {
        $fieldErrors = [];

        $nameError = InputValidator::validateName($input['name'], true);
        if ($nameError !== null) {
            $fieldErrors['name'] = 'Customer ' . lcfirst($nameError);
        }

        $emailError = InputValidator::validateEmail($input['email'], true);
        if ($emailError !== null) {
            $fieldErrors['email'] = $emailError;
        } elseif ($this->customerRepository->emailExistsForAnotherCustomer($input['email'], $existingCustomer?->getId())) {
            $fieldErrors['email'] = 'This email is already used by another customer.';
        }

        $phoneError = InputValidator::validatePhone($input['phone'], false);
        if ($phoneError !== null) {
            $fieldErrors['phone'] = $phoneError;
        }

        $companyError = InputValidator::validateCompany($input['company']);
        if ($companyError !== null) {
            $fieldErrors['company'] = $companyError;
        }

        $statusError = InputValidator::validateStatus($input['status'], self::ALLOWED_STATUSES);
        if ($statusError !== null) {
            $fieldErrors['status'] = 'Customer ' . lcfirst($statusError);
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted customer fields.',
                $fieldErrors
            );
        }
    }

    private function createConflictException(string $email, UniqueConstraintViolationException $exception): ValidationException
    {
        if ($this->customerRepository->findOneByEmailInsensitive($email) !== null) {
            return new ValidationException(
                'A customer with this email already exists.',
                [
                    'email' => 'This email is already used by another customer.',
                ],
                'conflict'
            );
        }

        return new ValidationException(
            'Unable to save the customer because the submitted data conflicts with an existing record.',
            [],
            'conflict'
        );
    }

    private function buildCustomerUpdateMessage(string $customerName, array $originalValues, array $updatedValues): string
    {
        $changes = [];

        foreach (['name', 'email', 'phone', 'company', 'status'] as $field) {
            if ($originalValues[$field] === $updatedValues[$field]) {
                continue;
            }

            $changes[] = $this->formatFieldChange($field, $updatedValues[$field]);
        }

        if ($originalValues['isVip'] !== $updatedValues['isVip']) {
            $changes[] = $updatedValues['isVip'] ? 'VIP enabled' : 'VIP disabled';
        }

        if ($changes === []) {
            return sprintf('Customer "%s" was updated.', $customerName);
        }

        $visibleChanges = array_slice($changes, 0, 3);
        $message = sprintf('Customer "%s" updated: %s', $customerName, implode(', ', $visibleChanges));

        if (count($changes) > 3) {
            $message .= sprintf(', +%d more', count($changes) - 3);
        }

        return $message . '.';
    }

    private function formatFieldChange(string $field, mixed $value): string
    {
        return match ($field) {
            'name' => sprintf('name set to "%s"', $value),
            'email' => sprintf('email set to "%s"', $value),
            'phone' => $value === null ? 'phone cleared' : sprintf('phone set to "%s"', $value),
            'company' => $value === null ? 'company cleared' : sprintf('company set to "%s"', $value),
            'status' => sprintf('status set to %s', $value),
            default => sprintf('%s updated', $field),
        };
    }
}
