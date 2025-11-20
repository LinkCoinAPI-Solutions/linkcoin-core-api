<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Service;

use App\Subscription\Domain\Service\Contract\SubscriptionPricingServiceInterface;

final readonly class SubscriptionPricingService implements SubscriptionPricingServiceInterface
{
    private const YEARLY_DISCOUNT_RATE = 0.15; // 15%

    /**
     * Calcule les économies réalisées sur un abonnement.
     */
    public function calculateSavings(float $price, string $billingCycle): float
    {
        if ($billingCycle === 'yearly') {
            return round($price * self::YEARLY_DISCOUNT_RATE, 2);
        }

        return 0.0;
    }

    /**
     * Calcule le pourcentage d'économie.
     */
    public function calculateSavingsPercentage(string $billingCycle): float
    {
        return $billingCycle === 'yearly' 
            ? self::YEARLY_DISCOUNT_RATE * 100 
            : 0.0;
    }

    /**
     * Calcule le prix après remise.
     */
    public function calculateDiscountedPrice(float $price, string $billingCycle): float
    {
        $savings = $this->calculateSavings($price, $billingCycle);
        return round($price - $savings, 2);
    }
}
