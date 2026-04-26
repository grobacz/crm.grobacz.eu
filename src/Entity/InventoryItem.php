<?php

namespace App\Entity;

use App\Repository\InventoryItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InventoryItemRepository::class)]
#[ORM\Table(name: 'inventory_item')]
class InventoryItem
{
    use GeneratesUuidTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'string', length: 120, unique: true)]
    private ?string $sku = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'unit'])]
    private string $unit = 'unit';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $stockQuantity = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $reservedQuantity = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private int $reorderLevel = 0;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $cost = null;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'inventoryItems')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\OneToMany(targetEntity: PricingListItem::class, mappedBy: 'inventoryItem', orphanRemoval: true)]
    private Collection $pricingListItems;

    public function __construct()
    {
        $this->initializeUuid();
        $now = new \DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->pricingListItems = new ArrayCollection();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(string $sku): self
    {
        $this->sku = $sku;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUnit(): string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getStockQuantity(): int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): self
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getReservedQuantity(): int
    {
        return $this->reservedQuantity;
    }

    public function setReservedQuantity(int $reservedQuantity): self
    {
        $this->reservedQuantity = $reservedQuantity;

        return $this;
    }

    public function getAvailableQuantity(): int
    {
        return max(0, $this->stockQuantity - $this->reservedQuantity);
    }

    public function getReorderLevel(): int
    {
        return $this->reorderLevel;
    }

    public function setReorderLevel(int $reorderLevel): self
    {
        $this->reorderLevel = $reorderLevel;

        return $this;
    }

    public function getCost(): ?string
    {
        return $this->cost;
    }

    public function setCost(?string $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getPricingListItems(): Collection
    {
        return $this->pricingListItems;
    }

    public function addPricingListItem(PricingListItem $pricingListItem): self
    {
        if (!$this->pricingListItems->contains($pricingListItem)) {
            $this->pricingListItems->add($pricingListItem);
            $pricingListItem->setInventoryItem($this);
        }

        return $this;
    }

    public function removePricingListItem(PricingListItem $pricingListItem): self
    {
        if ($this->pricingListItems->removeElement($pricingListItem) && $pricingListItem->getInventoryItem() === $this) {
            $pricingListItem->setInventoryItem(null);
        }

        return $this;
    }
}
