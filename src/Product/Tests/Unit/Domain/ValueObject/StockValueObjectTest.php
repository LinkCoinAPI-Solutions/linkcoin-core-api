<?php

namespace App\Product\Tests\Unit\Domain\ValueObject;

use App\Product\Domain\ValueObject\Stock;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le Value Object Stock.
 */
class StockValueObjectTest extends TestCase
{
    public function testLimitedStockCreatesValidInstance(): void
    {
        // Arrange & Act : création d'un stock limité à 10 unités
        $stock = Stock::limited(10);

        // Assert : quantité correcte et stock disponible
        $this->assertEquals(10, $stock->quantity);
        $this->assertFalse($stock->isUnlimited());
        $this->assertTrue($stock->isAvailable());
    }

    public function testUnlimitedStockCreatesValidInstance(): void
    {
        // Arrange & Act : création d'un stock illimité
        $stock = Stock::unlimited();

        // Assert : quantité null et stock illimité
        $this->assertNull($stock->quantity);
        $this->assertTrue($stock->isUnlimited());
        $this->assertTrue($stock->isAvailable());
    }

    public function testDecrementReducesStockQuantity(): void
    {
        // Arrange : stock initial de 10 unités
        $stock = Stock::limited(10);

        // Act : décrémentation de 3 unités
        $newStock = $stock->decrement(3);

        // Assert : nouvelle instance avec 7 unités, stock original inchangé (immutabilité)
        $this->assertEquals(7, $newStock->quantity);
        $this->assertEquals(10, $stock->quantity);
    }

    public function testDecrementThrowsExceptionWhenInsufficientStock(): void
    {
        // Arrange : stock de 5 unités
        $stock = Stock::limited(5);

        // Assert : exception attendue
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Insufficient stock');

        // Act : tentative de décrémentation de 10 unités
        $stock->decrement(10);
    }

    public function testIncrementIncreasesStockQuantity(): void
    {
        // Arrange : stock initial de 10 unités
        $stock = Stock::limited(10);

        // Act : incrémentation de 5 unités
        $newStock = $stock->increment(5);

        // Assert : nouvelle instance avec 15 unités
        $this->assertEquals(15, $newStock->quantity);
    }

    public function testIsOutOfStockReturnsTrueWhenStockIsZero(): void
    {
        // Arrange : stock à zéro
        $stock = Stock::limited(0);

        // Act & Assert : stock en rupture
        $this->assertTrue($stock->isOutOfStock());
        $this->assertFalse($stock->isAvailable());
    }

    public function testUnlimitedStockNeverRunsOut(): void
    {
        // Arrange : stock illimité
        $stock = Stock::unlimited();

        // Act : décrémentation (ne fait rien sur stock illimité)
        $newStock = $stock->decrement(1000);

        // Assert : toujours illimité et disponible
        $this->assertTrue($newStock->isUnlimited());
        $this->assertTrue($newStock->isAvailable());
    }
}
