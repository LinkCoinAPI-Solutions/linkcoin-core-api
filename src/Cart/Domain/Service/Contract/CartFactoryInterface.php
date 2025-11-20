<?php

namespace App\Cart\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;

/**
 * Factory pour la création de paniers.
 */
interface CartFactoryInterface
{
    /**
     * Crée un nouveau panier pour un utilisateur.
     */
    public function createForUser(User $user): Cart;
}
