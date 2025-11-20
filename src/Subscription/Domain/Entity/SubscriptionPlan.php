<?php

namespace App\Subscription\Domain\Entity;

use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\UserSubscription;
use App\Subscription\Infrastructure\Repository\SubscriptionPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionPlanRepository::class)]
#[ORM\Table(name: 'subscription_plans')]
#[ORM\Index(name: 'IDX_subscription_product', columns: ['product_id'])]
#[ORM\Index(name: 'IDX_subscription_tier', columns: ['tier_level'])]
class SubscriptionPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'subscriptionPlans')]
    #[ORM\JoinColumn(name: 'product_id', nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price;

    #[ORM\Column(type: 'string', length: 20)]
    private string $billingCycle; // monthly, yearly, one-time

    #[ORM\Column(type: 'integer')]
    private int $tierLevel = 1; // 1=Basic, 2=Pro, 3=Enterprise

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $apiCallsLimit = null; // null = unlimited

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $rateLimitPerMinute = 60;

    #[ORM\Column(type: 'json')]
    private array $allowedExchanges = ['*'];

    #[ORM\Column(type: 'json')]
    private array $allowedEndpoints = ['*'];

    #[ORM\Column(type: 'json')]
    private array $features = [];

    #[ORM\Column(type: 'json')]
    private array $restrictions = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'subscriptionPlan', targetEntity: UserSubscription::class)]
    private Collection $userSubscriptions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->userSubscriptions = new ArrayCollection();
    }

    public function hasFeature(string $featureName): bool
    {
        return in_array($featureName, $this->features);
    }

    public function canAccessEndpoint(string $endpoint): bool
    {
        if (in_array('*', $this->allowedEndpoints)) {
            return true;
        }

        return in_array($endpoint, $this->allowedEndpoints);
    }

    public function isUnlimited(): bool
    {
        return $this->apiCallsLimit === null;
    }

    public function getFormattedPrice(): string
    {
        $amount = number_format((float)$this->price, 2);
        $cycle = match ($this->billingCycle) {
            'monthly' => '/mois',
            'yearly' => '/an',
            'one-time' => ' (unique)',
            default => ''
        };

        return "$" . $amount . $cycle;
    }

    // Getters/Setters...
    public function getId(): ?int
    {
        return $this->id;
    }
    public function getProduct(): Product
    {
        return $this->product;
    }
    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
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
    public function getPrice(): string
    {
        return $this->price;
    }
    public function setPrice(string $price): self
    {
        $this->price = $price;
        return $this;
    }
    public function getBillingCycle(): string
    {
        return $this->billingCycle;
    }
    public function setBillingCycle(string $billingCycle): self
    {
        $this->billingCycle = $billingCycle;
        return $this;
    }
    public function getTierLevel(): int
    {
        return $this->tierLevel;
    }
    public function setTierLevel(int $tierLevel): self
    {
        $this->tierLevel = $tierLevel;
        return $this;
    }
    public function getApiCallsLimit(): ?int
    {
        return $this->apiCallsLimit;
    }
    public function setApiCallsLimit(?int $apiCallsLimit): self
    {
        $this->apiCallsLimit = $apiCallsLimit;
        return $this;
    }
    public function getRateLimitPerMinute(): ?int
    {
        return $this->rateLimitPerMinute;
    }
    public function setRateLimitPerMinute(?int $rateLimitPerMinute): self
    {
        $this->rateLimitPerMinute = $rateLimitPerMinute;
        return $this;
    }
    public function getAllowedExchanges(): array
    {
        return $this->allowedExchanges;
    }
    public function setAllowedExchanges(array $allowedExchanges): self
    {
        $this->allowedExchanges = $allowedExchanges;
        return $this;
    }
    public function getAllowedEndpoints(): array
    {
        return $this->allowedEndpoints;
    }
    public function setAllowedEndpoints(array $allowedEndpoints): self
    {
        $this->allowedEndpoints = $allowedEndpoints;
        return $this;
    }
    public function getFeatures(): array
    {
        return $this->features;
    }
    public function setFeatures(array $features): self
    {
        $this->features = $features;
        return $this;
    }
    public function getRestrictions(): array
    {
        return $this->restrictions;
    }
    public function setRestrictions(array $restrictions): self
    {
        $this->restrictions = $restrictions;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->isActive;
    }
    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }
    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUserSubscriptions(): Collection
    {
        return $this->userSubscriptions;
    }
}
