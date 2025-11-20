<?php

namespace App\Cart\Domain\Repository;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Interface de repository pour les items du panier.
 * Définit les opérations de persistence et de recherche des items.
 */
interface CartItemRepositoryInterface
{
    /**
     * Persiste un item en base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function save(CartItem $cartItem, bool $flush = false): void;

    /**
     * Supprime un item de la base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function remove(CartItem $cartItem, bool $flush = false): void;

    /**
     * Trouve un item par panier, produit et plan d'abonnement.
     * Utilisé pour détecter les doublons avant d'ajouter un item.
     * 
     * @return CartItem|null Item trouvé ou null
     */
    public function findByCartProductAndPlan(
        Cart $cart,
        Product $product,
        SubscriptionPlan $subscriptionPlan
    ): ?CartItem;

    /**
     * Trouve tous les items actifs d'un panier.
     * Actifs = produit disponible ET plan d'abonnement actif.
     * 
     * @return CartItem[]
     */
    public function findActiveItemsByCart(Cart $cart): array;

    /**
     * Compte le nombre total d'items dans un panier.
     * Inclut tous les items (actifs ou non).
     * 
     * @return int Nombre d'items
     */
    public function countByCart(Cart $cart): int;
}
