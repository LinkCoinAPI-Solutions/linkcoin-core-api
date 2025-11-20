<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper\Contract;

use App\Cart\Application\Dto\V1\Output\ProductCartOutput;
use App\Product\Domain\Entity\Product;

/**
 * Contrat pour le mapping Product → DTO simplifié panier.
 */
interface ProductCartMapperInterface
{
    /**
     * Transforme un Product en DTO simplifié pour le panier.
     */
    public function toApiOutput(Product $product): ProductCartOutput;
}
