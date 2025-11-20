<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper;

use App\Cart\Application\Dto\V1\Output\ProductCartOutput;
use App\Product\Domain\Entity\Product;
use App\Cart\Application\Mapper\Contract\ProductCartMapperInterface;

/**
 * Mapper pour transformer un produit en version simplifiée pour le panier.
 * Expose uniquement les informations nécessaires au contexte du panier.
 */
final readonly class ProductCartMapper implements ProductCartMapperInterface
{
    /**
     * Transforme un Product en DTO simplifié pour le panier.
     */
    public function toApiOutput(Product $product): ProductCartOutput
    {
        return new ProductCartOutput(
            id: $product->getId(),
            name: $product->getName(),
            category: $product->getCategory(),
            isAvailable: $product->isAvailable(),
        );
    }
}
