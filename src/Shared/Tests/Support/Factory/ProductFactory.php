<?php

namespace App\Shared\Tests\Support\Factory;

use App\Product\Domain\Entity\Product;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

final class ProductFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Product::class;
    }

    protected function defaults(): array
    {
        return [
            'name' => self::faker()->words(3, true),
            'description' => self::faker()->paragraph(),
            'category' => self::faker()->randomElement(['crypto', 'blockchain', 'defi', 'nft', 'trading']),
            'productType' => self::faker()->randomElement(['api_access', 'data_package', 'premium_features']),
            'metadata' => [
                'api_endpoints' => ['GET /market/prices', 'GET /market/tickers'],
                'supported_exchanges' => ['binance', 'coinbase', 'kraken'],
            ],
            'isAvailable' => true,
        ];
    }

    protected function initialize(): static
    {
        return $this->afterInstantiate(function(Product $product): void {
            if ($product->getSlug() === null) {
                $product->generateSlug();
            }
        });
    }
}
