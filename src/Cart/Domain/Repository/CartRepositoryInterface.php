<?php

namespace App\Cart\Domain\Repository;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;

/**
 * Interface de repository pour les paniers.
 * Définit les opérations de persistence et de recherche des paniers.
 */
interface CartRepositoryInterface
{
    /**
     * Persiste un panier en base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function save(Cart $entity, bool $flush = false): void;

    /**
     * Supprime un panier de la base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function remove(Cart $cart, bool $flush = false): void;

    /**
     * Trouve le panier actif (PENDING) d'un utilisateur.
     * Un utilisateur ne peut avoir qu'un seul panier actif à la fois.
     * Précharge les relations (items, produits, plans, réductions).
     * 
     * @return Cart|null Panier actif ou null
     */
    public function findActiveCartByUser(User $user): ?Cart;

    /**
     * Trouve tous les paniers d'un utilisateur (tous statuts).
     * Triés du plus récent au plus ancien.
     * 
     * @param int|null $limit Limite le nombre de résultats (optionnel)
     * @return Cart[]
     */
    public function findByUser(User $user, ?int $limit = null): array;
}
