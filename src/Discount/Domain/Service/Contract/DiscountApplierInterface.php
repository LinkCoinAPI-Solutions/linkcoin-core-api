<?php

namespace App\Discount\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Domain\Entity\Discount;

/**
 * Interface pour l'application des réductions aux paniers.
 * Gère le calcul, l'application, le recalcul et la suppression.
 */
interface DiscountApplierInterface
{
    /**
     * Calcule le montant de réduction via la stratégie appropriée.
     * 
     * @param Discount $discount Réduction à appliquer
     * @param float $totalAmount Montant total du panier
     * @return float Montant de réduction calculé
     * @throws \App\Discount\Domain\Exception\DiscountException Si calcul invalide
     */
    public function calculateDiscountAmount(Discount $discount, float $totalAmount): float;

    /**
     * Applique une réduction à un panier dans une transaction.
     * 
     * @return bool True si l'application a réussi
     * @throws \App\Discount\Domain\Exception\DiscountApplicationException Si persistence échoue
     */
    public function applyDiscountToCart(Cart $cart, Discount $discount): bool;

    /**
     * Recalcule la réduction d'un panier après modification.
     * Ne fait rien si aucune réduction n'est appliquée.
     * 
     * @throws \App\Discount\Domain\Exception\DiscountApplicationException Si persistence échoue
     */
    public function recalculateDiscountForCart(Cart $cart): void;

    /**
     * Retire la réduction d'un panier dans une transaction.
     * Ne fait rien si aucune réduction n'est appliquée.
     * 
     * @throws \App\Discount\Domain\Exception\DiscountApplicationException Si persistence échoue
     */
    public function removeDiscountFromCart(Cart $cart): void;
}
