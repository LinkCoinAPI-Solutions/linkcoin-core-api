<?php

namespace App\Discount\Tests\Unit\Domain\ValueObject;

use App\Discount\Domain\ValueObject\DiscountType;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour l'Enum DiscountType.
 */
class DiscountTypeTest extends TestCase
{
    public function testFromStringReturnsEnum(): void
    {
        // Arrange
        $typeString = 'fixed';

        // Act
        $type = DiscountType::fromString($typeString);

        // Assert
        $this->assertSame(DiscountType::FIXED, $type);
    }

    public function testGetLabel(): void
    {
        // Act
        $label = DiscountType::FIXED->getLabel();

        // Assert
        $this->assertEquals('Montant fixe', $label);
    }

    public function testGetUnit(): void
    {
        // Act
        $unit = DiscountType::PERCENTAGE->getUnit();

        // Assert
        $this->assertEquals('%', $unit);
    }

    public function testRequiresValue(): void
    {
        // Assert true pour FIXED
        $this->assertTrue(DiscountType::FIXED->requiresValue());
        // Assert false pour NONE
        $this->assertFalse(DiscountType::NONE->requiresValue());
    }
}
