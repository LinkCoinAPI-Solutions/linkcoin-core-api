<?php

namespace App\Shared\Tests\Support\Factory;

use App\Subscription\Domain\Entity\SubscriptionPlan;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class SubscriptionPlanFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return SubscriptionPlan::class;
    }

    protected function defaults(): array
    {
        return [
            'product' => ProductFactory::new(),
            'name' => self::faker()->words(2, true),
            'price' => (string) self::faker()->numberBetween(500, 5000) . '.00',
            'billingCycle' => self::faker()->randomElement(['monthly', 'yearly', 'quarterly']),
            'isActive' => true,
        ];
    }
}
