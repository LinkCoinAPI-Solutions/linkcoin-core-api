<?php

namespace App\Cart\Application\Dto\V1\Input;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use App\Discount\Infrastructure\Validator\Constraints\ValidDiscountCode;

final class ApplyDiscountInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(min: 3, max: 50)]
        #[ValidDiscountCode] 
        #[Groups(['cart:write'])]
        public string $code,
    ) {
    }
}
