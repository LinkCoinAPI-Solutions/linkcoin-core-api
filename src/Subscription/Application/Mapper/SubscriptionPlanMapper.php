<?php

declare(strict_types=1);

namespace App\Subscription\Application\Mapper;

use App\Product\Application\Dto\V1\Output\SubscriptionPlanOutput;
use App\Subscription\Application\Mapper\Contract\SubscriptionPlanMapperInterface;
use App\Product\Domain\Service\Contract\PriceFormatterServiceInterface;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use App\Subscription\Domain\Service\Contract\SubscriptionPricingServiceInterface;

/**
 * Transforme un SubscriptionPlan en DTO API.
 */
readonly class SubscriptionPlanMapper implements SubscriptionPlanMapperInterface
{

    public function __construct(
        private PriceFormatterServiceInterface $priceFormatter,
        private SubscriptionPricingServiceInterface $pricingService
    ) {}

    /**
     * Transforme un SubscriptionPlan en DTO utilisable par l’API.
     */
    public function toApiOutput(
        SubscriptionPlan $plan
    ): SubscriptionPlanOutput {
        return new SubscriptionPlanOutput(
            id: $plan->getId(),
            productId: $plan->getProduct()->getId(),
            name: $plan->getName(),
            price: (float) $plan->getPrice(),
            billingCycle: $plan->getBillingCycle(),
            tierLevel: $plan->getTierLevel(),
            apiCallsLimit: $plan->getApiCallsLimit(),
            rateLimitPerMinute: $plan->getRateLimitPerMinute(),
            allowedExchanges: $plan->getAllowedExchanges(),
            allowedEndpoints: $plan->getAllowedEndpoints(),
            features: $plan->getFeatures(),
            restrictions: $plan->getRestrictions(),
            isActive: $plan->isActive(),
            createdAt: $plan->getCreatedAt()->format('c'),
            priceFormatted: $this->priceFormatter->format($plan->getPrice()),
            savings: $this->pricingService->calculateSavingsPercentage($plan->getBillingCycle()),
        );
    }

    /**
     * Transforme une collection de SubscriptionPlan en tableau de DTO.
     *
     * @param iterable<SubscriptionPlan> $plans
     */
    public function toApiOutputCollection(iterable $plans): array
    {
        $result = [];
        foreach ($plans as $plan) {
            $result[] = $this->toApiOutput($plan);
        }
        return $result;
    }
}
