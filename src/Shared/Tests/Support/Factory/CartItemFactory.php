<?php

namespace App\Shared\Tests\Support\Factory;

use App\Cart\Domain\Entity\CartItem;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<CartItem>
 */
final class CartItemFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return CartItem::class;
    }

    protected function defaults(): array
    {
        $product = ProductFactory::new()->create()->_real();
        
        return [
            'cart' => CartFactory::new(),
            'product' => $product,
            'subscriptionPlan' => null,
            'quantity' => self::faker()->numberBetween(1, 5),
            'unitPrice' => 0.0,
            'options' => self::faker()->optional(0.3)->passthrough([
                'customization' => self::faker()->word(),
                'priority' => self::faker()->boolean(),
            ]),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 month', 'now')),
        ];
    }

    /**
     * Create a cart item with specific quantity
     */
    public static function withQuantity(int $quantity): self
    {
        return self::new(['quantity' => $quantity]);
    }
}
