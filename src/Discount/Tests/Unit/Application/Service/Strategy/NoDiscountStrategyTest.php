<?php

namespace App\Discount\Tests\Unit\Application\Service\Strategy;

use App\Discount\Application\Service\Strategy\NoDiscountStrategy;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour NoDiscountStrategy.
 */
class NoDiscountStrategyTest extends TestCase
{
    private NoDiscountStrategy $strategy;

    protected function setUp(): void
    {
        // Arrange
        $this->strategy = new NoDiscountStrategy();
    }

    public function testCalculateDiscountAmountAlwaysReturnsZero(): void
    {
        // Act
        $result = $this->strategy->calculateDiscountAmount(1000, 100);

        // Assert
        $this->assertEquals(0.0, $result);
    }

    public function testGetStrategyTypeReturnsNone(): void
    {
        // Act
        $type = $this->strategy->getStrategyType();

        // Assert
        $this->assertEquals('none', $type);
    }
}
