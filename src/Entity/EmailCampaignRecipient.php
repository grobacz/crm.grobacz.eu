<?php

namespace App\Entity;

use App\Repository\EmailCampaignRecipientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EmailCampaignRecipientRepository::class)]
#[ORM\Table(name: 'email_campaign_recipient')]
class EmailCampaignRecipient
{
    use GeneratesUuidTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: EmailCampaign::class, inversedBy: 'recipients')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?EmailCampaign $campaign = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $recipientType;

    #[ORM\Column(type: 'guid')]
    private string $recipientId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipientName;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipientEmail;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'new'])]
    private string $status = 'new';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $sentAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $openedAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $completedAt = null;

    public function __construct()
    {
        $this->initializeUuid();
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getCampaign(): ?EmailCampaign
    {
        return $this->campaign;
    }

    public function setCampaign(?EmailCampaign $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    public function getRecipientType(): string
    {
        return $this->recipientType;
    }

    public function setRecipientType(string $recipientType): self
    {
        $this->recipientType = $recipientType;

        return $this;
    }

    public function getRecipientId(): string
    {
        return $this->recipientId;
    }

    public function setRecipientId(string $recipientId): self
    {
        $this->recipientId = $recipientId;

        return $this;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): self
    {
        $this->recipientName = $recipientName;

        return $this;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): self
    {
        $this->recipientEmail = $recipientEmail;

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

    public function getSentAt(): ?\DateTimeInterface
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeInterface $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getOpenedAt(): ?\DateTimeInterface
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?\DateTimeInterface $openedAt): self
    {
        $this->openedAt = $openedAt;

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
}
