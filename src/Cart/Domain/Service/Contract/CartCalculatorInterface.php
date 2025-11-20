<?php

namespace App\Cart\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;

/**
 * Service de calcul pour les paniers.
 */
interface CartCalculatorInterface
{
    /**
     * Calcule le montant de la réduction.
     */
    public function calculateDiscountAmount(Cart $cart): float;

    /**
     * Calcule tous les totaux et retourne un tableau.
     */
    public function calculateTotals(Cart $cart): array;

    /**
     * Recalcule et met à jour les totaux du panier.
     */
    public function recalculateCart(Cart $cart): void;
}
