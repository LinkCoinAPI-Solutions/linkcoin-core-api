<?php

namespace App\Discount\Application\Service\Strategy;

use App\Discount\Domain\Exception\DiscountException;
use App\Discount\Domain\Service\Contract\DiscountStrategyInterface;

/**
 * Stratégie de réduction fixe (Strategy Pattern).
 * Applique un montant fixe de réduction, plafonné au montant total du panier.
 * 
 * Exemple : -10€ sur un panier de 50€ → réduction de 10€
 */
final readonly class FixedDiscountStrategy implements DiscountStrategyInterface
{
    public const TYPE = 'fixed';
    private const MAX_DISCOUNT_VALUE = 999999.99;

    /**
     * Calcule le montant de réduction fixe.
     * La réduction ne peut jamais dépasser le montant total du panier.
     * 
     * @param float $totalAmount Montant total du panier
     * @param float $discountValue Montant fixe de la réduction
     * @return float Montant de réduction appliqué (plafonné au total)
     * @throws DiscountException Si les montants sont invalides
     */
    public function calculateDiscountAmount(float $totalAmount, float $discountValue): float
    {
        // Validation : pas de montants négatifs
        if ($totalAmount < 0 || $discountValue < 0) {
            throw DiscountException::invalidAmount($totalAmount, $discountValue);
        }

        // Validation : plafond de sécurité
        if ($discountValue > self::MAX_DISCOUNT_VALUE) {
            throw DiscountException::invalidAmount($totalAmount, $discountValue);
        }

        // Validation : montants finis (pas NaN, pas infini)
        if (!is_finite($totalAmount) || !is_finite($discountValue)) {
            throw DiscountException::invalidAmount($totalAmount, $discountValue);
        }

        // Plafonne la réduction au montant total (évite les montants négatifs)
        return min($discountValue, $totalAmount);
    }

    /**
     * Retourne le type de stratégie pour l'identification.
     */
    public function getStrategyType(): string
    {
        return self::TYPE;
    }
}
