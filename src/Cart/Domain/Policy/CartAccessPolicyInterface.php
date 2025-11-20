<?php

declare(strict_types=1);

namespace App\Cart\Domain\Policy;

use App\Cart\Domain\Entity\Cart;

/**
 * Contrat pour la politique d'accès au panier.
 */
interface CartAccessPolicyInterface
{
    /**
     * Vérifie que l'utilisateur peut ajouter un item.
     * 
     * @throws \App\Cart\Domain\Exception\CartSecurityException
     */
    public function assertCanAddItem(Cart $cart): void;

    /**
     * Vérifie que l'utilisateur peut modifier le panier.
     * 
     * @throws \App\Cart\Domain\Exception\CartSecurityException
     */
    public function assertCanUpdate(Cart $cart): void;

    /**
     * Vérifie que l'utilisateur peut voir le panier.
     * 
     * @throws \App\Cart\Domain\Exception\CartSecurityException
     */
    public function assertCanView(Cart $cart): void;

    /**
     * Vérifie que l'utilisateur peut supprimer le panier.
     * 
     * @throws \App\Cart\Domain\Exception\CartSecurityException
     */
    public function assertCanDelete(Cart $cart): void;
}
