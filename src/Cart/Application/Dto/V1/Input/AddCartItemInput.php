<?php

namespace App\Cart\Application\Dto\V1\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class AddCartItemInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Groups(['cart:write'])]
        public int $productId,

        #[Assert\NotBlank]
        #[Assert\Positive]
        #[Groups(['cart:write'])]
        public int $subscriptionPlanId,

        #[Assert\Range(min: 1, max: 100)]
        #[Groups(['cart:write'])]
        public int $quantity = 1,
    ) {
    }
}
