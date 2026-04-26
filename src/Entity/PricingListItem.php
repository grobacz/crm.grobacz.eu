<?php

namespace App\Entity;

use App\Repository\PricingListItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PricingListItemRepository::class)]
#[ORM\Table(
    name: 'pricing_list_item',
    uniqueConstraints: [new ORM\UniqueConstraint(name: 'uniq_pricing_list_inventory_item', columns: ['pricing_list_id', 'inventory_item_id'])]
)]
class PricingListItem
{
    use GeneratesUuidTrait;

    #[ORM\Id]
    #[ORM\Column(type: 'guid', unique: true)]
    private ?string $id = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $price = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $compareAtPrice = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: PricingList::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?PricingList $pricingList = null;

    #[ORM\ManyToOne(targetEntity: InventoryItem::class, inversedBy: 'pricingListItems')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?InventoryItem $inventoryItem = null;

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

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getCompareAtPrice(): ?string
    {
        return $this->compareAtPrice;
    }

    public function setCompareAtPrice(?string $compareAtPrice): self
    {
        $this->compareAtPrice = $compareAtPrice;

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

    public function getPricingList(): ?PricingList
    {
        return $this->pricingList;
    }

    public function setPricingList(?PricingList $pricingList): self
    {
        $this->pricingList = $pricingList;

        return $this;
    }

    public function getInventoryItem(): ?InventoryItem
    {
        return $this->inventoryItem;
    }

    public function setInventoryItem(?InventoryItem $inventoryItem): self
    {
        $this->inventoryItem = $inventoryItem;

        return $this;
    }
}
