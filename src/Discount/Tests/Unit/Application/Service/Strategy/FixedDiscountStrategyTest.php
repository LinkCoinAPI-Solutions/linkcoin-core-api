<?php

namespace App\Discount\Tests\Unit\Application\Service\Strategy;

use App\Discount\Application\Service\Strategy\FixedDiscountStrategy;
use App\Discount\Domain\Exception\DiscountException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour FixedDiscountStrategy.
 */
class FixedDiscountStrategyTest extends TestCase
{
    private FixedDiscountStrategy $strategy;

    protected function setUp(): void
    {
        // Arrange: instanciation de la stratégie
        $this->strategy = new FixedDiscountStrategy();
    }

    public function testCalculateDiscountAmountReturnsDiscountOrTotalIfLower(): void
    {
        // Arrange: total 100, discount 10
        $total = 100.0;
        $discountValue = 10.0;

        // Act
        $result = $this->strategy->calculateDiscountAmount($total, $discountValue);

        // Assert: attendu 10.0 (discount < total)
        $this->assertEquals(10.0, $result);
    }

    public function testCalculateDiscountAmountCapsAtTotalAmount(): void
    {
        // Arrange: total 50, discount 100 (doit plafonner au total)
        $total = 50.0;
        $discountValue = 100.0;

        // Act
        $result = $this->strategy->calculateDiscountAmount($total, $discountValue);

        // Assert: max discount est 50 (total)
        $this->assertEquals(50.0, $result);
    }

    public function testCalculateDiscountAmountThrowsOnNegativeAmounts(): void
    {
        // Assert exceptions lancées pour valeurs négatives
        $this->expectException(DiscountException::class);

        // Act
        $this->strategy->calculateDiscountAmount(-1, 10);
    }

    public function testGetStrategyTypeReturnsFixed(): void
    {
        // Act
        $type = $this->strategy->getStrategyType();

        // Assert
        $this->assertEquals('fixed', $type);
    }
}
