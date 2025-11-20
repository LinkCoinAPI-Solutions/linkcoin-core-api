<?php

declare(strict_types=1);

namespace App\Product\Application\Mapper\Contract;

use App\Product\Application\Dto\V1\Output\ProductFeatureOutput;
use App\Product\Domain\Entity\ProductFeature;

/**
 * Interface pour transformer ProductFeature en DTO API.
 * Centralise la transformation des caractéristiques produit.
 */
interface ProductFeatureMapperInterface
{
    /**
     * Transforme une caractéristique en DTO API.
     * 
     * @param ProductFeature $feature Caractéristique à transformer
     * @return ProductFeatureOutput DTO pour la réponse API
     */
    public function toApiOutput(ProductFeature $feature): ProductFeatureOutput;

    /**
     * Transforme une collection de caractéristiques en tableau de DTOs.
     * Retourne un tableau vide si la collection est null.
     * 
     * @param iterable<ProductFeature>|null $features Collection de caractéristiques
     * @return ProductFeatureOutput[] Tableau de DTOs
     */
    public function toApiOutputCollection(?iterable $features): array;
}
