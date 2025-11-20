<?php

namespace App\Cart\Domain\Enum;

/**
 * Enum représentant les statuts possibles d'un panier.
 * Gère les transitions de statut et les autorisations de modification.
 * 
 * Flux standard : PENDING → COMPLETED (après paiement)
 *                 PENDING → CANCELLED (par l'utilisateur)
 */
enum CartStatus: string
{
    case PENDING = 'PENDING';      // Panier en cours de modification
    case COMPLETED = 'COMPLETED';  // Panier finalisé (commande passée)
    case CANCELLED = 'CANCELLED';  // Panier abandonné/annulé

    /**
     * Vérifie si une transition vers un nouveau statut est autorisée.
     * 
     * Règles :
     * - PENDING peut devenir COMPLETED ou CANCELLED
     * - COMPLETED et CANCELLED sont des états finaux (pas de transition)
     */
    public function isTransitionAllowed(CartStatus $newStatus): bool
    {
        return match ($this) {
            self::PENDING => in_array($newStatus, [self::COMPLETED, self::CANCELLED]),
            self::COMPLETED => false,
            self::CANCELLED => false,
        };
    }

    /**
     * Indique si le panier peut être modifié dans son état actuel.
     * Seuls les paniers PENDING peuvent être modifiés.
     */
    public function allowsModification(): bool
    {
        return match ($this) {
            self::PENDING => true,
            self::COMPLETED, self::CANCELLED => false,
        };
    }

    /**
     * Retourne la liste des statuts vers lesquels une transition est possible.
     * Utile pour l'UI (afficher les actions disponibles).
     * 
     * @return CartStatus[]
     */
    public function getAvailableTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::COMPLETED, self::CANCELLED],
            self::COMPLETED, self::CANCELLED => [],
        };
    }
}
