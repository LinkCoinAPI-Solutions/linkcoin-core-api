<?php

namespace App\Cart\Domain\Service\Contract\CartItem;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Factory pour la création d'items de panier.
 */
interface CartItemFactoryInterface
{
    /**
     * Crée un nouvel item de panier.
     */
    public function create(
        Cart $cart,
        Product $product,
        SubscriptionPlan $subscriptionPlan,
        int $quantity,
        ?array $options = null
    ): CartItem;
}
