<?php

declare(strict_types=1);

namespace App\Subscription\Application\Mapper\Contract;

use App\Product\Application\Dto\V1\Output\SubscriptionPlanOutput;
use App\Subscription\Domain\Entity\SubscriptionPlan;

/**
 * Interface pour transformer un SubscriptionPlan en DTO API.
 */
interface SubscriptionPlanMapperInterface
{
    /**
     * Transforme un SubscriptionPlan en DTO API.
     */
    public function toApiOutput(
        SubscriptionPlan $plan,
    ): SubscriptionPlanOutput;

    /**
     * Transforme une collection de SubscriptionPlan en tableau de DTO.
     */
    public function toApiOutputCollection(iterable $plans): array;
}
