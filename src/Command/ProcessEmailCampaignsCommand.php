<?php

namespace App\Command;

use App\Entity\EmailCampaign;
use App\Entity\EmailCampaignRecipient;
use App\Repository\EmailCampaignRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:process-email-campaigns')]
class ProcessEmailCampaignsCommand extends Command
{
    public function __construct(
        private readonly EmailCampaignRepository $emailCampaignRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ActivityLogger $activityLogger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('limit', null, InputOption::VALUE_REQUIRED, 'Maximum number of campaigns to process per run.', '10');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = max(1, (int) $input->getOption('limit'));
        $campaigns = $this->emailCampaignRepository->findProcessable($limit);

        if ($campaigns === []) {
            $io->writeln('No email campaigns ready for processing.');

            return Command::SUCCESS;
        }

        foreach ($campaigns as $campaign) {
            $this->processCampaign($campaign);
        }

        $this->entityManager->flush();
        $io->success(sprintf('Processed %d email campaign%s.', count($campaigns), count($campaigns) === 1 ? '' : 's'));

        return Command::SUCCESS;
    }

    private function processCampaign(EmailCampaign $campaign): void
    {
        $now = new \DateTime();

        if ($campaign->getStatus() === 'new') {
            $campaign->setStatus('sending');
            $campaign->setStartedAt($now);
        }

        $campaign->setUpdatedAt($now);

        $statusBuckets = [
            'new' => [],
            'sending' => [],
            'opened' => [],
        ];

        foreach ($campaign->getRecipients() as $recipient) {
            $status = $recipient->getStatus();

            if (array_key_exists($status, $statusBuckets)) {
                $statusBuckets[$status][] = $recipient;
            }
        }

        $this->transitionRecipients(array_slice($statusBuckets['new'], 0, 4), 'sending', function (EmailCampaignRecipient $recipient, \DateTimeInterface $currentTime): void {
            $recipient->setSentAt($currentTime);
        }, $now);

        $this->transitionRecipients(array_slice($statusBuckets['sending'], 0, 3), 'opened', function (EmailCampaignRecipient $recipient, \DateTimeInterface $currentTime): void {
            $recipient->setOpenedAt($currentTime);
        }, $now);

        $this->transitionRecipients(array_slice($statusBuckets['opened'], 0, 2), 'completed', function (EmailCampaignRecipient $recipient, \DateTimeInterface $currentTime): void {
            $recipient->setCompletedAt($currentTime);
        }, $now);

        $counts = $campaign->getRecipientStatusCounts();

        if ($counts['new'] === 0 && $counts['sending'] === 0 && $counts['opened'] === 0) {
            if ($campaign->getStatus() !== 'completed') {
                $campaign->setStatus('completed');
                $campaign->setCompletedAt($now);
                $campaign->setUpdatedAt($now);

                $this->activityLogger->log(
                    'campaign',
                    'completed',
                    $campaign->getId(),
                    sprintf(
                        'Email campaign "%s" completed for %d recipients.',
                        $campaign->getName(),
                        $campaign->getRecipientCount()
                    )
                );
            }

            return;
        }

        $campaign->setStatus('sending');
    }

    private function transitionRecipients(array $recipients, string $nextStatus, callable $callback, \DateTimeInterface $currentTime): void
    {
        foreach ($recipients as $recipient) {
            $recipient->setStatus($nextStatus);
            $recipient->setUpdatedAt($currentTime);
            $callback($recipient, $currentTime);
        }
    }
}
