<?php

namespace App\Cart\Domain\Entity;

use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Cart\Domain\ValueObject\Quantity;
use App\Cart\Infrastructure\Repository\CartItemRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant un item dans un panier.
 * Lie produit, abonnement et quantité au panier parent.
 */
#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: 'cart_items')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['cart_id'])]
#[ORM\Index(columns: ['product_id'])]
#[ORM\Index(columns: ['subscription_plan_id'])]
#[ORM\UniqueConstraint(name: 'cart_product_plan_unique', columns: ['cart_id', 'product_id', 'subscription_plan_id'])]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['cart_item:item', 'cart:item'])]
    private ?int $id = null;

    /** Panier parent contenant cet item */
    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: 'items', fetch: 'LAZY')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Groups(['cart_item:write'])]
    #[Assert\NotNull]
    private ?Cart $cart = null;

    /** Produit lié à cet item */
    #[ORM\ManyToOne(targetEntity: Product::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'product_id', nullable: false)]
    #[Groups(['cart_item:item', 'cart_item:write', 'cart:item'])]
    #[Assert\NotNull]
    private ?Product $product = null;

    /** Optionnel : abonnement lié */
    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, fetch: 'EAGER')]
    #[ORM\JoinColumn(name: 'subscription_plan_id', nullable: true)]
    #[Groups(['cart_item:item', 'cart_item:write', 'cart:item'])]
    private ?SubscriptionPlan $subscriptionPlan = null;

    /** Quantité souhaitée (>=1) */
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['cart_item:item', 'cart_item:write', 'cart:item'])]
    #[Assert\NotBlank]
    private int $quantity = 1;

    /** Prix unitaire applicable */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Groups(['cart_item:item', 'cart:item'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $unitPrice = '0.00';

    /** Options JSON arbitraires associées à cet item */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    #[Groups(['cart_item:item', 'cart_item:write', 'cart:item'])]
    private ?array $options = null;

    /** Date de création */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['cart_item:item'])]
    private \DateTimeImmutable $createdAt;

    /** Date de dernière modification */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['cart_item:item'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Avant création, capture le prix depuis l’abonnement s’il n’est pas défini explicitement.
     */
    #[ORM\PrePersist]
    public function captureProductPriceOnCreate(): void
    {
        if (bccomp($this->unitPrice, '0.00', 2) === 0) {
            if ($this->subscriptionPlan) {
                $this->unitPrice = $this->subscriptionPlan->getPrice();
            }
        }
    }

    // === Getters simples ===

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getCart(): ?Cart
    {
        return $this->cart;
    }
    public function getProduct(): ?Product
    {
        return $this->product;
    }
    public function getSubscriptionPlan(): ?SubscriptionPlan
    {
        return $this->subscriptionPlan;
    }
    public function getQuantity(): int
    {
        return $this->quantity;
    }
    public function getUnitPrice(): string
    {
        return $this->unitPrice;
    }
    public function getOptions(): ?array
    {
        return $this->options;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // === Setters avec validation via Value Object ===

    public function setCart(?Cart $cart): static
    {
        $this->cart = $cart;
        return $this;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function setSubscriptionPlan(?SubscriptionPlan $subscriptionPlan): static
    {
        $this->subscriptionPlan = $subscriptionPlan;
        return $this;
    }

    /**
     * Définit la quantité avec validation via Value Object.
     * @throws \InvalidArgumentException si la quantité n'est pas valide
     */
    public function setQuantity(int $quantity): static
    {
        $quantityVO = Quantity::fromInt($quantity);
        $this->quantity = $quantityVO->value;
        return $this;
    }

    /**
     * Définit le prix unitaire avec validation positive stricte.
     * @throws \InvalidArgumentException si <= 0
     */
    public function setUnitPrice(string $unitPrice): static
    {
        if (bccomp($unitPrice, '0.00', 2) <= 0) {
            throw new \InvalidArgumentException('Unit price must be positive');
        }
        $this->unitPrice = $unitPrice;
        return $this;
    }

    public function setOptions(?array $options): static
    {
        $this->options = $options;
        return $this;
    }

    // === Calcul du prix total pour cet item (unitaire x quantité) ===

    public function getTotalPrice(): string
    {
        return bcmul($this->unitPrice, (string)$this->quantity, 2);
    }

    // === Méthodes pour modifier quantité avec restrictions via Value Object ===

    public function increaseQuantity(int $amount = 1): static
    {
        $currentQuantity = Quantity::fromInt($this->quantity);
        $newQuantity = $currentQuantity->increase($amount);
        $this->quantity = $newQuantity->value;
        return $this;
    }

    public function decreaseQuantity(int $amount = 1): static
    {
        $currentQuantity = Quantity::fromInt($this->quantity);
        $newQuantity = $currentQuantity->decrease($amount);
        $this->quantity = $newQuantity->value;
        return $this;
    }

    public function canIncreaseQuantity(): bool
    {
        $currentQuantity = Quantity::fromInt($this->quantity);
        return !$currentQuantity->isMax();
    }

    public function canDecreaseQuantity(): bool
    {
        $currentQuantity = Quantity::fromInt($this->quantity);
        return !$currentQuantity->isMin();
    }

    // === Tests d’égalité / validité ===

    public function hasSameProduct(Product $product): bool
    {
        return $this->product && $this->product->getId() === $product->getId();
    }

    public function hasOptions(): bool
    {
        return !empty($this->options);
    }

    public function isValid(): bool
    {
        return $this->product ? $this->product->isAvailable() : false;
    }

    // === Gestion des options en table JSON ===

    public function getOption(string $key): mixed
    {
        return $this->options[$key] ?? null;
    }

    public function setOption(string $key, mixed $value): static
    {
        if ($this->options === null) {
            $this->options = [];
        }
        $this->options[$key] = $value;
        return $this;
    }

    public function removeOption(string $key): static
    {
        if ($this->options !== null) {
            unset($this->options[$key]);
            if (empty($this->options)) {
                $this->options = null;
            }
        }
        return $this;
    }
}
