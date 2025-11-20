<?php

namespace App\Discount\Application\Service\Strategy;

use App\Discount\Domain\Service\Contract\DiscountStrategyInterface;

/**
 * Stratégie sans réduction (Null Object Pattern).
 * Utilisée comme fallback quand aucune réduction n'est applicable.
 * Évite les vérifications null dans le code client.
 */
final readonly class NoDiscountStrategy implements DiscountStrategyInterface
{
    public const TYPE = 'none';

    /**
     * Calcule une réduction nulle.
     * Toujours retourne 0, quels que soient les paramètres.
     * 
     * @return float 0.0
     */
    public function calculateDiscountAmount(float $totalAmount, float $discountValue): float
    {
        return 0.0;
    }

    /**
     * Retourne le type de stratégie pour l'identification.
     */
    public function getStrategyType(): string
    {
        return self::TYPE;
    }
}
