<?php

namespace App\Product\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

readonly class SubscriptionPlanOutput
{
    public function __construct(
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public int $id,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public int $productId,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public string $name,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public float $price,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public string $billingCycle,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public int $tierLevel,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public ?int $apiCallsLimit,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public ?int $rateLimitPerMinute,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public array $allowedExchanges,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public array $allowedEndpoints,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public array $features,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public array $restrictions,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public bool $isActive,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public string $createdAt,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public string $priceFormatted,
        
        #[Groups(['product:list', 'product:read', 'subscription:read'])]
        public ?float $savings
    ) {}
}
