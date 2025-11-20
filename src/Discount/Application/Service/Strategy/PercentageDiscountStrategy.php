<?php

namespace App\Discount\Application\Service\Strategy;

use App\Discount\Domain\Exception\DiscountException;
use App\Discount\Domain\Service\Contract\DiscountStrategyInterface;

/**
 * Stratégie de réduction en pourcentage (Strategy Pattern).
 * Applique un pourcentage du montant total comme réduction.
 * 
 * Exemple : -20% sur un panier de 50€ → réduction de 10€
 */
final readonly class PercentageDiscountStrategy implements DiscountStrategyInterface
{
    public const TYPE = 'percentage';
    private const MAX_PERCENTAGE = 100.0;
    private const MIN_PERCENTAGE = 0.0;

    /**
     * Calcule le montant de réduction en pourcentage.
     * Arrondit à 2 décimales pour éviter les erreurs de précision.
     * 
     * @param float $totalAmount Montant total du panier
     * @param float $discountValue Pourcentage de réduction (0-100)
     * @return float Montant de réduction calculé (arrondi à 2 décimales)
     * @throws DiscountException Si les montants sont invalides ou % hors limites
     */
    public function calculateDiscountAmount(float $totalAmount, float $discountValue): float
    {
        // Validation : montant positif et pourcentage dans [0, 100]
        if ($totalAmount < 0 || $discountValue < self::MIN_PERCENTAGE || $discountValue > self::MAX_PERCENTAGE) {
            throw DiscountException::invalidAmount($totalAmount, $discountValue);
        }

        // Validation : montants finis (pas NaN, pas infini)
        if (!is_finite($totalAmount) || !is_finite($discountValue)) {
            throw DiscountException::invalidAmount($totalAmount, $discountValue);
        }

        // Calcul avec arrondi à 2 décimales (centimes)
        return round($totalAmount * ($discountValue / 100), 2);
    }

    /**
     * Retourne le type de stratégie pour l'identification.
     */
    public function getStrategyType(): string
    {
        return self::TYPE;
    }
}
