<?php

namespace App\Cart\Domain\Service;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;
use App\Cart\Domain\Enum\CartStatus;
use App\Cart\Domain\Service\Contract\CartFactoryInterface;

/**
 * Factory pour la création de paniers.
 * Initialise un panier avec des valeurs par défaut.
 */
final readonly class CartFactory implements CartFactoryInterface
{
    public function createForUser(User $user): Cart
    {
        $cart = new Cart();

        return $cart
            ->setUser($user)
            ->setStatus(CartStatus::PENDING)
            ->setTotalAmount('0.00');
    }
}
