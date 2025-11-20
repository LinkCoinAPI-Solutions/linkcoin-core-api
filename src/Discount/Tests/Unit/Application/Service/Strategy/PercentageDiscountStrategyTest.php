<?php

namespace App\Discount\Tests\Unit\Application\Service\Strategy;

use App\Discount\Application\Service\Strategy\PercentageDiscountStrategy;
use App\Discount\Domain\Exception\DiscountException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour PercentageDiscountStrategy.
 */
class PercentageDiscountStrategyTest extends TestCase
{
    private PercentageDiscountStrategy $strategy;

    protected function setUp(): void
    {
        // Arrange
        $this->strategy = new PercentageDiscountStrategy();
    }

    public function testCalculateDiscountAmountComputesCorrectPercentage(): void
    {
        // Arrange: total 200, discount 25%
        $total = 200.0;
        $discountValue = 25.0;

        // Act
        $result = $this->strategy->calculateDiscountAmount($total, $discountValue);

        // Assert: 25% de 200 = 50, arrondi à 2 décimales
        $this->assertEquals(50.0, $result);
    }

    public function testCalculateDiscountAmountThrowsIfPercentInvalid(): void
    {
        $this->expectException(DiscountException::class);

        // Act: discount négatif
        $this->strategy->calculateDiscountAmount(100.0, -1.0);
    }

    public function testGetStrategyTypeReturnsPercentage(): void
    {
        // Act
        $type = $this->strategy->getStrategyType();

        // Assert
        $this->assertEquals('percentage', $type);
    }
}
