<?php

namespace App\Discount\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Domain\Entity\Discount;

/**
 * Interface du service applicatif principal (Facade Pattern).
 * Orchestre Finder, Validator, Applier et UsageManager.
 * Point d'entrée unique pour toutes les opérations sur les réductions.
 */
interface DiscountServiceInterface
{
    /**
     * Trouve une réduction valide par son code.
     * Délègue au DiscountFinder.
     * 
     * @return Discount|null Réduction trouvée ou null
     */
    public function findValidDiscountByCode(string $discountCode): ?Discount;

    /**
     * Applique une réduction à un panier.
     * Orchestration complète : validation → application → usage.
     * 
     * @return bool True si l'application a réussi
     * @throws \App\Discount\Domain\Exception\DiscountValidationException Si validation échoue
     * @throws \App\Discount\Domain\Exception\DiscountApplicationException Si persistence échoue
     */
    public function applyDiscountToCart(Cart $cart, Discount $discount): bool;

    /**
     * Retire une réduction d'un panier.
     * Orchestration : suppression → décrémente usage.
     * Ne fait rien si aucune réduction n'est appliquée.
     * 
     * @throws \App\Discount\Domain\Exception\DiscountApplicationException Si persistence échoue
     */
    public function removeDiscountFromCart(Cart $cart): void;

    /**
     * Recalcule la réduction d'un panier après modification.
     * Vérifie la validité et supprime si invalide.
     * Utilisé après ajout/suppression d'items.
     * 
     * @throws \App\Discount\Domain\Exception\DiscountApplicationException Si persistence échoue
     */
    public function recalculateCartDiscount(Cart $cart): void;
}
