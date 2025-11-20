<?php

namespace App\Cart\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;

/**
 * Gestionnaire de cache pour les paniers.
 */
interface CartCacheManagerInterface
{
    /**
     * Récupère un panier depuis le cache.
     */
    public function getCartFromCache(User $user): ?Cart;

    /**
     * Met un panier en cache.
     */
    public function cacheCart(Cart $cart): void;

    /**
     * Invalide le cache d'un utilisateur.
     */
    public function invalidateCartCache(User $user): void;

    /**
     * Rafraîchit le cache d'un panier.
     */
    public function refreshCartCache(Cart $cart): void;

    /**
     * Invalide tout le cache des paniers.
     */
    public function invalidateAll(): void;
}
