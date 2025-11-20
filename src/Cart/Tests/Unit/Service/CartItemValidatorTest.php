<?php

namespace App\Cart\Tests\Unit\Service;

use App\Cart\Domain\Exception\CartItemException;
use App\Cart\Application\Service\CartItem\CartItemValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour la validation de la quantité d’items.
 */
class CartItemValidatorTest extends TestCase
{
    private CartItemValidator $validator;

    protected function setUp(): void
    {
        // Arrange: création de l’instance validator
        $this->validator = new CartItemValidator();
    }

    public function testValidateQuantityWithValidQuantity(): void
    {
        // Act & Assert: quantité valide ne lance pas d’exception
        $this->validator->validateQuantity(5);
        $this->assertTrue(true);
    }

    public function testValidateQuantityWithZeroThrowsException(): void
    {
        // Arrange & Assert: attend exception sur quantité 0
        $this->expectException(CartItemException::class);

        // Act
        $this->validator->validateQuantity(0);
    }

    public function testValidateQuantityWithNegativeThrowsException(): void
    {
        // Arrange & Assert: exception sur quantité négative
        $this->expectException(CartItemException::class);

        // Act
        $this->validator->validateQuantity(-1);
    }

    public function testValidateQuantityWithExcessiveQuantityThrowsException(): void
    {
        // Arrange & Assert: exception sur quantité > max (100)
        $this->expectException(CartItemException::class);

        // Act
        $this->validator->validateQuantity(101);
    }

    public function testValidateQuantityWithMaxQuantity(): void
    {
        // Act & Assert: quantité max valide ne lance pas d’exception
        $this->validator->validateQuantity(100);
        $this->assertTrue(true);
    }
}
