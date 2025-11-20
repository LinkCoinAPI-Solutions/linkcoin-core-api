<?php

namespace App\Subscription\Domain\Entity;

use App\Repository\SubscriptionUsageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubscriptionUsageRepository::class)]
#[ORM\Table(name: 'subscription_usage')]
#[ORM\Index(name: 'IDX_subscription_usage_subscription', columns: ['user_subscription_id'])]
#[ORM\Index(name: 'IDX_subscription_usage_period', columns: ['period_start', 'period_end'])]
#[ORM\Index(name: 'IDX_subscription_usage_date', columns: ['usage_date'])]
#[ORM\UniqueConstraint(name: 'UNIQ_subscription_usage_period', columns: ['user_subscription_id', 'usage_date', 'period_type'])]
class SubscriptionUsage
{
    public const PERIOD_DAILY = 'daily';
    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_WEEKLY = 'weekly';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserSubscription::class, inversedBy: 'usageRecords')]
    #[ORM\JoinColumn(name: 'user_subscription_id', nullable: false, onDelete: 'CASCADE')]
    private UserSubscription $userSubscription;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $usageDate; // Date de la période (ex: 2025-09-30)

    #[ORM\Column(type: 'string', length: 20)]
    private string $periodType = self::PERIOD_DAILY; // daily, weekly, monthly

    #[ORM\Column(type: 'datetime')]
    private \DateTime $periodStart;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $periodEnd;

    #[ORM\Column(type: 'integer')]
    private int $totalApiCalls = 0;

    #[ORM\Column(type: 'integer')]
    private int $successfulCalls = 0;

    #[ORM\Column(type: 'integer')]
    private int $failedCalls = 0;

    #[ORM\Column(type: 'integer')]
    private int $rateLimitedCalls = 0; // Calls avec 429

    #[ORM\Column(type: 'json')]
    private array $endpointUsage = []; // {"price": 100, "market_cap": 50}

    #[ORM\Column(type: 'json')]
    private array $exchangeUsage = []; // {"binance": 80, "coinbase": 70}

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $averageResponseTime = null; // en ms

    #[ORM\Column(type: 'bigint')]
    private int $totalDataTransferred = 0; // en bytes

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $errorRate = null; // pourcentage d'erreur

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $peakUsageHour = null; // {"hour": 14, "calls": 50}

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    
    public function getUserSubscription(): UserSubscription { return $this->userSubscription; }
    public function setUserSubscription(UserSubscription $userSubscription): self { $this->userSubscription = $userSubscription; return $this; }
    
    public function getUsageDate(): \DateTimeInterface { return $this->usageDate; }
    public function setUsageDate(\DateTimeInterface $usageDate): self { $this->usageDate = $usageDate; return $this; }
    
    public function getPeriodType(): string { return $this->periodType; }
    public function setPeriodType(string $periodType): self { $this->periodType = $periodType; return $this; }
    
    public function getPeriodStart(): \DateTime { return $this->periodStart; }
    public function setPeriodStart(\DateTime $periodStart): self { $this->periodStart = $periodStart; return $this; }
    
    public function getPeriodEnd(): \DateTime { return $this->periodEnd; }
    public function setPeriodEnd(\DateTime $periodEnd): self { $this->periodEnd = $periodEnd; return $this; }
    
    public function getTotalApiCalls(): int { return $this->totalApiCalls; }
    public function setTotalApiCalls(int $totalApiCalls): self { $this->totalApiCalls = $totalApiCalls; return $this; }
    
    public function getSuccessfulCalls(): int { return $this->successfulCalls; }
    public function setSuccessfulCalls(int $successfulCalls): self { $this->successfulCalls = $successfulCalls; return $this; }
    
    public function getFailedCalls(): int { return $this->failedCalls; }
    public function setFailedCalls(int $failedCalls): self { $this->failedCalls = $failedCalls; return $this; }
    
    public function getRateLimitedCalls(): int { return $this->rateLimitedCalls; }
    public function setRateLimitedCalls(int $rateLimitedCalls): self { $this->rateLimitedCalls = $rateLimitedCalls; return $this; }
    
    public function getEndpointUsage(): array { return $this->endpointUsage; }
    public function setEndpointUsage(array $endpointUsage): self { $this->endpointUsage = $endpointUsage; return $this; }
    
    public function getExchangeUsage(): array { return $this->exchangeUsage; }
    public function setExchangeUsage(array $exchangeUsage): self { $this->exchangeUsage = $exchangeUsage; return $this; }
    
    public function getAverageResponseTime(): ?int { return $this->averageResponseTime; }
    public function setAverageResponseTime(?int $averageResponseTime): self { $this->averageResponseTime = $averageResponseTime; return $this; }
    
    public function getTotalDataTransferred(): int { return $this->totalDataTransferred; }
    public function setTotalDataTransferred(int $totalDataTransferred): self { $this->totalDataTransferred = $totalDataTransferred; return $this; }
    
    public function getErrorRate(): ?string { return $this->errorRate; }
    public function setErrorRate(?string $errorRate): self { $this->errorRate = $errorRate; return $this; }
    
    public function getPeakUsageHour(): ?array { return $this->peakUsageHour; }
    public function setPeakUsageHour(?array $peakUsageHour): self { $this->peakUsageHour = $peakUsageHour; return $this; }
    
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    
    public function getUpdatedAt(): ?\DateTime { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTime $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }
}
