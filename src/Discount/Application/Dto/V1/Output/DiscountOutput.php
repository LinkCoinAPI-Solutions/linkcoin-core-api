<?php

namespace App\Discount\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class DiscountOutput
{
    public function __construct(
        #[Groups(['cart:read', 'discount:read'])]
        public int $id,

        #[Groups(['cart:read', 'discount:read'])]
        public string $code,

        #[Groups(['cart:read', 'discount:read'])]
        public string $description,

        #[Groups(['cart:read', 'discount:read'])]
        public string $type,

        #[Groups(['cart:read', 'discount:read'])]
        public float $value,

        #[Groups(['cart:read', 'discount:read'])]
        public string $formattedValue,

        #[Groups(['cart:read', 'discount:read'])]
        public int $usageCount,

        #[Groups(['cart:read', 'discount:read'])]
        public bool $isActive,

        #[Groups(['cart:read', 'discount:read'])]
        public bool $isCurrentlyValid,

        #[Groups(['cart:read', 'discount:read'])]
        public \DateTimeImmutable $createdAt,

        #[Groups(['cart:read', 'discount:read'])]
        public ?float $calculatedAmount = null,

        #[Groups(['cart:read', 'discount:read'])]
        public ?float $discountPercentage = null,

        #[Groups(['discount:read'])]
        public ?float $minimumAmount = null,

        #[Groups(['discount:read'])]
        public ?float $maximumDiscount = null,

        #[Groups(['discount:read'])]
        public ?int $usageLimit = null,

        #[Groups(['discount:read'])]
        public ?\DateTimeImmutable $validFrom = null,

        #[Groups(['discount:read'])]
        public ?\DateTimeImmutable $validUntil = null,

        #[Groups(['discount:read'])]
        public bool $hasUsagesLeft = true,

        #[Groups(['discount:read'])]
        public bool $isExpired = false,

        #[Groups(['discount:read'])]
        public ?\DateTimeImmutable $updatedAt = null,
    ) {
    }
}
