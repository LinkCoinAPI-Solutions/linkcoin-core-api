<?php

namespace App\Cart\Application\Service\CartItem;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Cart\Domain\Service\Contract\CartItem\CartItemFactoryInterface;

/**
 * Factory pour la création d'items de panier.
 * Initialise les items avec les valeurs appropriées.
 */
final readonly class CartItemFactory implements CartItemFactoryInterface
{
    /**
     * Crée un nouvel item de panier.
     * Configure automatiquement le prix unitaire depuis le plan d'abonnement.
     */
    public function create(
        Cart $cart,
        Product $product,
        SubscriptionPlan $subscriptionPlan,
        int $quantity,
        ?array $options = null
    ): CartItem {
        $cartItem = new CartItem();
        
        return $cartItem
            ->setCart($cart)
            ->setProduct($product)
            ->setSubscriptionPlan($subscriptionPlan)
            ->setQuantity($quantity)
            ->setUnitPrice($subscriptionPlan->getPrice())
            ->setOptions($options);
    }
}
