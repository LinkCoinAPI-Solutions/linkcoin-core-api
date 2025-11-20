<?php

namespace App\Shared\Tests\Support\Factory;

use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\ValueObject\DiscountType;
use App\Discount\Domain\ValueObject\DiscountCode;
use App\Shared\Domain\ValueObject\Money;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * Factory pour créer des entités Discount compatibles avec la nouvelle entité,
 * utilisant les ValueObjects pour normaliser et valider les propriétés.
 */
final class DiscountFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Discount::class;
    }

    protected function defaults(): array
    {
        $faker = self::faker();

        $type = $faker->randomElement([DiscountType::PERCENTAGE, DiscountType::FIXED]);

        $value = $type === DiscountType::PERCENTAGE 
            ? (string) $faker->numberBetween(5, 50)
            : (string) $faker->numberBetween(500, 5000);

        $value = number_format((float) $value, 2, '.', '');

        $code = strtoupper($faker->unique()->bothify('????####'));
        $validFrom = $faker->optional(0.7)->dateTimeBetween('-1 month', 'now');
        $validUntil = $faker->optional(0.7)->dateTimeBetween('now', '+1 year');
        $minimumAmount = $faker->optional(0.3)->numberBetween(1000, 5000);
        $minimumAmount = $minimumAmount ? number_format($minimumAmount, 2, '.', '') : null;
        $maximumDiscount = $type === DiscountType::PERCENTAGE 
            ? ($faker->optional(0.3)->numberBetween(1000, 10000) ?? null)
            : null;
        $maximumDiscount = $maximumDiscount !== null ? number_format($maximumDiscount, 2, '.', '') : null;

        return [
            'code' => DiscountCode::fromString($code)->value,
            'description' => $faker->sentence(),
            'type' => $type,
            'value' => $value,
            'minimumAmount' => $minimumAmount,
            'maximumDiscount' => $maximumDiscount,
            'usageLimit' => $faker->optional(0.5)->numberBetween(10, 1000),
            'validFrom' => $validFrom ? \DateTimeImmutable::createFromMutable($validFrom) : null,
            'validUntil' => $validUntil ? \DateTimeImmutable::createFromMutable($validUntil) : null,
            'isActive' => true,
        ];
    }

    /**
     * Crée une réduction de type pourcentage fixée à $percentage.
     */
    public static function createPercentage(int $percentage = 10): self
    {
        return self::new([
            'type' => DiscountType::PERCENTAGE,
            'value' => number_format($percentage, 2, '.', ''),
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
        ]);
    }

    /**
     * Crée une réduction fixe à $amount.
     */
    public static function createFixedAmount(int $amount = 1000): self
    {
        return self::new([
            'type' => DiscountType::FIXED,
            'value' => number_format($amount, 2, '.', ''),
            'isActive' => true,
            'validFrom' => new \DateTimeImmutable('-1 day'),
            'validUntil' => new \DateTimeImmutable('+1 year'),
        ]);
    }
}
