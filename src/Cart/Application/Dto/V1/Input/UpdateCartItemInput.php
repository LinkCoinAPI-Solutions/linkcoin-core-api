<?php

namespace App\Cart\Application\Dto\V1\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCartItemInput
{
    public function __construct(
        #[Assert\Range(min: 1, max: 100)]
        #[Groups(['cart:write'])]
        public int $quantity,
    ) {
    }
}
