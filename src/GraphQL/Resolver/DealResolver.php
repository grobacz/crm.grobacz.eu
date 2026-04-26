<?php

namespace App\GraphQL\Resolver;

use App\Entity\Deal;
use App\Repository\DealRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;

class DealResolver
{
    private DealRepository $dealRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        DealRepository $dealRepository,
        EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger
    ) {
        $this->dealRepository = $dealRepository;
        $this->entityManager = $entityManager;
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
        $deal = new Deal();
        $deal->setTitle($args['title']);
        $deal->setValue($args['value'] ?? null);
        $deal->setStatus($args['status'] ?? null);

        if (isset($args['closeDate'])) {
            $deal->setCloseDate(new \DateTime($args['closeDate']));
        }

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

        if (isset($args['title'])) {
            $deal->setTitle($args['title']);
        }
        if (isset($args['value'])) {
            $deal->setValue($args['value']);
        }
        if (isset($args['status'])) {
            $deal->setStatus($args['status']);
        }
        if (isset($args['closeDate'])) {
            $deal->setCloseDate(new \DateTime($args['closeDate']));
        }

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
}
