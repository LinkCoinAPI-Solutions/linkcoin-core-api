<?php

namespace App\Cart\Application\Service\CartItem;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Product\Domain\Exception\SubscriptionPlanException;
use App\Cart\Domain\Service\Contract\CartItem\CartItemFactoryInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemFinderInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemServiceInterface;
use App\Cart\Domain\Service\Contract\CartItem\CartItemValidatorInterface;
use App\Cart\Domain\ValueObject\Quantity;
use App\Discount\Domain\Service\Contract\DiscountServiceInterface;
use App\Cart\Domain\Service\Contract\CartServiceInterface;
use App\Subscription\Domain\Repository\SubscriptionPlanRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service applicatif pour la gestion des items du panier.
 * Coordonne les opérations d'ajout, modification et suppression.
 */
final readonly class CartItemService implements CartItemServiceInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CartItemValidatorInterface $validator,
        private CartItemFactoryInterface $factory,
        private CartItemFinderInterface $finder,
        private SubscriptionPlanRepositoryInterface $subscriptionPlanRepository,
        private DiscountServiceInterface $discountService,
        private LoggerInterface $logger,
        private CartServiceInterface $cartService
    ) {}

    /**
     * Ajoute un produit au panier avec un plan d'abonnement.
     * Si l'item existe déjà, augmente la quantité, sinon crée un nouvel item.
     * Recalcule automatiquement les totaux et réductions.
     * 
     * @throws SubscriptionPlanException Si le plan est invalide
     * @throws ProductException Si le produit est invalide
     * @throws CartItemException Si la quantité est invalide
     */
    public function addProductToCart(
        Cart $cart,
        Product $product,
        int $subscriptionPlanId,
        int $quantity,
        ?array $options = null
    ): CartItem {
        // Validation quantité via Value Object
        $quantityVO = Quantity::fromInt($quantity);
        
        $this->validator->validateProduct($product);

        $subscriptionPlan = $this->getAndValidateSubscriptionPlan($subscriptionPlanId);
        $this->validator->validateSubscriptionPlan($subscriptionPlan, $product);

        // Vérifie si l'item existe déjà
        $existingItem = $this->finder->findByProductAndPlan($cart, $product, $subscriptionPlan);

        if ($existingItem) {
            $cartItem = $this->increaseItemQuantity($existingItem, $quantityVO->value);
        } else {
            $cartItem = $this->createCartItem($cart, $product, $subscriptionPlan, $quantityVO->value, $options);
        }

        // Recalcul des totaux et réductions
        $this->cartService->updateCartTotals($cart);
        $this->discountService->recalculateCartDiscount($cart);

        return $cartItem;
    }

    /**
     * Met à jour la quantité d'un item existant.
     * Si la quantité est 0, supprime l'item.
     * Recalcule automatiquement les totaux.
     * 
     * @throws CartItemException Si la mise à jour n'est pas autorisée
     */
    public function updateCartItemQuantity(CartItem $cartItem, int $quantity): void
    {
        // Quantité 0 = suppression
        if ($quantity <= 0) {
            $this->removeCartItem($cartItem);
            return;
        }

        $quantityVO = Quantity::fromInt($quantity);
        $this->validator->validateUpdateQuantity($cartItem, $quantityVO->value);

        $oldQuantity = $cartItem->getQuantity();
        $cart = $cartItem->getCart();

        // Mise à jour dans une transaction
        $this->entityManager->wrapInTransaction(function () use ($cartItem, $quantityVO): void {
            $cartItem->setQuantity($quantityVO->value);
        });

        // Recalcul des totaux et réductions
        $this->cartService->updateCartTotals($cart);
        $this->discountService->recalculateCartDiscount($cart);
        $this->logCartItemUpdated($cartItem, $oldQuantity, $quantityVO->value);
    }

    /**
     * Supprime un item du panier.
     * Recalcule automatiquement les totaux.
     * 
     * @throws CartItemException Si la suppression n'est pas autorisée
     */
    public function removeCartItem(CartItem $cartItem): void
    {
        $this->validator->validateRemoveItem($cartItem);

        $cart = $cartItem->getCart();
        $cartItemId = $cartItem->getId();

        // Suppression dans une transaction
        $this->entityManager->wrapInTransaction(function () use ($cart, $cartItem): void {
            $cart->removeItem($cartItem);
            $this->entityManager->remove($cartItem);
        });

        // Recalcul des totaux et réductions
        $this->cartService->updateCartTotals($cart);
        $this->discountService->recalculateCartDiscount($cart);
        $this->logCartItemRemoved($cartItemId, $cart->getId());
    }

    /**
     * Recherche un item par produit dans le panier.
     */
    public function findCartItemByProduct(Cart $cart, Product $product): ?CartItem
    {
        return $this->finder->findByProduct($cart, $product);
    }

    /**
     * Récupère et valide un plan d'abonnement.
     * 
     * @throws SubscriptionPlanException Si le plan est invalide ou introuvable
     */
    private function getAndValidateSubscriptionPlan(int $subscriptionPlanId): SubscriptionPlan
    {
        if ($subscriptionPlanId <= 0) {
            throw SubscriptionPlanException::invalidId($subscriptionPlanId);
        }

        $subscriptionPlan = $this->subscriptionPlanRepository->find($subscriptionPlanId);

        if (!$subscriptionPlan) {
            throw SubscriptionPlanException::notFound($subscriptionPlanId);
        }

        return $subscriptionPlan;
    }

    /**
     * Augmente la quantité d'un item existant.
     */
    private function increaseItemQuantity(CartItem $existingItem, int $quantity): CartItem
    {
        $oldQuantity = $existingItem->getQuantity();

        $this->entityManager->wrapInTransaction(function () use ($existingItem, $quantity): void {
            $existingItem->increaseQuantity($quantity);
        });

        $this->logCartItemQuantityIncreased($existingItem, $quantity, $oldQuantity);

        return $existingItem;
    }

    /**
     * Crée un nouvel item dans le panier.
     */
    private function createCartItem(
        Cart $cart,
        Product $product,
        SubscriptionPlan $subscriptionPlan,
        int $quantity,
        ?array $options
    ): CartItem {
        $cartItem = $this->entityManager->wrapInTransaction(function () use ($cart, $product, $subscriptionPlan, $quantity, $options): CartItem {
            $cartItem = $this->factory->create($cart, $product, $subscriptionPlan, $quantity, $options);
            $this->entityManager->persist($cartItem);
            $cart->addItem($cartItem);
            return $cartItem;
        });

        $this->logCartItemCreated($cartItem);

        return $cartItem;
    }

    private function logCartItemCreated(CartItem $cartItem): void
    {
        $this->logger->info('Cart item added', [
            'cart_id' => $cartItem->getCart()->getId(),
            'product_id' => $cartItem->getProduct()->getId(),
            'subscription_plan_id' => $cartItem->getSubscriptionPlan()?->getId(),
            'quantity' => $cartItem->getQuantity(),
            'unit_price' => $cartItem->getUnitPrice()
        ]);
    }

    private function logCartItemQuantityIncreased(CartItem $cartItem, int $addedQuantity, int $oldQuantity): void
    {
        $this->logger->info('Cart item quantity increased', [
            'cart_id' => $cartItem->getCart()->getId(),
            'product_id' => $cartItem->getProduct()->getId(),
            'subscription_plan_id' => $cartItem->getSubscriptionPlan()?->getId(),
            'quantity_added' => $addedQuantity,
            'old_quantity' => $oldQuantity,
            'new_quantity' => $cartItem->getQuantity()
        ]);
    }

    private function logCartItemUpdated(CartItem $cartItem, int $oldQuantity, int $newQuantity): void
    {
        $this->logger->info('Cart item quantity updated', [
            'cart_item_id' => $cartItem->getId(),
            'old_quantity' => $oldQuantity,
            'new_quantity' => $newQuantity
        ]);
    }

    private function logCartItemRemoved(int $cartItemId, int $cartId): void
    {
        $this->logger->info('Cart item removed', [
            'cart_item_id' => $cartItemId,
            'cart_id' => $cartId
        ]);
    }
}
