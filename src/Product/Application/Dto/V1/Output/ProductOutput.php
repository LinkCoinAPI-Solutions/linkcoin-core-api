<?php

namespace App\Product\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

readonly class ProductOutput
{
    public function __construct(
        #[Groups(['product:list', 'product:read'])]
        public int $id,
        
        #[Groups(['product:list', 'product:read'])]
        public string $name,
        
        #[Groups(['product:list', 'product:read'])]
        public string $description,
        
        #[Groups(['product:list', 'product:read'])]
        public string $category,
        
        #[Groups(['product:list', 'product:read'])]
        public string $productType,
        
        #[Groups(['product:list', 'product:read'])]
        public array $metadata,
        
        #[Groups(['product:list', 'product:read'])]
        public bool $isAvailable,
        
        #[Groups(['product:list', 'product:read'])]
        public string $slug,
        
        #[Groups(['product:list', 'product:read'])]
        public string $createdAt,
        
        #[Groups(['product:list', 'product:read'])]
        public ?string $updatedAt,
        
        #[Groups(['product:list', 'product:read'])]
        public array $subscriptionPlans,
        
        #[Groups(['product:list', 'product:read'])]
        public array $features,

        #[Groups(['product:list', 'product:read'])]
        public ?bool $isUnlimitedStock, 
        
        #[Groups(['product:list', 'product:read'])]
        public ?bool $isPopular
    ) {}
}
