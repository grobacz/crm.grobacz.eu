<?php

namespace App\GraphQL\Resolver;

use App\Entity\Lead;
use App\GraphQL\Exception\ValidationException;
use App\Repository\LeadRepository;
use App\Service\ActivityLogger;
use App\Service\InputValidator;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class LeadResolver
{
    private const ALLOWED_STATUSES = ['new', 'qualified', 'converted'];

    public function __construct(
        private readonly LeadRepository $leadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function resolveLeads(): array
    {
        return $this->leadRepository->findAll();
    }

    public function resolveLead(array $args): ?Lead
    {
        return $this->leadRepository->find($args['id']);
    }

    public function resolveLeadCount(): int
    {
        return $this->leadRepository->count([]);
    }

    public function resolveCreateLead(array $args): Lead
    {
        $input = $this->normalizeLeadInput($args);
        $this->validateLeadInput($input);

        $lead = new Lead();
        $lead->setName($input['name']);
        $lead->setEmail($input['email']);
        $lead->setPhone($input['phone']);
        $lead->setCompany($input['company']);
        $lead->setStatus($input['status']);

        try {
            $this->entityManager->persist($lead);
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['email'], $exception);
        }

        $this->activityLogger->log(
            'lead',
            'created',
            $lead->getId(),
            sprintf('Lead "%s" was created.', $this->describeLead($lead))
        );

        return $lead;
    }

    public function resolveUpdateLead(array $args): Lead
    {
        $lead = $this->leadRepository->find($args['id']);

        if (!$lead) {
            throw new \Exception('Lead not found');
        }

        $originalValues = [
            'name' => $lead->getName(),
            'email' => $lead->getEmail(),
            'phone' => $lead->getPhone(),
            'company' => $lead->getCompany(),
            'status' => $lead->getStatus(),
        ];

        $input = $this->normalizeLeadInput([
            'name' => array_key_exists('name', $args) ? $args['name'] : $lead->getName(),
            'email' => array_key_exists('email', $args) ? $args['email'] : $lead->getEmail(),
            'phone' => array_key_exists('phone', $args) ? $args['phone'] : $lead->getPhone(),
            'company' => array_key_exists('company', $args) ? $args['company'] : $lead->getCompany(),
            'status' => array_key_exists('status', $args) ? $args['status'] : $lead->getStatus(),
        ]);

        $this->validateLeadInput($input, $lead);

        $lead->setName($input['name']);
        $lead->setEmail($input['email']);
        $lead->setPhone($input['phone']);
        $lead->setCompany($input['company']);
        $lead->setStatus($input['status']);
        $lead->setUpdatedAt(new \DateTime());

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw $this->createConflictException($input['email'], $exception);
        }

        $this->activityLogger->log(
            'lead',
            'updated',
            $lead->getId(),
            $this->buildLeadUpdateMessage($this->describeLead($lead), $originalValues, $input)
        );

        return $lead;
    }

    public function resolveDeleteLead(array $args): bool
    {
        $lead = $this->leadRepository->find($args['id']);

        if (!$lead) {
            return false;
        }

        $leadId = $lead->getId();
        $leadLabel = $this->describeLead($lead);

        $this->entityManager->remove($lead);
        $this->entityManager->flush();

        $this->activityLogger->log(
            'lead',
            'deleted',
            $leadId,
            sprintf('Lead "%s" was deleted.', $leadLabel)
        );

        return true;
    }

    private function normalizeLeadInput(array $args): array
    {
        return [
            'name' => InputValidator::trimString($args['name'] ?? null),
            'email' => InputValidator::normalizeEmail($args['email'] ?? null),
            'phone' => InputValidator::trimString($args['phone'] ?? null),
            'company' => InputValidator::trimString($args['company'] ?? null),
            'status' => isset($args['status']) && $args['status'] !== null
                ? strtolower(InputValidator::trimString($args['status']) ?? '')
                : 'new',
        ];
    }

    private function validateLeadInput(array $input, ?Lead $existingLead = null): void
    {
        $fieldErrors = [];

        $nameError = InputValidator::validateName($input['name'], false);
        if ($nameError !== null) {
            $fieldErrors['name'] = 'Lead ' . lcfirst($nameError);
        }

        if ($input['email'] === null && $input['phone'] === null) {
            $fieldErrors['email'] = 'Provide at least an email or a phone number.';
            $fieldErrors['phone'] = 'Provide at least an email or a phone number.';
        }

        if ($input['email'] !== null) {
            $emailError = InputValidator::validateEmail($input['email'], true);
            if ($emailError !== null) {
                $fieldErrors['email'] = $emailError;
            } elseif ($this->leadRepository->emailExistsForAnotherLead($input['email'], $existingLead?->getId())) {
                $fieldErrors['email'] = 'This email is already used by another lead.';
            }
        }

        if ($input['phone'] !== null) {
            $phoneError = InputValidator::validatePhone($input['phone'], false);
            if ($phoneError !== null) {
                $fieldErrors['phone'] = $phoneError;
            }
        }

        $companyError = InputValidator::validateCompany($input['company']);
        if ($companyError !== null) {
            $fieldErrors['company'] = $companyError;
        }

        $statusError = InputValidator::validateStatus($input['status'], self::ALLOWED_STATUSES);
        if ($statusError !== null) {
            $fieldErrors['status'] = 'Lead ' . lcfirst($statusError);
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted lead fields.',
                $fieldErrors
            );
        }
    }

    private function createConflictException(?string $email, UniqueConstraintViolationException $exception): ValidationException
    {
        if ($email !== null && $this->leadRepository->findOneByEmailInsensitive($email) !== null) {
            return new ValidationException(
                'A lead with this email already exists.',
                [
                    'email' => 'This email is already used by another lead.',
                ],
                'conflict'
            );
        }

        return new ValidationException(
            'Unable to save the lead because the submitted data conflicts with an existing record.',
            [],
            'conflict'
        );
    }

    private function buildLeadUpdateMessage(string $leadLabel, array $originalValues, array $updatedValues): string
    {
        $changes = [];

        foreach (['name', 'email', 'phone', 'company', 'status'] as $field) {
            if ($originalValues[$field] === $updatedValues[$field]) {
                continue;
            }

            $changes[] = $this->formatFieldChange($field, $updatedValues[$field]);
        }

        if ($changes === []) {
            return sprintf('Lead "%s" was updated.', $leadLabel);
        }

        $visibleChanges = array_slice($changes, 0, 3);
        $message = sprintf('Lead "%s" updated: %s', $leadLabel, implode(', ', $visibleChanges));

        if (count($changes) > 3) {
            $message .= sprintf(', +%d more', count($changes) - 3);
        }

        return $message . '.';
    }

    private function formatFieldChange(string $field, mixed $value): string
    {
        return match ($field) {
            'name' => $value === null ? 'name cleared' : sprintf('name set to "%s"', $value),
            'email' => $value === null ? 'email cleared' : sprintf('email set to "%s"', $value),
            'phone' => $value === null ? 'phone cleared' : sprintf('phone set to "%s"', $value),
            'company' => $value === null ? 'company cleared' : sprintf('company set to "%s"', $value),
            'status' => sprintf('status set to %s', $value),
            default => sprintf('%s updated', $field),
        };
    }

    private function describeLead(Lead $lead): string
    {
        return $lead->getName() ?: $lead->getEmail() ?: $lead->getPhone() ?: 'Untitled lead';
    }
}
