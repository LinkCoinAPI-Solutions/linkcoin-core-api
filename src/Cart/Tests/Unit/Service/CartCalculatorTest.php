<?php

namespace App\Cart\Tests\Unit\Service;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Entity\CartItem;
use App\Product\Domain\Entity\Product;
use App\Cart\Domain\Service\CartCalculator;
use App\Discount\Domain\Service\Contract\DiscountApplierInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Tests unitaires pour CartCalculator.
 */
class CartCalculatorTest extends TestCase
{
    private CartCalculator $calculator;
    private $discountApplier;
    private $logger;

    protected function setUp(): void
    {
        // Arrange: créer les mocks du discountApplier et logger
        $this->discountApplier = $this->createMock(DiscountApplierInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        // Arrange: instanciation du service à tester
        $this->calculator = new CartCalculator($this->discountApplier, $this->logger);
    }

    public function testCalculateItemsTotalWithoutItems(): void
    {
        // Arrange: panier vide
        $cart = new Cart();

        // Act: calcul du total des items
        $total = $this->calculator->calculateItemsTotal($cart);

        // Assert: total attendu à zéro
        $this->assertEquals(0.0, $total);
    }

    public function testCalculateItemsTotalWithSingleItem(): void
    {
        // Arrange: panier avec un item à quantité 1 et prix 1500.00
        $cart = new Cart();
        $product = new Product();
        $item = new CartItem();
        $item->setProduct($product);
        $item->setQuantity(1);
        $item->setUnitPrice('1500.00');
        $cart->addItem($item);

        // Act: calcul total
        $total = $this->calculator->calculateItemsTotal($cart);

        // Assert: total attendu à 1500.0
        $this->assertEquals(1500.0, $total);
    }

    public function testCalculateItemsTotalWithMultipleItems(): void
    {
        // Arrange: panier avec deux items
        $cart = new Cart();

        $product1 = new Product();
        $item1 = new CartItem();
        $item1->setProduct($product1);
        $item1->setQuantity(1);
        $item1->setUnitPrice('1000.00');
        $cart->addItem($item1);

        $product2 = new Product();
        $item2 = new CartItem();
        $item2->setProduct($product2);
        $item2->setQuantity(1);
        $item2->setUnitPrice('2500.00');
        $cart->addItem($item2);

        // Act: calcul total
        $total = $this->calculator->calculateItemsTotal($cart);

        // Assert: total attendu 3500.0
        $this->assertEquals(3500.0, $total);
    }

    public function testCalculateItemsTotalWithQuantityMultiplier(): void
    {
        // Arrange: panier avec un item quantité 3, prix 500.00
        $cart = new Cart();
        $product = new Product();
        $item = new CartItem();
        $item->setProduct($product);
        $item->setQuantity(3);
        $item->setUnitPrice('500.00');
        $cart->addItem($item);

        // Act: calcul total
        $total = $this->calculator->calculateItemsTotal($cart);

        // Assert: total attendu 1500.0
        $this->assertEquals(1500.0, $total);
    }

    public function testCalculateTotalQuantityWithNoItems(): void
    {
        // Arrange: panier vide
        $cart = new Cart();

        // Act: nombre total d’articles
        $total = $cart->getTotalQuantity();

        // Assert: attendu zéro
        $this->assertEquals(0, $total);
    }

    public function testCalculateTotalQuantityWithMultipleItems(): void
    {
        // Arrange: panier avec items totalisant 8 articles
        $cart = new Cart();

        $product1 = new Product();
        $item1 = new CartItem();
        $item1->setProduct($product1);
        $item1->setQuantity(3);
        $item1->setUnitPrice('100.00');
        $cart->addItem($item1);

        $product2 = new Product();
        $item2 = new CartItem();
        $item2->setProduct($product2);
        $item2->setQuantity(5);
        $item2->setUnitPrice('200.00');
        $cart->addItem($item2);

        // Act: somme des quantités
        $total = $cart->getTotalQuantity();

        // Assert: total attendu 8
        $this->assertEquals(8, $total);
    }

    public function testCalculateTotalsWithDiscount(): void
    {
        // Arrange: panier avec items et réduction mockée à 100.00
        $cart = new Cart();

        $product = new Product();
        $item1 = new CartItem();
        $item1->setProduct($product);
        $item1->setQuantity(2);
        $item1->setUnitPrice('500.00');
        $cart->addItem($item1);

        $discount = $this->createMock(\App\Discount\Domain\Entity\Discount::class);
        $this->discountApplier->method('calculateDiscountAmount')->willReturn(100.00);
        $cart->setDiscount($discount);

        // Act: recalcul des montants
        $this->calculator->recalculateCart($cart);
        $totals = $this->calculator->calculateTotals($cart);

        // Assert
        $this->assertEquals(1000.0, $totals['totalAmount']);
        $this->assertEquals(100.0, $totals['discountAmount']);
        $this->assertEquals(900.0, $totals['finalAmount']);
    }

    public function testCalculateTotalsWithoutDiscount(): void
    {
        // Arrange: panier avec un seul item, pas de réduction
        $cart = new Cart();

        $product = new Product();
        $item = new CartItem();
        $item->setProduct($product);
        $item->setQuantity(1);
        $item->setUnitPrice('500.00');
        $cart->addItem($item);

        // Act: recalcul montants
        $this->calculator->recalculateCart($cart);
        $totals = $this->calculator->calculateTotals($cart);

        // Assert
        $this->assertEquals(500.0, $totals['totalAmount']);
        $this->assertEquals(0.0, $totals['discountAmount']);
        $this->assertEquals(500.0, $totals['finalAmount']);
    }

    public function testCalculateTotalsWithItemsCount(): void
    {
        // Arrange: panier avec un item quantité 2, prix 500.00
        $cart = new Cart();

        $product = new Product();
        $item = new CartItem();
        $item->setProduct($product);
        $item->setQuantity(2);
        $item->setUnitPrice('500.00');
        $cart->addItem($item);

        // Act: calcul des totaux
        $totals = $this->calculator->calculateTotals($cart);

        // Assert
        $this->assertEquals(2, $totals['totalQuantity']);
        $this->assertEquals(1, $totals['itemsCount']);
        $this->assertEquals(1000.0, $totals['totalAmount']);
    }
}
