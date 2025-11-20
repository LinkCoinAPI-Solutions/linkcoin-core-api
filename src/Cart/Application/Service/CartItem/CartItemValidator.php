<?php

namespace App\Cart\Application\Service\CartItem;

use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Cart\Domain\Exception\CartItemException;
use App\Cart\Domain\Service\Contract\CartItem\CartItemValidatorInterface;
use App\Cart\Domain\ValueObject\Quantity;
use App\Product\Domain\Exception\ProductException;
use App\Product\Domain\Exception\SubscriptionPlanException;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Validateur pour les opérations sur les items du panier.
 * Encapsule toutes les règles de validation métier.
 */
final readonly class CartItemValidator implements CartItemValidatorInterface
{
    /**
     * Valide qu'une quantité est dans les limites acceptables.
     * 
     * @throws CartItemException Si la quantité est invalide
     */
    public function validateQuantity(int $quantity): void
    {
        Quantity::fromInt($quantity);
    }

    /**
     * Valide qu'un produit peut être ajouté au panier.
     * 
     * @throws ProductException Si le produit est invalide ou indisponible
     */
    public function validateProduct(Product $product): void
    {
        if (!$product->getId()) {
            throw ProductException::invalidProduct('Le produit doit être persisté en base');
        }

        if (!$product->isAvailable()) {
            throw ProductException::notAvailable(
                $product->getId(),
                'Produit désactivé'
            );
        }
    }

    /**
     * Valide qu'un plan d'abonnement est actif et correspond au produit.
     * 
     * @throws SubscriptionPlanException Si le plan est inactif ou incompatible
     */
    public function validateSubscriptionPlan(SubscriptionPlan $subscriptionPlan, Product $product): void
    {
        if (!$subscriptionPlan->isActive()) {
            throw SubscriptionPlanException::inactive($subscriptionPlan->getId());
        }

        if ($subscriptionPlan->getProduct()->getId() !== $product->getId()) {
            throw SubscriptionPlanException::mismatchWithProduct(
                $subscriptionPlan->getId(),
                $product->getId(),
                $subscriptionPlan->getProduct()->getId()
            );
        }
    }

    /**
     * Valide l'ajout d'un produit au panier (quantité + disponibilité).
     * 
     * @throws ProductException|CartItemException Si la validation échoue
     */
    public function validateAddToCart(Product $product, int $quantity): void
    {
        $this->validateQuantity($quantity);
        $this->validateProduct($product);
    }

    /**
     * Valide qu'un item peut être supprimé du panier.
     * Vérifie l'existence, l'association au panier et le statut du panier.
     * 
     * @throws CartItemException Si l'item ne peut pas être supprimé
     */
    public function validateRemoveItem(CartItem $cartItem): void
    {
        if (!$cartItem->getId()) {
            throw CartItemException::itemNotFound(0);
        }

        $cart = $cartItem->getCart();
        if (!$cart) {
            throw CartItemException::cannotModifyCartItem(
                $cartItem->getId(),
                'CartItem n\'est associé à aucun panier'
            );
        }

        // Vérifie que le panier autorise les modifications
        if ($cart->getStatus() && !$cart->getStatus()->allowsModification()) {
            throw CartItemException::cartInInvalidState(
                $cartItem->getId(),
                $cart->getStatus()->value
            );
        }

        // Vérifie l'appartenance au panier
        if (!$cart->getItems()->contains($cartItem)) {
            throw CartItemException::cannotModifyCartItem(
                $cartItem->getId(),
                'Cet article n\'appartient pas au panier spécifié'
            );
        }
    }

    /**
     * Valide la mise à jour de quantité d'un item.
     * Réutilise la validation de suppression + validation de quantité.
     * 
     * @throws CartItemException Si la mise à jour n'est pas autorisée
     */
    public function validateUpdateQuantity(CartItem $cartItem, int $newQuantity): void
    {
        $this->validateRemoveItem($cartItem);

        if ($newQuantity > 0) {
            $this->validateQuantity($newQuantity);
        }
    }
}
