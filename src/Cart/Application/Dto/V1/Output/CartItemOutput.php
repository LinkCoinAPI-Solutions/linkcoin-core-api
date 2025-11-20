<?php

namespace App\Cart\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class CartItemOutput
{
    public function __construct(
        #[Groups(['cart:read'])]
        public ProductCartOutput $product,
        
        #[Groups(['cart:read'])]
        public int $itemId,

        #[Groups(['cart:read'])]
        public int $productId,

        #[Groups(['cart:read'])]
        public string $productName,

        #[Groups(['cart:read'])]
        public int $quantity,

        #[Groups(['cart:read'])]
        public float $unitPrice,

        #[Groups(['cart:read'])]
        public float $totalPrice,

        #[Groups(['cart:read'])]
        public string $unitPriceFormatted,

        #[Groups(['cart:read'])]
        public string $totalPriceFormatted,

        #[Groups(['cart:read'])]
        public ?int $subscriptionPlanId = null,

        #[Groups(['cart:read'])] 
        public ?string $planName = null,

        #[Groups(['cart:read'])] 
        public ?string $billingCycle = null,

        #[Groups(['cart:read'])]
        public ?array $planLimits = null,

        #[Groups(['cart:read'])]
        public ?float $planSavings = null,
    ) {}
}
