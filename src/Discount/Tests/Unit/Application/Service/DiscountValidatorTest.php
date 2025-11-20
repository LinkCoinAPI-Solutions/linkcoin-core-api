<?php

namespace App\Discount\Tests\Unit\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountValidationException;
use App\Discount\Application\Service\DiscountValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour le service DiscountValidator.
 */
class DiscountValidatorTest extends TestCase
{
    private DiscountValidator $validator;

    protected function setUp(): void
    {
        // Arrange : instanciation du validateur
        $this->validator = new DiscountValidator();
    }

    public function testValidateDiscountWithValidDiscount(): void
    {
        // Arrange : réduction active, valide, sans minimum
        $discount = new Discount();
        $discount->setCode('VALID20');
        $discount->setIsActive(true);
        $discount->setValidFrom(new \DateTimeImmutable('-1 day'));
        $discount->setValidUntil(new \DateTimeImmutable('+1 year'));
        $discount->setMinimumAmount(null);

        $cart = new Cart();
        $cart->setTotalAmount('1000.00');

        // Act & Assert : validation réussie sans exception
        $this->validator->validate($cart, $discount);
        $this->assertTrue(true);
    }

    public function testValidateDiscountWithInactiveDiscountThrowsException(): void
    {
        // Arrange : réduction inactive
        $discount = new Discount();
        $discount->setCode('INACTIVE');
        $discount->setIsActive(false);

        $cart = new Cart();
        $cart->setTotalAmount('1000.00');

        // Assert : exception attendue
        $this->expectException(DiscountValidationException::class);

        // Act
        $this->validator->validate($cart, $discount);
    }

    public function testValidateDiscountWithExpiredDiscountThrowsException(): void
    {
        // Arrange : réduction expirée
        $discount = new Discount();
        $discount->setCode('EXPIRED');
        $discount->setIsActive(true);
        $discount->setValidFrom(new \DateTimeImmutable('-1 year'));
        $discount->setValidUntil(new \DateTimeImmutable('-1 day'));

        $cart = new Cart();
        $cart->setTotalAmount('1000.00');

        // Assert : exception attendue
        $this->expectException(DiscountValidationException::class);

        // Act
        $this->validator->validate($cart, $discount);
    }

    public function testValidateDiscountWithMinimumAmountNotReachedThrowsException(): void
    {
        // Arrange : réduction avec minimum non atteint
        $discount = new Discount();
        $discount->setCode('MIN500');
        $discount->setIsActive(true);
        $discount->setValidFrom(new \DateTimeImmutable('-1 day'));
        $discount->setValidUntil(new \DateTimeImmutable('+1 year'));
        $discount->setMinimumAmount('500.00');

        $cart = new Cart();
        $cart->setTotalAmount('200.00');

        // Assert : exception attendue
        $this->expectException(DiscountValidationException::class);

        // Act
        $this->validator->validate($cart, $discount);
    }

    public function testValidateDiscountWithAlreadyAppliedThrowsException(): void
    {
        // Arrange : réduction déjà appliquée
        $discount = new Discount();
        $discount->setCode('APPLIED');
        $discount->setIsActive(true);
        $discount->setValidFrom(new \DateTimeImmutable('-1 day'));
        $discount->setValidUntil(new \DateTimeImmutable('+1 year'));

        $cart = new Cart();
        $cart->setTotalAmount('1000.00');
        $cart->setDiscount($discount);

        // Assert : exception attendue si on ne skip pas
        $this->expectException(DiscountValidationException::class);

        // Act
        $this->validator->validate($cart, $discount, false);
    }

    public function testValidateDiscountIgnoresAlreadyAppliedWhenFlagTrue(): void
    {
        // Arrange : réduction déjà appliquée, mais skip check
        $discount = new Discount();
        $discount->setCode('APPLIED');
        $discount->setIsActive(true);
        $discount->setValidFrom(new \DateTimeImmutable('-1 day'));
        $discount->setValidUntil(new \DateTimeImmutable('+1 year'));

        $cart = new Cart();
        $cart->setTotalAmount('1000.00');
        $cart->setDiscount($discount);

        // Act & Assert : pas d’exception lancée
        $this->validator->validate($cart, $discount, true);
        $this->assertTrue(true);
    }
}
