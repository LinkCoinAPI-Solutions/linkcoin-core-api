<?php

declare(strict_types=1);

namespace App\Product\Application\Mapper\Contract;

use App\Product\Application\Dto\V1\Output\ProductOutput;
use App\Product\Domain\Entity\Product;

/**
 * Interface pour transformer Product en DTO API.
 * Centralise la transformation des produits pour la couche API.
 */
interface ProductMapperInterface
{
    /**
     * Transforme un Product en DTO utilisable par l'API.
     * Agrège les données du produit, ses plans d'abonnement et ses caractéristiques.
     * 
     * @param Product $product Entité à transformer
     * @return ProductOutput DTO pour la réponse API
     */
    public function toApiOutput(Product $product): ProductOutput;
}
