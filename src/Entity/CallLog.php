<?php

namespace App\Entity;

use App\Repository\CallLogRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallLogRepository::class)]
#[ORM\Table(name: 'call_log')]
class CallLog
{
    use GeneratesUuidTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $targetType;

    #[ORM\Column(type: 'guid')]
    private string $targetId;

    #[ORM\Column(type: 'string', length: 255)]
    private string $targetName;

    #[ORM\Column(type: 'string', length: 20)]
    private string $targetPhone;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'active'])]
    private string $status = 'active';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $startedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $endedAt = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $durationSeconds = null;

    public function __construct()
    {
        $this->initializeUuid();
        $this->startedAt = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
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

    public function getTargetId(): string
    {
        return $this->targetId;
    }

    public function setTargetId(string $targetId): self
    {
        $this->targetId = $targetId;

        return $this;
    }

    public function getTargetName(): string
    {
        return $this->targetName;
    }

    public function setTargetName(string $targetName): self
    {
        $this->targetName = $targetName;

        return $this;
    }

    public function getTargetPhone(): string
    {
        return $this->targetPhone;
    }

    public function setTargetPhone(string $targetPhone): self
    {
        $this->targetPhone = $targetPhone;

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

    public function getStartedAt(): \DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeInterface $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getEndedAt(): ?\DateTimeInterface
    {
        return $this->endedAt;
    }

    public function setEndedAt(?\DateTimeInterface $endedAt): self
    {
        $this->endedAt = $endedAt;

        return $this;
    }

    public function getDurationSeconds(): ?int
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(?int $durationSeconds): self
    {
        $this->durationSeconds = $durationSeconds;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->endedAt === null;
    }
}
