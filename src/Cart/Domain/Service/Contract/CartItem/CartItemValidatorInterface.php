<?php

namespace App\Cart\Domain\Service\Contract\CartItem;

use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Validateur pour les opérations sur les items.
 */
interface CartItemValidatorInterface
{
    /**
     * Valide une quantité.
     * 
     * @throws \App\Cart\Domain\Exception\CartItemException
     */
    public function validateQuantity(int $quantity): void;

    /**
     * Valide qu'un produit est disponible.
     * 
     * @throws \App\Product\Domain\Exception\ProductException
     */
    public function validateProduct(Product $product): void;

    /**
     * Valide qu'un plan d'abonnement est compatible.
     * 
     * @throws \App\Product\Domain\Exception\SubscriptionPlanException
     */
    public function validateSubscriptionPlan(SubscriptionPlan $subscriptionPlan, Product $product): void;

    /**
     * Valide l'ajout d'un produit au panier.
     */
    public function validateAddToCart(Product $product, int $quantity): void;

    /**
     * Valide la suppression d'un item.
     * 
     * @throws \App\Cart\Domain\Exception\CartItemException
     */
    public function validateRemoveItem(CartItem $cartItem): void;

    /**
     * Valide la mise à jour de quantité.
     * 
     * @throws \App\Cart\Domain\Exception\CartItemException
     */
    public function validateUpdateQuantity(CartItem $cartItem, int $newQuantity): void;
}
