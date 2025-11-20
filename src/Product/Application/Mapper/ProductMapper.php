<?php

declare(strict_types=1);

namespace App\Product\Application\Mapper;

use App\Product\Application\Dto\V1\Output\ProductOutput;
use App\Product\Application\Mapper\Contract\ProductMapperInterface;
use App\Product\Domain\Entity\Product;
use App\Product\Application\Mapper\Contract\ProductFeatureMapperInterface;
use App\Subscription\Application\Mapper\Contract\SubscriptionPlanMapperInterface;

/**
 * Mapper pour transformer Product en DTO API.
 * Coordonne les sous-mappers (features, subscription plans).
 * Agrège toutes les données nécessaires à l'affichage du produit.
 */
final readonly class ProductMapper implements ProductMapperInterface
{
    public function __construct(
        private SubscriptionPlanMapperInterface $subscriptionPlanMapper,
        private ProductFeatureMapperInterface $productFeatureMapper
    ) {}

    /**
     * Transforme un Product en DTO utilisable par l'API.
     * 
     * Agrégation :
     * 1. Données de base du produit (nom, description, catégorie, etc.)
     * 2. Caractéristiques (features) via ProductFeatureMapper
     * 3. Plans d'abonnement via SubscriptionPlanMapper
     * 4. Métadonnées et flags (popular, unlimited stock)
     * 
     * Format des dates : ISO 8601 (format 'c') pour compatibilité API.
     */
    public function toApiOutput(Product $product): ProductOutput
    {
        // Transforme les caractéristiques du produit
        $featuresDto = $this->productFeatureMapper->toApiOutputCollection(
            $product->getProductFeatures()
        );

        // Transforme les plans d'abonnement associés
        $subscriptionPlansDto = $this->subscriptionPlanMapper->toApiOutputCollection(
            $product->getSubscriptionPlans()
        );

        return new ProductOutput(
            id: $product->getId(),
            name: $product->getName(),
            description: $product->getDescription(),
            category: $product->getCategory(),
            productType: $product->getProductTypeValue(),
            metadata: $product->getMetadata(),
            isAvailable: $product->isAvailable(),
            slug: $product->getSlug(),
            createdAt: $product->getCreatedAt()->format('c'),
            updatedAt: $product->getUpdatedAt()?->format('c'),
            subscriptionPlans: $subscriptionPlansDto,
            features: $featuresDto,
            isPopular: $product->isPopular(),
            isUnlimitedStock: $product->hasUnlimitedStock()
        );
    }
}
