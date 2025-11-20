<?php

namespace App\Cart\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;

/**
 * Service principal de gestion des paniers.
 */
interface CartServiceInterface
{
    /**
     * Récupère le panier d'un utilisateur (avec cache optionnel).
     */
    public function getCartByUser(User $user, bool $useCache = true): ?Cart;

    /**
     * Récupère le panier directement depuis la base.
     */
    public function getFreshCartByUser(User $user): ?Cart;

    /**
     * Récupère ou crée un panier pour un utilisateur.
     */
    public function getOrCreateCart(User $user, bool $useCache = true): Cart;

    /**
     * Crée un nouveau panier pour un utilisateur.
     * 
     * @throws \App\Cart\Domain\Exception\CartException Si un panier existe déjà
     */
    public function createCartForUser(User $user): Cart;

    /**
     * Met à jour les totaux du panier.
     */
    public function updateCartTotals(Cart $cart): void;

    /**
     * Vide complètement un panier.
     */
    public function clearCart(Cart $cart): void;

    /**
     * Invalide le cache d'un panier.
     */
    public function invalidateCartCache(Cart $cart): void;

    /**
     * Calcule les totaux sans les persister.
     */
    public function calculateCartTotals(Cart $cart): array;
}
