<?php

declare(strict_types=1);

namespace App\Discount\Application\Mapper\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Application\Dto\V1\Output\DiscountOutput;
use App\Discount\Domain\Entity\Discount;

/**
 * Interface pour transformer un Discount en DTO API.
 * Centralise la transformation des données pour la couche API.
 */
interface DiscountMapperInterface
{
    /**
     * Transforme un Discount en DTO utilisable par l'API.
     * Agrège les données de l'entité et les montants calculés.
     * 
     * @param Discount $discount Entité à transformer
     * @param float|null $calculatedAmount Montant de réduction calculé (optionnel)
     * @param float|null $discountPercentage Pourcentage effectif appliqué (optionnel)
     * @return DiscountOutput DTO pour la réponse API
     */
    public function toApiOutput(
        Discount $discount,
        ?float $calculatedAmount = null,
        ?float $discountPercentage = null
    ): DiscountOutput;
}
