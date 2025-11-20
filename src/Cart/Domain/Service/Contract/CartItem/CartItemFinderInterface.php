<?php

namespace App\Cart\Domain\Service\Contract\CartItem;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Service de recherche d'items dans le panier.
 */
interface CartItemFinderInterface
{
    /**
     * Trouve un item par produit et plan d'abonnement.
     */
    public function findByProductAndPlan(
        Cart $cart,
        Product $product,
        SubscriptionPlan $subscriptionPlan
    ): ?CartItem;

    /**
     * Trouve un item par produit uniquement.
     */
    public function findByProduct(Cart $cart, Product $product): ?CartItem;
}
