<?php

namespace App\Cart\Application\Dto\V1\Output;

use App\Discount\Application\Dto\V1\Output\DiscountOutput;
use Symfony\Component\Serializer\Annotation\Groups;

final class CartOutput
{
    public function __construct(
        #[Groups(['cart:read'])]
        public ?int $cartId,

        #[Groups(['cart:read'])]
        public ?int $userId,

        #[Groups(['cart:read'])]
        public string $status,

        #[Groups(['cart:read'])]
        public float $totalAmount,

        #[Groups(['cart:read'])]
        public ?float $discountAmount,

        #[Groups(['cart:read'])]
        public float $finalAmount,

        #[Groups(['cart:read'])]
        public \DateTimeImmutable $createdAt,

        #[Groups(['cart:read'])]
        public ?\DateTimeImmutable $updatedAt,

        #[Groups(['cart:read'])]
        public array $items,

        #[Groups(['cart:read'])]
        public int $totalItems,

        #[Groups(['cart:read'])]
        public int $totalQuantity,

        #[Groups(['cart:read'])]
        public ?string $sessionId = null,

        #[Groups(['cart:read'])]
        public ?DiscountOutput $discount = null,
    ) {}
}
