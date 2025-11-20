<?php

namespace App\Cart\Tests\Integration\Service;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Infrastructure\Repository\CartRepository;
use App\Cart\Application\Service\CartService;
use App\Cart\Application\Service\CartItem\CartItemService;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;

/**
 * Tests d’intégration pour le service Cart.
 */
class CartServiceIntegrationTest extends ApiTestCase
{
    private CartService $cartService;
    private CartItemService $cartItemService;
    private CartRepository $cartRepository;

    protected function setUp(): void
    {
        // Arrange: récupération services nécessaires
        parent::setUp();
        $this->cartService = self::getContainer()->get(CartService::class);
        $this->cartItemService = self::getContainer()->get(CartItemService::class);
        $this->cartRepository = self::getContainer()->get(CartRepository::class);
    }

    public function testGetOrCreateCartCreatesNewCart(): void
    {
        // Arrange: création utilisateur actif
        $user = UserFactory::createOne(['isActive' => true]);

        // Act: création ou récupération panier
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);
        $this->entityManager->flush();

        // Assert: panier créé et lié à l’utilisateur
        $this->assertInstanceOf(Cart::class, $cart);
        $this->assertEquals($user->getId(), $cart->getUser()->getId());

        $persistedCart = $this->cartRepository->findOneBy(['user' => $user->getId()]);
        $this->assertNotNull($persistedCart);
    }

    public function testGetOrCreateCartReturnsExistingCart(): void
    {
        // Arrange: utilisateur actif avec panier existant
        $user = UserFactory::createOne(['isActive' => true]);

        // Act: récupérer deux fois le panier
        $cart1 = $this->cartService->getOrCreateCart($user->_real(), false);
        $this->entityManager->flush();
        $cart1Id = $cart1->getId();
        $this->entityManager->clear();

        $cart2 = $this->cartService->getOrCreateCart($user->_real(), false);

        // Assert: mêmes panier et id récupérés
        $this->assertEquals($cart1Id, $cart2->getId());

        $allCarts = $this->cartRepository->findBy(['user' => $user->getId()]);
        $this->assertCount(1, $allCarts);
    }

    public function testClearCartRemovesAllItems(): void
    {
        // Arrange: utilisateur, 2 produits, plans et panier avec items
        $user = UserFactory::createOne(['isActive' => true]);
        $product1 = ProductFactory::createOne(['isAvailable' => true]);
        $product2 = ProductFactory::createOne(['isAvailable' => true]);
        $plan1 = SubscriptionPlanFactory::createOne(['product' => $product1]);
        $plan2 = SubscriptionPlanFactory::createOne(['product' => $product2]);

        $cart = $this->cartService->getOrCreateCart($user->_real(), false);
        $this->cartItemService->addProductToCart($cart, $product1->_real(), $plan1->getId(), 2);
        $this->cartItemService->addProductToCart($cart, $product2->_real(), $plan2->getId(), 3);
        $this->entityManager->flush();

        $this->assertCount(2, $cart->getItems());
        $cartId = $cart->getId();

        // Act: vider le panier
        $this->cartService->clearCart($cart);
        $this->entityManager->flush();

        // Assert: panier vide et montant total remis à zéro
        $this->assertCount(0, $cart->getItems());
        $this->assertEquals('0.00', $cart->getTotalAmount());
        $this->assertNull($cart->getDiscount());

        $this->entityManager->clear();
        $persistedCart = $this->cartRepository->find($cartId);
        $this->assertNotNull($persistedCart);
        $this->assertCount(0, $persistedCart->getItems());
        $this->assertEquals('0.00', $persistedCart->getTotalAmount());
    }

    public function testGetCartByUserReturnsNullIfNoCart(): void
    {
        // Arrange: utilisateur actif sans panier
        $user = UserFactory::createOne(['isActive' => true]);

        // Act: récupération panier qui n’existe pas
        $cart = $this->cartService->getCartByUser($user->_real(), false);

        // Assert: null retourné
        $this->assertNull($cart);
    }

    public function testGetCartByUserReturnsExistingCart(): void
    {
        // Arrange: utilisateur actif avec panier existant
        $user = UserFactory::createOne(['isActive' => true]);
        $createdCart = $this->cartService->getOrCreateCart($user->_real(), false);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Act: récupération panier
        $foundCart = $this->cartService->getCartByUser($user->_real(), false);

        // Assert: panier récupéré
        $this->assertNotNull($foundCart);
        $this->assertEquals($createdCart->getId(), $foundCart->getId());
    }

    public function testCartPersistsAcrossRequests(): void
    {
        // Arrange: utilisateur, produit, plan et panier avec item
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '1000.00']);
        $cart = $this->cartService->getOrCreateCart($user->_real(), false);
        $this->cartItemService->addProductToCart($cart, $product->_real(), $plan->getId(), 3);
        $this->entityManager->flush();
        $this->entityManager->clear();

        // Act: récupération panier depuis base
        $retrievedCart = $this->cartRepository->findOneBy(['user' => $user->getId()]);

        // Assert: panier et quantités présentes
        $this->assertNotNull($retrievedCart);
        $this->assertCount(1, $retrievedCart->getItems());
        $this->assertEquals(3, $retrievedCart->getItems()->first()->getQuantity());
    }
}
