<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper\Contract;

use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Contrat pour le mapping SubscriptionPlan → DTO panier.
 */
interface SubscriptionPlanCartMapperInterface
{
    /**
     * Extrait les limites du plan sous forme de tableau.
     */
    public function toApiOutputLimits(SubscriptionPlan $plan): array;
}
