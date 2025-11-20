<?php

namespace App\Cart\Domain\Service\Contract\CartItem;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;

/**
 * Service de gestion des items du panier.
 */
interface CartItemServiceInterface
{
    /**
     * Ajoute un produit au panier.
     */
    public function addProductToCart(
        Cart $cart,
        Product $product,
        int $subscriptionPlanId,
        int $quantity,
        ?array $options = null
    ): CartItem;

    /**
     * Met à jour la quantité d'un item.
     */
    public function updateCartItemQuantity(CartItem $cartItem, int $quantity): void;

    /**
     * Supprime un item du panier.
     */
    public function removeCartItem(CartItem $cartItem): void;

    /**
     * Recherche un item par produit.
     */
    public function findCartItemByProduct(Cart $cart, Product $product): ?CartItem;
}
