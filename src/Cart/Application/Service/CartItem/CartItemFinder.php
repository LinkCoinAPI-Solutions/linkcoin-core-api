<?php

namespace App\Cart\Application\Service\CartItem;

use App\Cart\Domain\Service\Contract\CartItem\CartItemFinderInterface;
use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Cart\Infrastructure\Repository\CartItemRepository;

/**
 * Service de recherche d'items dans le panier.
 * Délègue les requêtes au repository.
 */
final readonly class CartItemFinder implements CartItemFinderInterface
{
    public function __construct(
        private CartItemRepository $cartItemRepository
    ) {}

    /**
     * Trouve un item par produit et plan d'abonnement.
     */
    public function findByProductAndPlan(Cart $cart, Product $product, SubscriptionPlan $subscriptionPlan): ?CartItem
    {
        return $this->cartItemRepository->findByCartProductAndPlan($cart, $product, $subscriptionPlan);
    }

    /**
     * Trouve un item par produit uniquement.
     */
    public function findByProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->cartItemRepository->findOneBy([
            'cart' => $cart,
            'product' => $product
        ]);
    }
}
