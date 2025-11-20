<?php

namespace App\Discount\Tests\Unit\Domain\ValueObject;

use App\Discount\Domain\ValueObject\Percentage;
use App\Discount\Domain\Exception\DiscountException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le Value Object Percentage.
 */
class PercentageTest extends TestCase
{
    public function testValidPercentageCreation(): void
    {
        // Arrange
        $value = 20.5;

        // Act
        $percentage = Percentage::fromFloat($value);

        // Assert
        $this->assertEquals($value, $percentage->value);
    }

    public function testInvalidPercentageThrows(): void
    {
        // Assert: exception attendue pour % hors borne [0-100]
        $this->expectException(DiscountException::class);

        // Act
        Percentage::fromFloat(120);
    }

    public function testApplyToCalculatesCorrectDiscount(): void
    {
        // Arrange
        $percentage = Percentage::fromFloat(10);
        $amount = \App\Shared\Domain\ValueObject\Money::fromFloat(200);

        // Act
        $result = $percentage->applyTo($amount);

        // Assert: 10% de 200 = 20
        $this->assertEquals(20.0, $result->amount);
    }
}
