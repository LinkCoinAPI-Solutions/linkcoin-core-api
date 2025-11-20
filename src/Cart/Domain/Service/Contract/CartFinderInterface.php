<?php

namespace App\Cart\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;

/**
 * Service de recherche de paniers.
 */
interface CartFinderInterface
{
    /**
     * Trouve le panier actif d'un utilisateur.
     */
    public function findActiveCartByUser(User $user): ?Cart;

    /**
     * Trouve un panier par son ID.
     */
    public function findCartById(int $cartId): ?Cart;
}
