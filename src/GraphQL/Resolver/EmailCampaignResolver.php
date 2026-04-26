<?php

namespace App\GraphQL\Resolver;

use App\Entity\Customer;
use App\Entity\EmailCampaign;
use App\Entity\EmailCampaignRecipient;
use App\Entity\Lead;
use App\GraphQL\Exception\ValidationException;
use App\Repository\CustomerRepository;
use App\Repository\EmailCampaignRepository;
use App\Repository\LeadRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;

class EmailCampaignResolver
{
    private const LEAD_SEGMENTS = ['new', 'qualified', 'converted'];
    private const CUSTOMER_SEGMENTS = ['active', 'inactive', 'vip'];

    public function __construct(
        private readonly EmailCampaignRepository $emailCampaignRepository,
        private readonly LeadRepository $leadRepository,
        private readonly CustomerRepository $customerRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
    }

    public function resolveEmailCampaigns(array $args): array
    {
        $limit = $args['limit'] ?? 20;

        return $this->emailCampaignRepository->findRecent(max(1, min(50, (int) $limit)));
    }

    public function resolveEmailCampaign(array $args): ?EmailCampaign
    {
        return $this->emailCampaignRepository->find($args['id']);
    }

    public function resolveCreateEmailCampaign(array $args): EmailCampaign
    {
        $input = $this->normalizeCampaignInput($args);
        $targets = $this->resolveTargets($input['targetType'], $input['targetSegment']);

        if ($targets === []) {
            throw new ValidationException(
                'No recipients matched the selected audience.',
                [
                    'targetSegment' => 'No recipients matched the selected audience.',
                ]
            );
        }

        $campaign = new EmailCampaign();
        $campaign->setName($input['name']);
        $campaign->setSubject($input['subject']);
        $campaign->setContent($input['content']);
        $campaign->setTargetType($input['targetType']);
        $campaign->setTargetSegment($input['targetSegment']);
        $campaign->setStatus('new');
        $this->entityManager->persist($campaign);

        foreach ($targets as $target) {
            $recipient = new EmailCampaignRecipient();
            $recipient->setRecipientType($input['targetType']);
            $recipient->setRecipientId($target['id']);
            $recipient->setRecipientName($target['name']);
            $recipient->setRecipientEmail($target['email']);
            $recipient->setStatus('new');
            $campaign->addRecipient($recipient);
            $this->entityManager->persist($recipient);
        }

        $this->entityManager->flush();

        $this->activityLogger->log(
            'campaign',
            'created',
            $campaign->getId(),
            sprintf(
                'Email campaign "%s" was created for %d %s recipient%s.',
                $campaign->getName(),
                $campaign->getRecipientCount(),
                $campaign->getTargetType(),
                $campaign->getRecipientCount() === 1 ? '' : 's'
            )
        );

        return $campaign;
    }

    private function normalizeCampaignInput(array $args): array
    {
        $name = trim((string) ($args['name'] ?? ''));
        $subject = trim((string) ($args['subject'] ?? ''));
        $content = trim((string) ($args['content'] ?? ''));
        $targetType = strtolower(trim((string) ($args['targetType'] ?? '')));
        $targetSegment = strtolower(trim((string) ($args['targetSegment'] ?? '')));

        $fieldErrors = [];

        if ($name === '') {
            $fieldErrors['name'] = 'Campaign name is required.';
        } elseif (mb_strlen($name) > 160) {
            $fieldErrors['name'] = 'Campaign name must be 160 characters or fewer.';
        }

        if ($subject === '') {
            $fieldErrors['subject'] = 'Email subject is required.';
        } elseif (mb_strlen($subject) > 255) {
            $fieldErrors['subject'] = 'Email subject must be 255 characters or fewer.';
        }

        if ($content === '') {
            $fieldErrors['content'] = 'Email content is required.';
        }

        if (!in_array($targetType, ['lead', 'customer'], true)) {
            $fieldErrors['targetType'] = 'Target type must be lead or customer.';
        } else {
            $allowedSegments = $targetType === 'lead' ? self::LEAD_SEGMENTS : self::CUSTOMER_SEGMENTS;

            if (!in_array($targetSegment, $allowedSegments, true)) {
                $fieldErrors['targetSegment'] = 'Selected target segment is not available for this audience.';
            }
        }

        if ($fieldErrors !== []) {
            throw new ValidationException(
                'Please correct the highlighted campaign fields.',
                $fieldErrors
            );
        }

        return [
            'name' => $name,
            'subject' => $subject,
            'content' => $content,
            'targetType' => $targetType,
            'targetSegment' => $targetSegment,
        ];
    }

    private function resolveTargets(string $targetType, string $targetSegment): array
    {
        if ($targetType === 'lead') {
            return array_map(
                fn(Lead $lead) => [
                    'id' => $lead->getId(),
                    'name' => $lead->getName() ?: $lead->getEmail() ?: 'Untitled lead',
                    'email' => (string) $lead->getEmail(),
                ],
                $this->leadRepository->findByCampaignSegment($targetSegment)
            );
        }

        return array_map(
            fn(Customer $customer) => [
                'id' => $customer->getId(),
                'name' => $customer->getName(),
                'email' => (string) $customer->getEmail(),
            ],
            $this->customerRepository->findByCampaignSegment($targetSegment)
        );
    }
}
