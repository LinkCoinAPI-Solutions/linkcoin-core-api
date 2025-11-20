<?php

declare(strict_types=1);

namespace App\Subscription\Tests\Unit\Service;

use App\Subscription\Domain\Service\SubscriptionPricingService;
use PHPUnit\Framework\TestCase;

final class SubscriptionPricingServiceTest extends TestCase
{
    private SubscriptionPricingService $service;

    protected function setUp(): void
    {
        $this->service = new SubscriptionPricingService();
    }

    public function testCalculateSavingsForYearlyPlan(): void
    {
        $savings = $this->service->calculateSavings(100.0, 'yearly');
        
        $this->assertEquals(15.0, $savings);
    }

    public function testCalculateSavingsForMonthlyPlan(): void
    {
        $savings = $this->service->calculateSavings(100.0, 'monthly');
        
        $this->assertEquals(0.0, $savings);
    }

    public function testCalculateSavingsPercentage(): void
    {
        $percentage = $this->service->calculateSavingsPercentage('yearly');
        
        $this->assertEquals(15.0, $percentage);
    }

    public function testCalculateDiscountedPrice(): void
    {
        $discountedPrice = $this->service->calculateDiscountedPrice(100.0, 'yearly');
        
        $this->assertEquals(85.0, $discountedPrice);
    }
}
