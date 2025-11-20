<?php

namespace App\Subscription\Domain\Entity;

use App\Repository\UserSubscriptionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Account\Domain\Entity\User;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\ApiUsage\Domain\Entity\ApiUsage;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSubscriptionRepository::class)]
#[ORM\Table(name: 'user_subscriptions')]
#[ORM\Index(name: 'IDX_user_subscription_status', columns: ['status'])]
#[ORM\Index(name: 'IDX_user_subscription_user', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_user_subscription_expires', columns: ['expires_at'])]
class UserSubscription
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_PENDING = 'pending';
    public const STATUS_SUSPENDED = 'suspended';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: SubscriptionPlan::class, inversedBy: 'userSubscriptions')]
    #[ORM\JoinColumn(name: 'subscription_plan_id', nullable: false, onDelete: 'CASCADE')]
    private SubscriptionPlan $subscriptionPlan;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $stripeSubscriptionId = null;

    #[ORM\Column(type: 'json')]
    private array $currentLimits = [];

    #[ORM\Column(type: 'json')]
    private array $customFeatures = [];

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'userSubscription', targetEntity: ApiUsage::class)]
    private Collection $apiUsages;

    #[ORM\OneToMany(mappedBy: 'userSubscription', targetEntity: SubscriptionUsage::class)]
    private Collection $usageRecords;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startedAt = new \DateTimeImmutable();
        $this->apiUsages = new ArrayCollection();
        $this->usageRecords = new ArrayCollection();
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               ($this->expiresAt === null || $this->expiresAt > new \DateTimeImmutable());
    }

    public function getRemainingApiCalls(): int
    {
        $limit = $this->getApiCallsLimit();
        if ($limit === null) {
            return PHP_INT_MAX; 
        }

        $used = $this->getCurrentMonthUsage();
        return max(0, $limit - $used);
    }

    public function getApiCallsLimit(): ?int
    {
        return $this->currentLimits['api_calls_per_month'] ?? 
               $this->subscriptionPlan->getApiCallsLimit();
    }

    public function getCurrentMonthUsage(): int
    {
        $startOfMonth = new \DateTimeImmutable('first day of this month 00:00:00');
        $endOfMonth = new \DateTimeImmutable('last day of this month 23:59:59');

        $total = 0;
        foreach ($this->apiUsages as $usage) {
            if ($usage->getCreatedAt() >= $startOfMonth && $usage->getCreatedAt() <= $endOfMonth) {
                $total += $usage->getCallsCount();
            }
        }

        return $total;
    }

    public function hasFeatureAccess(string $featureName): bool
    {
        // Vérifier les features personnalisées
        if (in_array($featureName, $this->customFeatures)) {
            return true;
        }

        // Vérifier les features du plan
        return $this->subscriptionPlan->hasFeature($featureName);
    }

    public function canAccessExchange(string $exchangeName): bool
    {
        $allowedExchanges = $this->currentLimits['allowed_exchanges'] ?? 
                           $this->subscriptionPlan->getAllowedExchanges();

        return in_array($exchangeName, $allowedExchanges) || in_array('*', $allowedExchanges);
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): self { $this->user = $user; return $this; }
    public function getSubscriptionPlan(): SubscriptionPlan { return $this->subscriptionPlan; }
    public function setSubscriptionPlan(SubscriptionPlan $subscriptionPlan): self { $this->subscriptionPlan = $subscriptionPlan; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getCurrentLimits(): array { return $this->currentLimits; }
    public function setCurrentLimits(array $currentLimits): self { $this->currentLimits = $currentLimits; return $this; }
    public function getCustomFeatures(): array { return $this->customFeatures; }
    public function setCustomFeatures(array $customFeatures): self { $this->customFeatures = $customFeatures; return $this; }
}
