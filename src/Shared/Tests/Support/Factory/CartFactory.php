<?php

namespace App\Shared\Tests\Support\Factory;

use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Enum\CartStatus;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Cart>
 */
final class CartFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Cart::class;
    }

    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new(),
            'totalAmount' => '0.00',
            'status' => CartStatus::PENDING,
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 month', 'now')),
            'updatedAt' => self::faker()->optional(0.5)->passthrough(
                \DateTimeImmutable::createFromMutable(self::faker()->dateTimeBetween('-1 week', 'now'))
            ),
            'discount' => null,
            'discountAmount' => null,
            'sessionId' => self::faker()->optional(0.3)->uuid(),
        ];
    }

    /**
     * Create an active cart with items
     */
    public static function withItems(int $itemCount = 2): self
    {
        return self::new()
            ->afterInstantiate(function (Cart $cart) use ($itemCount) {
                for ($i = 0; $i < $itemCount; $i++) {
                    $item = CartItemFactory::new(['cart' => $cart])->create()->_real();
                    $cart->addItem($item);
                }
            });
    }
}
