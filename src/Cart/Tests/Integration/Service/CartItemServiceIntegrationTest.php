<?php

namespace App\Cart\Tests\Integration\Service;

use App\Cart\Infrastructure\Repository\CartItemRepository;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Cart\Application\Service\CartItem\CartItemService;
use App\Cart\Application\Service\CartService;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;

/**
 * Tests d’intégration des services CartItem et Cart.
 */
class CartItemServiceIntegrationTest extends ApiTestCase
{
    private CartItemService $cartItemService;
    private CartService $cartService;
    private CartRepository $cartRepository;
    private CartItemRepository $cartItemRepository;

    protected function setUp(): void
    {
        // Arrange: récupération des services dans le container
        parent::setUp();
        $this->cartItemService = self::getContainer()->get(CartItemService::class);
        $this->cartService = self::getContainer()->get(CartService::class);
        $this->cartRepository = self::getContainer()->get(CartRepository::class);
        $this->cartItemRepository = self::getContainer()->get(CartItemRepository::class);
    }

    public function testAddProductToCartCreatesNewItem(): void
    {
        // Arrange: utilisateur, produit, plan et panier existants
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);

        // Act: ajout du produit au panier
        $this->cartItemService->addProductToCart($cart, $product->_real(), $plan->getId(), 2);
        $this->entityManager->flush();

        // Assert: vérification que l’item a été ajouté avec la bonne quantité
        $this->assertCount(1, $cart->getItems());
        $item = $cart->getItems()->first();
        $this->assertEquals(2, $item->getQuantity());
        $this->assertEquals($product->getId(), $item->getProduct()->getId());
    }

    public function testAddProductToCartIncreasesQuantityIfExists(): void
    {
        // Arrange: utilisateur, produit, plan, panier initialisés
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);

        // Act: ajout initial puis ajout supplémentaire du même produit/plan
        $this->cartItemService->addProductToCart($cart, $product->_real(), $plan->getId(), 2);
        $this->cartItemService->addProductToCart($cart, $product->_real(), $plan->getId(), 3);
        $this->entityManager->flush();

        // Assert: quantité cumulée attendue
        $this->assertCount(1, $cart->getItems());
        $item = $cart->getItems()->first();
        $this->assertEquals(5, $item->getQuantity());
    }

    public function testUpdateCartItemQuantityPersistsToDatabase(): void
    {
        // Arrange: utilisateur, produit, plan, panier avec un item
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);

        $this->cartItemService->addProductToCart($cart, $product->_real(), $plan->getId(), 2);
        $cartItem = $cart->getItems()->first();
        $itemId = $cartItem->getId();

        // Act: mise à jour quantité et flush / clear pour synchronisation
        $this->cartItemService->updateCartItemQuantity($cartItem, 10);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Assert: vérification en base de la nouvelle quantité
        $updatedItem = $this->cartItemRepository->find($itemId);
        $this->assertEquals(10, $updatedItem->getQuantity());
    }

    public function testRemoveCartItemDeletesFromDatabase(): void
    {
        // Arrange: utilisateur, produit, plan, panier avec un item
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product]);
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);

        $this->cartItemService->addProductToCart($cart, $product->_real(), $plan->getId(), 2);
        $cartItem = $cart->getItems()->first();
        $itemId = $cartItem->getId();

        // Act: suppression item, flush, clear
        $this->cartItemService->removeCartItem($cartItem);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Assert: item supprimé et panier vide
        $deletedItem = $this->cartItemRepository->find($itemId);
        $this->assertNull($deletedItem);

        $updatedCart = $this->cartRepository->find($cart->getId());
        $this->assertCount(0, $updatedCart->getItems());
    }

    public function testAddMultipleProductsToCart(): void
    {
        // Arrange: utilisateur, 3 produits et leurs plans
        $user = UserFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isAvailable' => true]);
        $product2 = ProductFactory::createOne(['isAvailable' => true]);
        $product3 = ProductFactory::createOne(['isAvailable' => true]);
        $plan1 = SubscriptionPlanFactory::createOne(['product' => $product1]);
        $plan2 = SubscriptionPlanFactory::createOne(['product' => $product2]);
        $plan3 = SubscriptionPlanFactory::createOne(['product' => $product3]);
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);

        // Act: ajout successifs des 3 produits avec quantités
        $this->cartItemService->addProductToCart($cart, $product1->_real(), $plan1->getId(), 2);
        $this->cartItemService->addProductToCart($cart, $product2->_real(), $plan2->getId(), 3);
        $this->cartItemService->addProductToCart($cart, $product3->_real(), $plan3->getId(), 1);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Assert: vérification en base que 3 items sont persistés
        $persistedCart = $this->cartRepository->find($cart->getId());
        $this->assertCount(3, $persistedCart->getItems());
    }
}
