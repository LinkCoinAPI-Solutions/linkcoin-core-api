<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper;

use App\Cart\Application\Mapper\Contract\SubscriptionPlanCartMapperInterface;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Mapper pour transformer les plans d'abonnement pour le contexte panier.
 * Extrait les limites et caractéristiques du plan.
 */
final readonly class SubscriptionPlanCartMapper implements SubscriptionPlanCartMapperInterface
{
    /**
     * Extrait les limites du plan sous forme de tableau.
     * Utilisé pour afficher les caractéristiques du plan dans le panier.
     */
    public function toApiOutputLimits(SubscriptionPlan $plan): array
    {
        return [
            'apiCallsLimit' => $plan->getApiCallsLimit(),
            'rateLimitPerMinute' => $plan->getRateLimitPerMinute(),
            'allowedExchanges' => $plan->getAllowedExchanges(),
            'allowedEndpoints' => $plan->getAllowedEndpoints(),
            'tierLevel' => $plan->getTierLevel(),
        ];
    }
}
