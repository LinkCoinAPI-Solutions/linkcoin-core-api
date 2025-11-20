<?php

namespace App\Cart\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class ProductCartOutput
{
    public function __construct(
        #[Groups(['cart:read'])]
        public int $id,

        #[Groups(['cart:read'])]
        public string $name,

        #[Groups(['cart:read'])]
        public string $category,

        #[Groups(['cart:read'])]
        public bool $isAvailable,
    ) {}
}
