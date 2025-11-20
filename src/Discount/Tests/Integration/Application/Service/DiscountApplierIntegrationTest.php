<?php

namespace App\Discount\Tests\Integration\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Application\Service\DiscountApplier;
use App\Discount\Domain\Entity\Discount;
use App\Shared\Tests\Support\TestCase\ApiTestCase;

class DiscountApplierIntegrationTest extends ApiTestCase
{
    private DiscountApplier $discountApplier;

    protected function setUp(): void
    {
        // Arrange : récupération du service DiscountApplier depuis le container
        parent::setUp();
        $this->discountApplier = self::getContainer()->get(DiscountApplier::class);
    }

    public function testCalculateDiscountAmountFixed(): void
    {
        // Arrange : création d'une réduction fixe de 10€
        $discount = new Discount();
        $discount->setDescription('Réduction fixe de 10€');
        $discount->setType('fixed');
        $discount->setValue('10.00');

        // Act : calcul du montant de réduction sur une base de 100€
        $amount = $this->discountApplier->calculateDiscountAmount($discount, 100.0);

        // Assert : la réduction doit être égale à 10.0
        $this->assertEquals(10.0, $amount);
    }

    public function testCalculateDiscountAmountPercentage(): void
    {
        // Arrange : création d'une réduction en pourcentage de 25%
        $discount = new Discount();
        $discount->setDescription('Réduction 25%');
        $discount->setType('percentage');
        $discount->setValue('25.00');

        // Act : calcul du montant de réduction sur une base de 200€
        $amount = $this->discountApplier->calculateDiscountAmount($discount, 200.0);

        // Assert : la réduction doit être égale à 50.0 (25% de 200)
        $this->assertEquals(50.0, $amount);
    }

    public function testApplyDiscountToCartUpdatesCart(): void
    {
        // Arrange : création d'un panier avec un montant total de 100€
        $cart = new Cart();
        $cart->setTotalAmount('100.00');

        // Création d'une réduction fixe de 15€
        $discount = new Discount();
        $discount->setDescription('Réduction fixe 15€');
        $discount->setType('fixed');
        $discount->setValue('15.00');

        // Act : application de la réduction au panier
        $success = $this->discountApplier->applyDiscountToCart($cart, $discount);

        // Assert : vérifie que l'application a réussi,
        // que le montant de la réduction est correct,
        // et que la réduction est bien liée au panier
        $this->assertTrue($success);
        $this->assertEquals(15.0, $cart->getDiscountAmount());
        $this->assertSame($discount, $cart->getDiscount());
    }
}
