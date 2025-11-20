<?php

namespace App\Discount\Tests\Integration\Application\Service;

use App\Cart\Application\Service\CartService;
use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Discount\Application\Service\DiscountService;
use App\Shared\Tests\Support\Factory\DiscountFactory;
use App\Shared\Tests\Support\TestCase\ApiTestCase;
use App\Shared\Tests\Support\Factory\UserFactory;
use App\Shared\Tests\Support\Factory\ProductFactory;
use App\Shared\Tests\Support\Factory\SubscriptionPlanFactory;

class DiscountServiceIntegrationTest extends ApiTestCase
{
    private DiscountService $discountService;
    private CartService $cartService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = self::getContainer()->get(DiscountService::class);
        $this->cartService = self::getContainer()->get(CartService::class);
    }

    public function testApplyDiscountToCartSucceeds(): void
    {
        // Arrange: création utilisateur, produit, souscription, panier avec articles
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '100.00']);

        $cart = new Cart();
        $cart->setUser($user->_real());

        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product->_real());
        $cartItem->setSubscriptionPlan($plan->_real());
        $cartItem->setQuantity(2);
        $cartItem->setUnitPrice('100.00');
        $cart->addItem($cartItem);

        $discount = DiscountFactory::createPercentage(20)->create([
            'code' => 'APPLY20',
            'description' => 'Réduction de 20%',
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
            'minimumAmount' => '0.00',
        ]);

        $this->entityManager->persist($cartItem);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        // Act : appliquer la réduction au panier
        $success = $this->discountService->applyDiscountToCart($cart, $discount->_real());
        $this->entityManager->flush();

        // Assert : l’application réussit, la réduction est positive et le total correct (2x100)
        $this->assertTrue($success);
        $this->assertGreaterThan(0.00, (float) $cart->getDiscountAmount());
        $this->assertEquals(200, (string) $cart->getTotalAmount());
    }

    public function testRecalculateCartDiscountAfterTotalChange(): void
    {
        // Arrange: création utilisateur, produit, plan et panier avec item+réduction
        $user = UserFactory::createOne(['isActive' => true]);
        $product = ProductFactory::createOne(['isAvailable' => true]);
        $plan = SubscriptionPlanFactory::createOne(['product' => $product, 'price' => '100.00']);

        $cart = new Cart();
        $cart->setUser($user->_real());

        $cartItem = new CartItem();
        $cartItem->setCart($cart);
        $cartItem->setProduct($product->_real());
        $cartItem->setSubscriptionPlan($plan->_real());
        $cartItem->setQuantity(3);
        $cartItem->setUnitPrice('100.00');
        $cart->addItem($cartItem);

        $discount = DiscountFactory::createPercentage(20)->create([
            'code' => 'RECALC20',
            'description' => 'Réduction recalculée 20%',
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
            'minimumAmount' => '0.00',
        ]);

        $cart->setDiscount($discount->_real());

        $this->entityManager->persist($cartItem);
        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        // Premiers totaux calculés
        $this->cartService->updateCartTotals($cart);
        $this->entityManager->flush();

        // Assert initial que les totaux correspondent
        $this->assertEquals(300, (string)$cart->getTotalAmount());
        $this->assertEquals(60, (string)$cart->getDiscountAmount());

        // Act : changement quantité = total à 200, recalcul réduction
        $cartItem->setQuantity(2);
        $this->entityManager->flush();

        $this->discountService->recalculateCartDiscount($cart);
        $this->entityManager->flush();

        // Assert: totaux mis à jour après recalcul
        $this->assertEquals(200, (string)$cart->getTotalAmount());
        $this->assertEquals(40, (string)$cart->getDiscountAmount());
        $this->assertEquals(160, (string)$cart->getFinalAmount());
    }
}
