<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:seed-demo-data')]
class SeedDemoDataCommand extends Command
{
    private const LOCK_KEY = 'crm_demo_data_seed';
    private const SEED_MARKER_KEY = 'demo_data_seeded_at';

    private const FIRST_NAMES = [
        'Alicia', 'Mateusz', 'Sarah', 'Jakub', 'Emma', 'Noah', 'Olivia', 'Liam', 'Maja', 'Ethan',
        'Zofia', 'Lucas', 'Nina', 'Daniel', 'Julia', 'Adam', 'Mila', 'Tomasz', 'Laura', 'Victor',
        'Hanna', 'Felix', 'Clara', 'Oskar', 'Sofia', 'Igor', 'Marta', 'Nathan', 'Elena', 'Kamil',
    ];

    private const LAST_NAMES = [
        'Nowak', 'Kowalski', 'Smith', 'Johnson', 'Brown', 'Miller', 'Garcia', 'Wilson', 'Davis', 'Taylor',
        'Lewandowski', 'Zielinski', 'Anderson', 'Martin', 'Clark', 'Walker', 'Young', 'Hall', 'Allen', 'King',
        'Wojcik', 'Kaminski', 'Scott', 'Green', 'Baker', 'Adams', 'Nelson', 'Carter', 'Mitchell', 'Perez',
    ];

    private const COMPANY_PREFIXES = [
        'Bluebird', 'Northstar', 'Brightlane', 'Summit', 'Riverstone', 'Oakline', 'Vector', 'Clearpath',
        'Silvergate', 'Redwood', 'Evergreen', 'Ironworks', 'Cloudpeak', 'Marketly', 'Finora', 'Medwise',
        'Logisphere', 'UrbanGrid', 'Novatek', 'Optima', 'Greenfield', 'BoldFrame', 'Swiftline', 'Corevia',
    ];

    private const COMPANY_SUFFIXES = [
        'Software', 'Logistics', 'Consulting', 'Retail Group', 'Manufacturing', 'Health', 'Finance',
        'Systems', 'Studio', 'Partners', 'Analytics', 'Commerce', 'Energy', 'Foods', 'Mobility',
    ];

    private const PRODUCT_NAMES = [
        'Sales Starter License', 'Sales Pro License', 'Sales Enterprise License', 'Support Desk Seat',
        'Field Sales Mobile Seat', 'Data Import Package', 'CRM Onboarding Workshop', 'Pipeline Audit',
        'Customer Success Playbook', 'Email Automation Pack', 'Lead Scoring Add-on', 'Forecasting Module',
        'Inventory Sync Connector', 'Accounting Sync Connector', 'API Access Bundle', 'SSO Configuration',
        'Advanced Reporting Pack', 'Dashboard Design Session', 'Outbound Calling Credits', 'SMS Outreach Pack',
        'Partner Portal Access', 'Quote Builder Add-on', 'Contract Template Pack', 'Renewal Management Module',
        'Dedicated Success Manager', 'Data Cleanup Sprint', 'Custom Workflow Build', 'Sandbox Environment',
        'Premium Support Plan', 'Implementation Retainer',
    ];

    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', null, InputOption::VALUE_NONE, 'Clear existing demo-owned CRM data without asking for confirmation.')
            ->addOption('if-empty', null, InputOption::VALUE_NONE, 'Seed only when core CRM data is empty and the seed marker is absent.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $force = (bool) $input->getOption('force');
        $ifEmpty = (bool) $input->getOption('if-empty');

        $this->connection->executeStatement("SELECT pg_advisory_lock(hashtext('" . self::LOCK_KEY . "'))");

        try {
            $this->ensureSeedMarkerStorage();

            if ($ifEmpty && $this->hasSeedMarker()) {
                $io->writeln('Demo data seed marker already exists. Skipping.');

                return Command::SUCCESS;
            }

            if ($ifEmpty && !$this->isCoreCrmDataEmpty()) {
                $io->writeln('Core CRM data already exists. Skipping demo data seed.');

                return Command::SUCCESS;
            }

            if (!$force && !$io->confirm('This will clear products, campaigns, leads, customers, users, and logs before seeding demo data. Continue?', false)) {
                $io->warning('Demo data seed cancelled.');

                return Command::SUCCESS;
            }

            $dataset = $this->seed();
            $this->writeSeedMarker();

            $io->success(sprintf(
                'Seeded %d users, %d products, %d leads, %d customers, %d campaigns, %d activity logs, and %d call logs.',
                count($dataset['users']),
                count($dataset['products']),
                count($dataset['leads']),
                count($dataset['customers']),
                count($dataset['campaigns']),
                $dataset['activityCount'],
                $dataset['callLogCount']
            ));

            return Command::SUCCESS;
        } finally {
            $this->connection->executeStatement("SELECT pg_advisory_unlock(hashtext('" . self::LOCK_KEY . "'))");
        }
    }

    private function seed(): array
    {
        $this->connection->beginTransaction();

        try {
            $this->clearSeededData();

            $categories = $this->createCategories();
            $pricingLists = $this->createPricingLists();
            $products = $this->createProducts($categories, $pricingLists);
            $users = $this->createUsers();
            $customers = $this->createCustomers();
            $leads = $this->createLeads($customers);
            $campaigns = $this->createCampaigns($leads, $customers);
            $logCounts = $this->createLogs($users, $customers, $leads, $products, $campaigns);

            $this->connection->commit();

            return [
                'users' => $users,
                'products' => $products,
                'leads' => $leads,
                'customers' => $customers,
                'campaigns' => $campaigns,
                'activityCount' => $logCounts['activities'],
                'callLogCount' => $logCounts['callLogs'],
            ];
        } catch (\Throwable $exception) {
            $this->connection->rollBack();

            throw $exception;
        }
    }

    private function clearSeededData(): void
    {
        $this->connection->executeStatement(<<<'SQL'
TRUNCATE TABLE
    notification,
    email_campaign_recipient,
    email_campaign,
    call_log,
    activities,
    deal,
    contact,
    customer,
    lead,
    pricing_list_item,
    pricing_list,
    inventory_item,
    category,
    app_user
RESTART IDENTITY CASCADE
SQL);
    }

    private function createCategories(): array
    {
        $definitions = [
            ['Software', 'software', 'Subscription software and user licenses.'],
            ['Services', 'services', 'Implementation, advisory, and operational services.'],
            ['Automation', 'automation', 'Workflow and outreach automation packages.'],
            ['Integrations', 'integrations', 'Connectors and API access products.'],
            ['Support', 'support', 'Support plans and success management.'],
            ['Reporting', 'reporting', 'Analytics, forecasting, and dashboard products.'],
        ];

        $categories = [];
        foreach ($definitions as $index => [$name, $slug, $description]) {
            $id = $this->uuid();
            $this->connection->insert('category', [
                'id' => $id,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'sort_order' => $index + 1,
                'created_at' => $this->dateTimeString($this->daysAgo(random_int(80, 180))),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(1, 35))),
            ]);
            $categories[] = ['id' => $id, 'name' => $name, 'slug' => $slug];
        }

        return $categories;
    }

    private function createPricingLists(): array
    {
        $definitions = [
            ['Standard', 'STD', 'USD', true, 'Published retail pricing.'],
            ['Partner', 'PARTNER', 'USD', false, 'Discounted partner channel pricing.'],
            ['Enterprise', 'ENT', 'USD', false, 'Negotiated enterprise account pricing.'],
        ];

        $pricingLists = [];
        foreach ($definitions as [$name, $code, $currency, $isDefault, $description]) {
            $id = $this->uuid();
            $this->connection->insert('pricing_list', [
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'currency' => $currency,
                'description' => $description,
                'is_default' => $isDefault ? 1 : 0,
                'is_active' => 1,
                'created_at' => $this->dateTimeString($this->daysAgo(random_int(60, 120))),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(1, 30))),
            ]);
            $pricingLists[] = ['id' => $id, 'code' => $code];
        }

        return $pricingLists;
    }

    private function createProducts(array $categories, array $pricingLists): array
    {
        $products = [];

        foreach (self::PRODUCT_NAMES as $index => $name) {
            $category = $categories[$index % count($categories)];
            $cost = random_int(1200, 75000) / 100;
            $productId = $this->uuid();

            $this->connection->insert('inventory_item', [
                'id' => $productId,
                'category_id' => $category['id'],
                'sku' => sprintf('CRM-%03d', $index + 1),
                'name' => $name,
                'description' => sprintf('%s for growing B2B teams using the CRM platform.', $name),
                'unit' => str_contains($name, 'Seat') || str_contains($name, 'License') ? 'seat' : 'package',
                'stock_quantity' => random_int(12, 240),
                'reserved_quantity' => random_int(0, 18),
                'reorder_level' => random_int(5, 25),
                'cost' => $this->money($cost),
                'is_active' => $index < 28 ? 1 : 0,
                'created_at' => $this->dateTimeString($this->daysAgo(random_int(30, 240))),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(1, 28))),
            ]);

            foreach ($pricingLists as $pricingList) {
                $multiplier = match ($pricingList['code']) {
                    'PARTNER' => 1.35,
                    'ENT' => 1.6,
                    default => 1.85,
                };
                $price = $cost * $multiplier;

                $this->connection->insert('pricing_list_item', [
                    'id' => $this->uuid(),
                    'pricing_list_id' => $pricingList['id'],
                    'inventory_item_id' => $productId,
                    'price' => $this->money($price),
                    'compare_at_price' => $pricingList['code'] === 'STD' ? $this->money($price * 1.12) : null,
                    'created_at' => $this->dateTimeString($this->daysAgo(random_int(20, 180))),
                    'updated_at' => $this->dateTimeString($this->daysAgo(random_int(1, 20))),
                ]);
            }

            $products[] = ['id' => $productId, 'name' => $name, 'category' => $category['name']];
        }

        return $products;
    }

    private function createUsers(): array
    {
        $definitions = [
            ['Anna Kowalska', 'anna.kowalska@customerhub.example', 'admin', '#2563eb'],
            ['Marek Zielinski', 'marek.zielinski@customerhub.example', 'manager', '#059669'],
            ['Sarah Johnson', 'sarah.johnson@customerhub.example', 'manager', '#7c3aed'],
            ['Daniel Nowak', 'daniel.nowak@customerhub.example', 'user', '#dc2626'],
            ['Olivia Brown', 'olivia.brown@customerhub.example', 'user', '#ea580c'],
            ['Kamil Wrobel', 'kamil.wrobel@customerhub.example', 'user', '#0891b2'],
        ];

        $users = [];
        $passwordHash = password_hash('demo123', PASSWORD_BCRYPT);

        foreach ($definitions as [$name, $email, $role, $avatarColor]) {
            $id = $this->uuid();
            $this->connection->insert('app_user', [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'password_hash' => $passwordHash,
                'role' => $role,
                'status' => 'active',
                'avatar_color' => $avatarColor,
                'created_at' => $this->dateTimeString($this->daysAgo(random_int(45, 160))),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(1, 20))),
            ]);
            $users[] = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role];
        }

        return $users;
    }

    private function createCustomers(): array
    {
        $customers = [];

        for ($index = 0; $index < 57; $index++) {
            $company = $this->companyName($index);
            $contactName = $this->personName($index);
            $customerId = $this->uuid();
            $createdAt = $this->daysAgo(random_int(8, 360));
            $status = $this->weightedValue(['active' => 72, 'prospect' => 14, 'inactive' => 9, 'churned' => 5]);

            $this->connection->insert('customer', [
                'id' => $customerId,
                'name' => $contactName,
                'email' => $this->emailFromName($contactName, $company, 'customers.example'),
                'phone' => $this->phone($index),
                'company' => $company,
                'status' => $status,
                'is_vip' => $index < 9 ? 1 : 0,
                'created_at' => $this->dateTimeString($createdAt),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(0, 30))),
            ]);

            $contactCount = random_int(1, 3);
            for ($contactIndex = 0; $contactIndex < $contactCount; $contactIndex++) {
                $name = $contactIndex === 0 ? $contactName : $this->personName($index + $contactIndex + 80);
                $this->connection->insert('contact', [
                    'id' => $this->uuid(),
                    'customer_id' => $customerId,
                    'name' => $name,
                    'email' => $this->emailFromName($name, $company, 'contacts.example'),
                    'phone' => $this->phone($index + $contactIndex + 200),
                    'title' => $this->weightedValue(['Founder' => 15, 'Operations Manager' => 25, 'Sales Director' => 24, 'Finance Lead' => 12, 'IT Manager' => 24]),
                    'is_primary' => $contactIndex === 0 ? 1 : 0,
                    'created_at' => $this->dateTimeString($createdAt->modify(sprintf('+%d days', $contactIndex))),
                ]);
            }

            foreach (range(1, random_int(1, 3)) as $dealIndex) {
                $this->connection->insert('deal', [
                    'id' => $this->uuid(),
                    'customer_id' => $customerId,
                    'title' => sprintf('%s %s', $company, $this->weightedValue(['Expansion' => 35, 'Renewal' => 28, 'Implementation' => 22, 'Pilot' => 15])),
                    'value' => $this->money(random_int(450000, 7800000) / 100),
                    'status' => $this->weightedValue(['qualified' => 18, 'proposal' => 25, 'negotiation' => 22, 'won' => 25, 'lost' => 10]),
                    'close_date' => $this->daysFromNow(random_int(-80, 120))->format('Y-m-d'),
                    'created_at' => $this->dateTimeString($this->daysAgo(random_int(1, 180))),
                ]);
            }

            $customers[] = [
                'id' => $customerId,
                'name' => $contactName,
                'email' => $this->emailFromName($contactName, $company, 'customers.example'),
                'phone' => $this->phone($index),
                'company' => $company,
                'status' => $status,
            ];
        }

        return $customers;
    }

    private function createLeads(array $customers): array
    {
        $existingCompanies = array_column($customers, 'company');
        $leads = [];

        for ($index = 0; $index < 326; $index++) {
            $name = $this->personName($index + 300);
            $company = $index % 11 === 0
                ? $existingCompanies[$index % count($existingCompanies)]
                : $this->companyName($index + 100);
            $leadId = $this->uuid();

            $this->connection->insert('lead', [
                'id' => $leadId,
                'name' => $name,
                'email' => $this->emailFromName($name, $company, 'leads.example', $index),
                'phone' => $this->phone($index + 500),
                'company' => $company,
                'status' => $this->weightedValue(['new' => 36, 'contacted' => 24, 'qualified' => 20, 'nurturing' => 12, 'lost' => 8]),
                'created_at' => $this->dateTimeString($this->daysAgo(random_int(0, 210))),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(0, 24))),
            ]);

            $leads[] = [
                'id' => $leadId,
                'name' => $name,
                'email' => $this->emailFromName($name, $company, 'leads.example', $index),
                'phone' => $this->phone($index + 500),
                'company' => $company,
            ];
        }

        return $leads;
    }

    private function createCampaigns(array $leads, array $customers): array
    {
        $definitions = [
            ['Q2 Pipeline Reactivation', 'Still planning your next CRM rollout?', 'lead', 'nurturing'],
            ['New Reporting Pack Launch', 'See your pipeline forecast earlier', 'customer', 'active'],
            ['Partner Webinar Follow-up', 'Resources from the partner revenue webinar', 'lead', 'qualified'],
            ['VIP Renewal Check-in', 'Your renewal review is ready', 'customer', 'vip'],
            ['Implementation Offer', 'Launch your team in under 30 days', 'lead', 'new'],
            ['Inventory Sync Announcement', 'Keep CRM inventory and sales in sync', 'customer', 'all'],
            ['Cold Lead Warm-up', 'A practical sales operations checklist', 'lead', 'contacted'],
            ['Customer Success Survey', 'Tell us what to improve next', 'customer', 'active'],
        ];

        $campaigns = [];
        foreach ($definitions as $index => [$name, $subject, $targetType, $targetSegment]) {
            $status = $this->weightedValue(['new' => 20, 'sending' => 30, 'completed' => 50]);
            $campaignId = $this->uuid();
            $createdAt = $this->daysAgo(random_int(2, 120));
            $completedAt = $status === 'completed' ? $this->daysAgo(random_int(0, 15)) : null;
            $startedAt = $status === 'new' ? null : $this->daysAgo(random_int(1, 30));

            $this->connection->insert('email_campaign', [
                'id' => $campaignId,
                'name' => $name,
                'subject' => $subject,
                'content' => sprintf("Hi {{ name }},\n\n%s\n\nOur team can help you turn this into a measurable CRM workflow.", $subject),
                'target_type' => $targetType,
                'target_segment' => $targetSegment,
                'status' => $status,
                'created_at' => $this->dateTimeString($createdAt),
                'updated_at' => $this->dateTimeString($this->daysAgo(random_int(0, 7))),
                'started_at' => $startedAt ? $this->dateTimeString($startedAt) : null,
                'completed_at' => $completedAt ? $this->dateTimeString($completedAt) : null,
            ]);

            $pool = $targetType === 'lead' ? $leads : $customers;
            $recipientCount = min(count($pool), random_int(32, 84));
            $offset = ($index * 37) % max(1, count($pool) - $recipientCount);
            $recipients = array_slice($pool, $offset, $recipientCount);

            foreach ($recipients as $recipientIndex => $recipient) {
                $recipientStatus = match ($status) {
                    'completed' => 'completed',
                    'sending' => $this->weightedValue(['sending' => 35, 'opened' => 45, 'completed' => 20]),
                    default => 'new',
                };
                $sentAt = $recipientStatus === 'new' ? null : $this->daysAgo(random_int(0, 20));
                $openedAt = in_array($recipientStatus, ['opened', 'completed'], true) ? $this->daysAgo(random_int(0, 12)) : null;
                $recipientCompletedAt = $recipientStatus === 'completed' ? $this->daysAgo(random_int(0, 6)) : null;

                $this->connection->insert('email_campaign_recipient', [
                    'id' => $this->uuid(),
                    'campaign_id' => $campaignId,
                    'recipient_type' => $targetType,
                    'recipient_id' => $recipient['id'],
                    'recipient_name' => $recipient['name'],
                    'recipient_email' => $recipient['email'],
                    'status' => $recipientStatus,
                    'created_at' => $this->dateTimeString($createdAt->modify(sprintf('+%d minutes', $recipientIndex))),
                    'updated_at' => $this->dateTimeString($this->daysAgo(random_int(0, 6))),
                    'sent_at' => $sentAt ? $this->dateTimeString($sentAt) : null,
                    'opened_at' => $openedAt ? $this->dateTimeString($openedAt) : null,
                    'completed_at' => $recipientCompletedAt ? $this->dateTimeString($recipientCompletedAt) : null,
                ]);
            }

            $campaigns[] = ['id' => $campaignId, 'name' => $name, 'status' => $status];
        }

        return $campaigns;
    }

    private function createLogs(array $users, array $customers, array $leads, array $products, array $campaigns): array
    {
        $activityCount = 0;
        $entities = [
            'customer' => $customers,
            'lead' => $leads,
            'inventory_item' => $products,
            'campaign' => $campaigns,
        ];

        for ($index = 0; $index < 160; $index++) {
            $entityType = array_keys($entities)[$index % count($entities)];
            $entity = $entities[$entityType][$index % count($entities[$entityType])];
            $activityId = $this->uuid();
            $action = $this->weightedValue(['created' => 26, 'updated' => 32, 'contacted' => 20, 'qualified' => 12, 'completed' => 10]);

            $this->connection->insert('activities', [
                'id' => $activityId,
                'entity_type' => $entityType,
                'action' => $action,
                'entity_id' => $entity['id'],
                'message' => $this->activityMessage($entityType, $action, $entity),
                'created_at' => $this->dateTimeString($this->daysAgo(random_int(0, 45), random_int(0, 86399))),
            ]);
            $activityCount++;

            foreach ($users as $userIndex => $user) {
                if (($index + $userIndex) % 3 !== 0) {
                    continue;
                }

                $this->connection->insert('notification', [
                    'id' => $this->uuid(),
                    'user_id' => $user['id'],
                    'activity_id' => $activityId,
                    'is_read' => random_int(1, 100) <= 68 ? 1 : 0,
                    'created_at' => $this->dateTimeString($this->daysAgo(random_int(0, 30), random_int(0, 86399))),
                ]);
            }
        }

        $callLogCount = 0;
        $callTargets = array_merge(array_slice($leads, 0, 80), array_slice($customers, 0, 30));
        foreach ($callTargets as $index => $target) {
            $startedAt = $this->daysAgo(random_int(0, 35), random_int(0, 86399));
            $duration = random_int(90, 2600);
            $status = $index % 17 === 0 ? 'missed' : 'completed';

            $this->connection->insert('call_log', [
                'id' => $this->uuid(),
                'target_type' => array_key_exists('status', $target) ? 'customer' : 'lead',
                'target_id' => $target['id'],
                'target_name' => $target['name'],
                'target_phone' => $target['phone'],
                'status' => $status,
                'started_at' => $this->dateTimeString($startedAt),
                'ended_at' => $this->dateTimeString((clone $startedAt)->modify(sprintf('+%d seconds', $duration))),
                'duration_seconds' => $status === 'missed' ? 0 : $duration,
            ]);
            $callLogCount++;
        }

        return ['activities' => $activityCount, 'callLogs' => $callLogCount];
    }

    private function ensureSeedMarkerStorage(): void
    {
        $this->connection->executeStatement(<<<'SQL'
CREATE TABLE IF NOT EXISTS app_setting (
    id UUID PRIMARY KEY,
    setting_key VARCHAR(120) UNIQUE NOT NULL,
    setting_value TEXT NOT NULL DEFAULT '',
    description TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
)
SQL);
    }

    private function hasSeedMarker(): bool
    {
        return (bool) $this->connection->fetchOne(
            'SELECT 1 FROM app_setting WHERE setting_key = ? LIMIT 1',
            [self::SEED_MARKER_KEY]
        );
    }

    private function writeSeedMarker(): void
    {
        $now = $this->dateTimeString(new \DateTimeImmutable());

        $this->connection->executeStatement(
            <<<'SQL'
INSERT INTO app_setting (id, setting_key, setting_value, description, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, ?)
ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = EXCLUDED.updated_at
SQL,
            [
                $this->uuid(),
                self::SEED_MARKER_KEY,
                $now,
                'Tracks one-time automatic demo data seeding.',
                $now,
                $now,
            ]
        );
    }

    private function isCoreCrmDataEmpty(): bool
    {
        $tables = ['customer', 'lead', 'inventory_item', 'email_campaign', 'activities', 'call_log'];

        foreach ($tables as $table) {
            if ((int) $this->connection->fetchOne(sprintf('SELECT COUNT(*) FROM %s', $table)) > 0) {
                return false;
            }
        }

        return true;
    }

    private function personName(int $index): string
    {
        return self::FIRST_NAMES[$index % count(self::FIRST_NAMES)] . ' ' . self::LAST_NAMES[($index * 7) % count(self::LAST_NAMES)];
    }

    private function companyName(int $index): string
    {
        return self::COMPANY_PREFIXES[$index % count(self::COMPANY_PREFIXES)] . ' ' . self::COMPANY_SUFFIXES[($index * 5) % count(self::COMPANY_SUFFIXES)];
    }

    private function emailFromName(string $name, string $company, string $domain, int $salt = 0): string
    {
        $namePart = strtolower((string) preg_replace('/[^a-z0-9]+/i', '.', $name));
        $companyPart = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $company));
        $suffix = $salt > 0 ? sprintf('.%d', $salt) : '';

        return trim($namePart, '.') . $suffix . '@' . $companyPart . '.' . $domain;
    }

    private function phone(int $index): string
    {
        return sprintf('+1-555-%03d-%04d', 100 + ($index % 800), 1000 + (($index * 37) % 9000));
    }

    private function weightedValue(array $weights): string
    {
        $roll = random_int(1, array_sum($weights));
        $current = 0;

        foreach ($weights as $value => $weight) {
            $current += $weight;
            if ($roll <= $current) {
                return (string) $value;
            }
        }

        return (string) array_key_first($weights);
    }

    private function activityMessage(string $entityType, string $action, array $entity): string
    {
        $label = $entity['company'] ?? $entity['name'];

        return match ($entityType) {
            'customer' => sprintf('Customer %s was %s by the sales team.', $label, $action),
            'lead' => sprintf('Lead %s at %s was %s.', $entity['name'], $entity['company'], $action),
            'inventory_item' => sprintf('Product %s was %s in the catalog.', $entity['name'], $action),
            'campaign' => sprintf('Campaign %s was %s.', $entity['name'], $action),
            default => sprintf('%s was %s.', $label, $action),
        };
    }

    private function uuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hex, 0, 8),
            substr($hex, 8, 4),
            substr($hex, 12, 4),
            substr($hex, 16, 4),
            substr($hex, 20)
        );
    }

    private function daysAgo(int $days, int $seconds = 0): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify(sprintf('-%d days -%d seconds', $days, $seconds));
    }

    private function daysFromNow(int $days): \DateTimeImmutable
    {
        return (new \DateTimeImmutable())->modify(sprintf('%+d days', $days));
    }

    private function dateTimeString(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format('Y-m-d H:i:s');
    }

    private function money(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
