<?php

namespace App\GraphQL\Resolver;

use App\Entity\CallLog;
use App\Entity\Customer;
use App\Entity\Lead;
use App\GraphQL\Exception\ValidationException;
use App\Repository\CallLogRepository;
use App\Repository\CustomerRepository;
use App\Repository\LeadRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;

class CallLogResolver
{
    private const ALLOWED_TARGET_TYPES = ['customer', 'lead'];

    public function __construct(
        private readonly CallLogRepository $callLogRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly LeadRepository $leadRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function resolveCallLogs(array $args): array
    {
        $limit = $args['limit'] ?? 25;

        return $this->callLogRepository->findRecent(max(1, min(100, (int) $limit)));
    }

    public function resolveActiveCall(): ?CallLog
    {
        return $this->callLogRepository->findActiveCall();
    }

    public function resolveStartCall(array $args): CallLog
    {
        $targetType = strtolower(trim((string) ($args['targetType'] ?? '')));
        $targetId = trim((string) ($args['targetId'] ?? ''));

        if (!in_array($targetType, self::ALLOWED_TARGET_TYPES, true)) {
            throw new ValidationException('Calls can only be started for leads or customers.');
        }

        if ($targetId === '') {
            throw new ValidationException('A target record is required to start a call.');
        }

        $activeCall = $this->callLogRepository->findActiveCall();

        if ($activeCall !== null) {
            throw new ValidationException(sprintf(
                'A call with %s "%s" is already active. Stop it before starting a new call.',
                $activeCall->getTargetType(),
                $activeCall->getTargetName()
            ));
        }

        [$targetName, $targetPhone] = $this->resolveTargetDetails($targetType, $targetId);

        $callLog = new CallLog();
        $callLog->setTargetType($targetType);
        $callLog->setTargetId($targetId);
        $callLog->setTargetName($targetName);
        $callLog->setTargetPhone($targetPhone);
        $callLog->setStatus('active');

        $this->entityManager->persist($callLog);
        $this->entityManager->flush();

        $this->activityLogger->log(
            'call',
            'started',
            $callLog->getId(),
            sprintf('Call started with %s "%s".', $targetType, $targetName)
        );

        return $callLog;
    }

    public function resolveStopCall(array $args): CallLog
    {
        $callId = trim((string) ($args['id'] ?? ''));
        $callLog = $this->callLogRepository->find($callId);

        if ($callLog === null) {
            throw new ValidationException('Call log not found.');
        }

        if (!$callLog->isActive()) {
            throw new ValidationException('This call has already been stopped.');
        }

        $endedAt = new \DateTime();
        $durationSeconds = max(0, $endedAt->getTimestamp() - $callLog->getStartedAt()->getTimestamp());

        $callLog->setEndedAt($endedAt);
        $callLog->setDurationSeconds($durationSeconds);
        $callLog->setStatus('completed');

        $this->entityManager->flush();

        $this->activityLogger->log(
            'call',
            'stopped',
            $callLog->getId(),
            sprintf(
                'Call ended with %s "%s" after %s.',
                $callLog->getTargetType(),
                $callLog->getTargetName(),
                $this->formatDuration($durationSeconds)
            )
        );

        return $callLog;
    }

    private function resolveTargetDetails(string $targetType, string $targetId): array
    {
        if ($targetType === 'customer') {
            $customer = $this->customerRepository->find($targetId);

            if (!$customer instanceof Customer) {
                throw new ValidationException('Customer not found.');
            }

            $phone = $customer->getPhone();

            if ($phone === null || $phone === '') {
                throw new ValidationException('This customer does not have a phone number.');
            }

            return [$customer->getName(), $phone];
        }

        $lead = $this->leadRepository->find($targetId);

        if (!$lead instanceof Lead) {
            throw new ValidationException('Lead not found.');
        }

        $phone = $lead->getPhone();

        if ($phone === null || $phone === '') {
            throw new ValidationException('This lead does not have a phone number.');
        }

        return [$this->describeLead($lead), $phone];
    }

    private function describeLead(Lead $lead): string
    {
        return $lead->getName() ?: $lead->getEmail() ?: $lead->getPhone() ?: 'Untitled lead';
    }

    private function formatDuration(int $durationSeconds): string
    {
        $minutes = intdiv($durationSeconds, 60);
        $seconds = $durationSeconds % 60;

        if ($minutes === 0) {
            return sprintf('%ds', $seconds);
        }

        return sprintf('%dm %02ds', $minutes, $seconds);
    }
}
