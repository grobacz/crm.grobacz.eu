<?php

namespace App\GraphQL\Resolver;

use App\Entity\Deal;
use App\GraphQL\Exception\ValidationException;
use App\Repository\DealRepository;
use App\Service\ActivityLogger;
use App\Service\InputValidator;
use Doctrine\ORM\EntityManagerInterface;

class DealResolver
{
    private const ALLOWED_STATUSES = ['open', 'won', 'lost', 'pending'];

    public function __construct(
        private readonly DealRepository $dealRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger
    ) {
    }

    public function resolveDeals(): array
    {
        return $this->dealRepository->findAll();
    }

    public function resolveDeal(array $args): ?Deal
    {
        return $this->dealRepository->find($args['id']);
    }

    public function resolveDealCount(): int
    {
        return $this->dealRepository->count([]);
    }

    public function resolveCreateDeal(array $args): Deal
    {
        $input = $this->normalizeDealInput($args);
        $this->validateDealInput($input);

        $deal = new Deal();
        $this->applyDealInput($deal, $input);

        $customer = $this->entityManager->getRepository(\App\Entity\Customer::class)->find($args['customerId']);
        if (!$customer) {
            throw new \Exception('Customer not found');
        }
        $deal->setCustomer($customer);

        $this->entityManager->persist($deal);
        $this->entityManager->flush();
        $this->activityLogger->log(
            'deal',
            'created',
            $deal->getId(),
            sprintf('Deal "%s" was created for customer "%s".', $deal->getTitle(), $customer->getName())
        );

        return $deal;
    }

    public function resolveUpdateDeal(array $args): Deal
    {
        $deal = $this->dealRepository->find($args['id']);

        if (!$deal) {
            throw new \Exception('Deal not found');
        }

        $input = $this->normalizeDealInput([
            'title' => $args['title'] ?? $deal->getTitle(),
            'value' => $args['value'] ?? $deal->getValue(),
            'status' => $args['status'] ?? $deal->getStatus(),
            'closeDate' => $args['closeDate'] ?? ($deal->getCloseDate()?->format('Y-m-d')),
        ]);

        $this->validateDealInput($input);
        $this->applyDealInput($deal, $input);

        $this->entityManager->flush();
        $this->activityLogger->log(
            'deal',
            'updated',
            $deal->getId(),
            sprintf('Deal "%s" was updated.', $deal->getTitle())
        );

        return $deal;
    }

    public function resolveDeleteDeal(array $args): bool
    {
        $deal = $this->dealRepository->find($args['id']);

        if (!$deal) {
            return false;
        }

        $dealId = $deal->getId();
        $dealTitle = $deal->getTitle();

        $this->entityManager->remove($deal);
        $this->entityManager->flush();
        $this->activityLogger->log(
            'deal',
            'deleted',
            $dealId,
            sprintf('Deal "%s" was deleted.', $dealTitle)
        );

        return true;
    }

    private function normalizeDealInput(array $args): array
    {
        $title = InputValidator::trimString($args['title'] ?? null) ?? '';
        $status = InputValidator::trimString($args['status'] ?? null);

        return [
            'title' => $title,
            'value' => isset($args['value']) && $args['value'] !== null ? (string) $args['value'] : null,
            'status' => $status === '' ? null : $status,
            'closeDate' => InputValidator::trimString($args['closeDate'] ?? null),
        ];
    }

    private function validateDealInput(array $input): void
    {
        $fieldErrors = [];

        $nameError = InputValidator::validateName($input['title'], true);
        if ($nameError !== null) {
            $fieldErrors['title'] = 'Deal ' . lcfirst($nameError);
        }

        if ($input['status'] !== null) {
            $statusError = InputValidator::validateStatus($input['status'], self::ALLOWED_STATUSES);
            if ($statusError !== null) {
                $fieldErrors['status'] = 'Deal ' . lcfirst($statusError);
            }
        }

        if ($input['closeDate'] !== null) {
            $date = \DateTime::createFromFormat('Y-m-d', $input['closeDate']);
            if ($date === false || $date->format('Y-m-d') !== $input['closeDate']) {
                $fieldErrors['closeDate'] = 'Close date must be a valid date in YYYY-MM-DD format.';
            }
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted deal fields.',
                $fieldErrors
            );
        }
    }

    private function applyDealInput(Deal $deal, array $input): void
    {
        $deal->setTitle($input['title']);
        $deal->setValue($input['value']);
        $deal->setStatus($input['status']);

        if ($input['closeDate'] !== null) {
            $deal->setCloseDate(new \DateTime($input['closeDate']));
        } else {
            $deal->setCloseDate(null);
        }
    }
}
