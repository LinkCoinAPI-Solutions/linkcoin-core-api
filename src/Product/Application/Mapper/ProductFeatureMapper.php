<?php

declare(strict_types=1);

namespace App\Product\Application\Mapper;

use App\Product\Application\Dto\V1\Output\ProductFeatureOutput;
use App\Product\Application\Mapper\Contract\ProductFeatureMapperInterface;
use App\Product\Domain\Entity\ProductFeature;

/**
 * Mapper pour transformer ProductFeature en DTO API.
 * Extrait les données essentielles des caractéristiques produit.
 */
final readonly class ProductFeatureMapper implements ProductFeatureMapperInterface
{
    /**
     * Transforme une caractéristique en DTO utilisable par l'API.
     * Gère la description nullable (fallback sur chaîne vide).
     */
    public function toApiOutput(ProductFeature $feature): ProductFeatureOutput
    {
        return new ProductFeatureOutput(
            id: $feature->getId(),
            name: $feature->getName(),
            description: $feature->getDescription() ?? '' // Fallback pour null
        );
    }

    /**
     * Transforme une collection de caractéristiques en tableau de DTOs.
     * 
     * Gestion des cas limites :
     * - null → tableau vide
     * - iterable → converti en array puis mappé
     * 
     * @param iterable<ProductFeature>|null $features Collection de caractéristiques
     * @return ProductFeatureOutput[] Tableau de DTOs
     */
    public function toApiOutputCollection(?iterable $features): array
    {
        if (!$features) {
            return [];
        }

        // Conversion iterable → array pour array_map
        return array_map(
            fn(ProductFeature $feature) => $this->toApiOutput($feature),
            iterator_to_array($features)
        );
    }
}
