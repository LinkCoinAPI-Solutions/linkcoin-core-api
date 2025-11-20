<?php

namespace App\Discount\Tests\Unit\Domain\ValueObject;

use App\Discount\Domain\ValueObject\DiscountCode;
use App\Discount\Domain\Exception\DiscountValidationException;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le Value Object DiscountCode.
 */
class DiscountCodeTest extends TestCase
{
    public function testValidCodeCreation(): void
    {
        // Arrange
        $input = 'promo2025';

        // Act
        $code = DiscountCode::fromString($input);

        // Assert: code normalisé en majuscules et trimé
        $this->assertEquals('PROMO2025', (string) $code);
    }

    public function testEmptyCodeThrowsException(): void
    {
        // Assert: exception attendue pour code vide
        $this->expectException(DiscountValidationException::class);

        // Act
        DiscountCode::fromString('');
    }

    public function testInvalidCharactersThrowException(): void
    {
        // Assert: exception attendue pour code avec caractères invalides
        $this->expectException(DiscountValidationException::class);

        // Act
        DiscountCode::fromString('code invalide!');
    }
}
