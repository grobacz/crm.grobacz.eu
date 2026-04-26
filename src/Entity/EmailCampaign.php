<?php

namespace App\Entity;

use App\Repository\EmailCampaignRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailCampaignRepository::class)]
#[ORM\Table(name: 'email_campaign')]
class EmailCampaign
{
    use GeneratesUuidTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 160)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'string', length: 20)]
    private string $targetType;

    #[ORM\Column(type: 'string', length: 20)]
    private string $targetSegment;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'new'])]
    private string $status = 'new';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $startedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    #[ORM\OneToMany(mappedBy: 'campaign', targetEntity: EmailCampaignRecipient::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $recipients;

    public function __construct()
    {
        $this->initializeUuid();
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->recipients = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getTargetType(): string
    {
        return $this->targetType;
    }

    public function setTargetType(string $targetType): self
    {
        $this->targetType = $targetType;

        return $this;
    }

    public function getTargetSegment(): string
    {
        return $this->targetSegment;
    }

    public function setTargetSegment(string $targetSegment): self
    {
        $this->targetSegment = $targetSegment;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeInterface
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeInterface $completedAt): self
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getRecipients(): Collection
    {
        return $this->recipients;
    }

    public function addRecipient(EmailCampaignRecipient $recipient): self
    {
        if (!$this->recipients->contains($recipient)) {
            $this->recipients->add($recipient);
            $recipient->setCampaign($this);
        }

        return $this;
    }

    public function removeRecipient(EmailCampaignRecipient $recipient): self
    {
        if ($this->recipients->removeElement($recipient)) {
            if ($recipient->getCampaign() === $this) {
                $recipient->setCampaign(null);
            }
        }

        return $this;
    }

    public function getRecipientCount(): int
    {
        return $this->recipients->count();
    }

    public function getRecipientStatusCounts(): array
    {
        $counts = [
            'new' => 0,
            'sending' => 0,
            'opened' => 0,
            'completed' => 0,
        ];

        foreach ($this->recipients as $recipient) {
            $status = $recipient->getStatus();

            if (array_key_exists($status, $counts)) {
                $counts[$status]++;
            }
        }

        return $counts;
    }

    public function getProgressPercent(): int
    {
        $recipientCount = $this->getRecipientCount();

        if ($recipientCount === 0) {
            return 0;
        }

        $counts = $this->getRecipientStatusCounts();

        return (int) round(($counts['completed'] / $recipientCount) * 100);
    }
}
