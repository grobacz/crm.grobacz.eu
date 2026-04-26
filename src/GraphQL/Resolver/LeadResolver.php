<?php

namespace App\GraphQL\Resolver;

use App\Entity\Lead;
use App\GraphQL\Exception\ValidationException;
use App\Repository\LeadRepository;
use App\Service\ActivityLogger;
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
        $name = array_key_exists('name', $args) && $args['name'] !== null
            ? trim((string) $args['name'])
            : null;
        $email = array_key_exists('email', $args) && $args['email'] !== null
            ? trim((string) $args['email'])
            : null;
        $phone = array_key_exists('phone', $args) && $args['phone'] !== null
            ? trim((string) $args['phone'])
            : null;
        $company = array_key_exists('company', $args) && $args['company'] !== null
            ? trim((string) $args['company'])
            : null;

        return [
            'name' => $name === '' ? null : $name,
            'email' => $email === null || $email === '' ? null : strtolower($email),
            'phone' => $phone === '' ? null : $phone,
            'company' => $company === '' ? null : $company,
            'status' => isset($args['status']) && $args['status'] !== null
                ? strtolower(trim((string) $args['status']))
                : 'new',
        ];
    }

    private function validateLeadInput(array $input, ?Lead $existingLead = null): void
    {
        $fieldErrors = [];

        if ($input['name'] !== null && mb_strlen($input['name']) > 255) {
            $fieldErrors['name'] = 'Lead name must be 255 characters or fewer.';
        }

        if ($input['email'] === null && $input['phone'] === null) {
            $fieldErrors['email'] = 'Provide at least an email or a phone number.';
            $fieldErrors['phone'] = 'Provide at least an email or a phone number.';
        }

        if ($input['email'] !== null) {
            if (mb_strlen($input['email']) > 255) {
                $fieldErrors['email'] = 'Email must be 255 characters or fewer.';
            } elseif (filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
                $fieldErrors['email'] = 'Enter a valid email address.';
            } elseif ($this->leadRepository->emailExistsForAnotherLead($input['email'], $existingLead?->getId())) {
                $fieldErrors['email'] = 'This email is already used by another lead.';
            }
        }

        if ($input['phone'] !== null) {
            if (mb_strlen($input['phone']) > 20) {
                $fieldErrors['phone'] = 'Phone number must be 20 characters or fewer.';
            } elseif (!preg_match('/^\+?[\d\s().-]{7,20}$/', $input['phone'])) {
                $fieldErrors['phone'] = 'Use a valid phone format with digits, spaces, parentheses, dots, or dashes.';
            }
        }

        if ($input['company'] !== null && mb_strlen($input['company']) > 255) {
            $fieldErrors['company'] = 'Company name must be 255 characters or fewer.';
        }

        if (!in_array($input['status'], self::ALLOWED_STATUSES, true)) {
            $fieldErrors['status'] = 'Lead status must be new, qualified, or converted.';
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
