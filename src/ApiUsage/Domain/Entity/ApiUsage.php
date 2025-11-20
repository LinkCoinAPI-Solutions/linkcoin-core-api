<?php

namespace App\ApiUsage\Domain\Entity;

use App\Repository\ApiUsageRepository;
use App\Subscription\Domain\Entity\UserSubscription;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApiUsageRepository::class)]
#[ORM\Table(name: 'api_usage')]
#[ORM\Index(name: 'IDX_api_usage_user_date', columns: ['user_subscription_id', 'created_at'])]
#[ORM\Index(name: 'IDX_api_usage_endpoint', columns: ['endpoint'])]
#[ORM\Index(name: 'IDX_api_usage_date', columns: ['created_at'])]
class ApiUsage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: UserSubscription::class, inversedBy: 'apiUsages')]
    #[ORM\JoinColumn(name: 'user_subscription_id', nullable: false, onDelete: 'CASCADE')]
    private UserSubscription $userSubscription;

    #[ORM\Column(type: 'string', length: 255)]
    private string $endpoint;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $exchange = null;

    #[ORM\Column(type: 'integer')]
    private int $callsCount = 1;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $responseTime = null; // en ms

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $responseSize = null; // en bytes

    #[ORM\Column(type: 'string', length: 10)]
    private string $httpStatus = '200';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $metadata = null; // Headers, IP, etc.

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUserSubscription(): UserSubscription { return $this->userSubscription; }
    public function setUserSubscription(UserSubscription $userSubscription): self { $this->userSubscription = $userSubscription; return $this; }
    public function getEndpoint(): string { return $this->endpoint; }
    public function setEndpoint(string $endpoint): self { $this->endpoint = $endpoint; return $this; }
    public function getExchange(): ?string { return $this->exchange; }
    public function setExchange(?string $exchange): self { $this->exchange = $exchange; return $this; }
    public function getCallsCount(): int { return $this->callsCount; }
    public function setCallsCount(int $callsCount): self { $this->callsCount = $callsCount; return $this; }
    public function getResponseTime(): ?int { return $this->responseTime; }
    public function setResponseTime(?int $responseTime): self { $this->responseTime = $responseTime; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
