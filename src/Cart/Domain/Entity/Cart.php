<?php

namespace App\Cart\Domain\Entity;

use App\Account\Domain\Entity\User;
use App\Cart\Domain\Enum\CartStatus;
use App\Discount\Domain\Entity\Discount;
use App\Cart\Infrastructure\Repository\CartRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité Cart représentant le panier d'achat.
 * Contient la collection d'items, informations utilisateur, montants totaux et réduction.
 */
#[ORM\Entity(repositoryClass: CartRepository::class)]
#[ORM\Table(name: 'carts')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['user_id', 'status'])]
#[ORM\Index(columns: ['session_id'])]
class Cart
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['cart:list', 'cart:item'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, fetch: 'LAZY')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['cart:item', 'cart:write'])]
    #[Assert\NotNull]
    private ?User $user = null;

    /** Total brut des items (avant réduction) */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['cart:list', 'cart:item'])]
    private ?string $totalAmount = null;

    /** Total net à payer (après réduction appliquée) */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['cart:list', 'cart:item'])]
    private ?string $finalAmount = null;

    /** Statut du panier (en cours, complété, annulé) */
    #[ORM\Column(type: Types::STRING, length: 20, enumType: CartStatus::class)]
    #[Groups(['cart:list', 'cart:item', 'cart:write'])]
    #[Assert\NotNull]
    private CartStatus $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['cart:list', 'cart:item'])]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['cart:item'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /** Réduction appliquée au panier, nullable */
    #[ORM\ManyToOne(targetEntity: Discount::class, fetch: 'LAZY', cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Groups(['cart:item', 'cart:write'])]
    private ?Discount $discount = null;

    /** Montant de réduction appliqué */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Groups(['cart:item'])]
    private ?string $discountAmount = null;

    /** Collection d'items dans le panier */
    #[ORM\OneToMany(
        mappedBy: 'cart',
        targetEntity: CartItem::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true,
        fetch: 'LAZY'
    )]
    #[Groups(['cart:item', 'cart:write'])]
    #[Assert\Valid]
    private Collection $items;

    /** Identifiant de session pour association non connectée */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Groups(['cart:write'])]
    #[Assert\Length(max: 255)]
    private ?string $sessionId = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->status = CartStatus::PENDING;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ===== Getters and Setters ===== //

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getTotalAmount(): ?string
    {
        return $this->totalAmount;
    }
    public function setTotalAmount(?string $totalAmount): static
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function setFinalAmount(?string $amount): void
    {
        $this->finalAmount = $amount;
    }

    public function getFinalAmount(): float
    {
        return $this->finalAmount !== null ? (float)$this->finalAmount : 0.0;
    }

    public function getStatus(): CartStatus
    {
        return $this->status;
    }
    public function setStatus(CartStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getDiscount(): ?Discount
    {
        return $this->discount;
    }
    public function setDiscount(?Discount $discount): static
    {
        $this->discount = $discount;
        return $this;
    }

    public function setDiscountAmount(?string $amount): void
    {
        $this->discountAmount = $amount;
    }

    public function getDiscountAmount(): float
    {
        return $this->discountAmount !== null ? (float)$this->discountAmount : 0.0;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(CartItem $item): static
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCart($this);
        }
        return $this;
    }

    public function removeItem(CartItem $item): static
    {
        if ($this->items->removeElement($item)) {
            if ($item->getCart() === $this) {
                $item->setCart(null);
            }
        }
        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }
    public function setSessionId(?string $sessionId): static
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    // ===== Méthodes d'interrogation d'état ===== //

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }
    public function getItemCount(): int
    {
        return $this->items->count();
    }
    public function isPending(): bool
    {
        return $this->status === CartStatus::PENDING;
    }
    public function isCompleted(): bool
    {
        return $this->status === CartStatus::COMPLETED;
    }
    public function isCancelled(): bool
    {
        return $this->status === CartStatus::CANCELLED;
    }
    public function isActive(): bool
    {
        return $this->status === CartStatus::PENDING;
    }

    /**
     * Vérifie si le panier a une réduction active et non nulle.
     */
    public function hasDiscountApplied(): bool
    {
        return $this->discount !== null
            && $this->discountAmount !== null
            && bccomp($this->discountAmount, '0', 2) > 0;
    }

    /**
     * Calcule la quantité totale d’articles dans le panier.
     */
    public function getTotalQuantity(): int
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item->getQuantity();
        }
        return $total;
    }
}
