<?php

namespace App\Product\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

readonly class ProductFeatureOutput
{
    public function __construct(
        #[Groups(['product:list', 'product:read', 'feature:read'])]
        public int $id,
        
        #[Groups(['product:list', 'product:read', 'feature:read'])]
        public string $name,
        
        #[Groups(['product:list', 'product:read', 'feature:read'])]
        public string $description
    ) {}
}
