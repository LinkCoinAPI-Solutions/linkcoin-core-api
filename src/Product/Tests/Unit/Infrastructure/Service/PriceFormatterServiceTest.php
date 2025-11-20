<?php

namespace App\Product\Tests\Unit\Infrastructure\Service;

use App\Product\Infrastructure\Service\PriceFormatterService;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour PriceFormatterService.
 */
class PriceFormatterServiceTest extends TestCase
{
    private PriceFormatterService $formatter;

    protected function setUp(): void
    {
        // Arrange : instanciation du service
        $this->formatter = new PriceFormatterService();
    }

    public function testFormatWithDefaultOptions(): void
    {
        // Arrange : prix de 10.50
        $price = 10.50;

        // Act : formatage avec options par défaut
        $formatted = $this->formatter->format($price);

        // Assert : format $10.50
        $this->assertEquals('$10.50', $formatted);
    }

    public function testFormatWithCustomCurrency(): void
    {
        // Arrange : prix de 25.99
        $price = 25.99;

        // Act : formatage avec devise Euro
        $formatted = $this->formatter->format($price, ['currency' => '€']);

        // Assert : format €25.99
        $this->assertEquals('€25.99', $formatted);
    }

    public function testFormatWithBillingCycleMonthly(): void
    {
        // Arrange : prix de 10
        $price = 10;

        // Act : formatage avec cycle mensuel
        $formatted = $this->formatter->formatWithBillingCycle($price, 'monthly');

        // Assert : format $10.00/mois
        $this->assertEquals('$10.00/mois', $formatted);
    }

    public function testFormatWithBillingCycleYearly(): void
    {
        // Arrange : prix de 100
        $price = 100;

        // Act : formatage avec cycle annuel
        $formatted = $this->formatter->formatWithBillingCycle($price, 'yearly');

        // Assert : format $100.00/an
        $this->assertEquals('$100.00/an', $formatted);
    }

    public function testFormatWithCurrencyUSD(): void
    {
        // Arrange : prix de 50.00
        $price = 50.00;

        // Act : formatage avec USD
        $formatted = $this->formatter->formatWithCurrency($price, 'USD');

        // Assert : format $50.00
        $this->assertEquals('$50.00', $formatted);
    }

    public function testFormatWithCurrencyEUR(): void
    {
        // Arrange : prix de 75.50
        $price = 75.50;

        // Act : formatage avec EUR
        $formatted = $this->formatter->formatWithCurrency($price, 'EUR');

        // Assert : format européen 75,50€
        $this->assertEquals('75,50€', $formatted);
    }

    public function testFormatProductPriceDefaultsToMonthly(): void
    {
        // Arrange : prix de 15
        $price = 15;

        // Act : formatage prix produit (cycle mensuel par défaut)
        $formatted = $this->formatter->formatProductPrice($price);

        // Assert : format $15.00/mois
        $this->assertEquals('$15.00/mois', $formatted);
    }
}
