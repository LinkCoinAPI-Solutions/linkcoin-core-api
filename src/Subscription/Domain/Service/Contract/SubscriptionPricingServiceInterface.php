<?php

declare(strict_types=1);

namespace App\Subscription\Domain\Service\Contract;

/**
 * Service de calcul des tarifs et économies pour les abonnements.
 */
interface SubscriptionPricingServiceInterface
{
    /**
     * Calcule le montant des économies réalisées sur un abonnement.
     */
    public function calculateSavings(float $price, string $billingCycle): float;
    
    /**
     * Calcule le pourcentage de réduction appliqué selon le cycle de facturation.
     */
    public function calculateSavingsPercentage(string $billingCycle): float;
    
    /**
     * Calcule le prix final après application de la remise.
     */
    public function calculateDiscountedPrice(float $price, string $billingCycle): float;
}
