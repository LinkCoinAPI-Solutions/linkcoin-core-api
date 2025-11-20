<?php

declare(strict_types=1);

namespace App\Discount\Application\Mapper;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Application\Dto\V1\Output\DiscountOutput;
use App\Discount\Application\Mapper\Contract\DiscountMapperInterface;
use App\Discount\Domain\Entity\Discount;

/**
 * Mapper pour transformer Discount en DiscountOutput.
 * Agrège toutes les informations de l'entité et les données calculées.
 */
final readonly class DiscountMapper implements DiscountMapperInterface
{
    /**
     * Transforme l'entité Discount en DTO API.
     * Inclut les informations statiques (règles) et dynamiques (calculs).
     * Format les montants et dates pour la réponse API.
     */
    public function toApiOutput(
        Discount $discount,
        ?float $calculatedAmount = null,
        ?float $discountPercentage = null
    ): DiscountOutput {
        return new DiscountOutput(
            id: $discount->getId(),
            code: $discount->getCode(),
            description: $discount->getDescription(),
            type: $discount->getType(),
            value: (float) $discount->getValue(),
            formattedValue: $discount->getFormattedValue(),
            usageCount: $discount->getUsageCount(),
            isActive: $discount->isActive(),
            isCurrentlyValid: $discount->isCurrentlyValid(),
            createdAt: $discount->getCreatedAt(),
            
            // Données calculées dynamiquement
            calculatedAmount: $calculatedAmount,
            discountPercentage: $discountPercentage,
            
            // Règles de validation
            minimumAmount: (float) $discount->getMinimumAmount(),
            maximumDiscount: (float) $discount->getMaximumDiscount(),
            usageLimit: $discount->getUsageLimit(),
            validFrom: $discount->getValidFrom(),
            validUntil: $discount->getValidUntil(),
            
            // États dérivés
            hasUsagesLeft: $discount->hasUsagesLeft(),
            isExpired: $discount->isExpired(),
            updatedAt: $discount->getUpdatedAt()
        );
    }
}
