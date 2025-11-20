<?php

namespace App\Auth\Domain\Repository;

use App\Auth\Domain\Entity\PasswordResetToken;
use App\Account\Domain\Entity\User;

/**
 * Interface de repository pour les tokens de réinitialisation de mot de passe.
 * Définit les opérations de persistence et de recherche des tokens.
 */
interface PasswordResetTokenRepositoryInterface
{
    /**
     * Trouve un token par sa valeur (sans vérification de validité).
     * Utilisé pour récupérer le token avant validation manuelle.
     * 
     * @return PasswordResetToken|null Token trouvé ou null
     */
    public function findOneByToken(string $token): ?PasswordResetToken;

    /**
     * Trouve un token valide par sa valeur.
     * Vérifie que le token existe, n'est pas expiré et n'est pas utilisé.
     * 
     * @return PasswordResetToken|null Token trouvé ou null
     */
    public function findValidToken(string $token): ?PasswordResetToken;

    /**
     * Trouve tous les tokens actifs pour un utilisateur.
     * Actifs = non utilisés ET non expirés.
     * 
     * @return PasswordResetToken[]
     */
    public function findActiveByUser(User $user): array;

    /**
     * Trouve tous les tokens d'un utilisateur (tous états).
     * 
     * @return PasswordResetToken[]
     */
    public function findByUser(User $user): array;

    /**
     * Persiste un token en base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function save(PasswordResetToken $token, bool $flush = false): void;

    /**
     * Supprime un token de la base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function remove(PasswordResetToken $token, bool $flush = false): void;

    /**
     * Invalide tous les tokens actifs d'un utilisateur.
     * Appelé avant de créer un nouveau token pour éviter l'accumulation.
     * 
     * @return int Nombre de tokens invalidés
     */
    public function invalidateUserTokens(User $user): int;

    /**
     * Supprime les tokens expirés avant une date donnée.
     * Utilisé par une commande de nettoyage périodique.
     * 
     * @return int Nombre de tokens supprimés
     */
    public function removeExpiredTokens(\DateTimeInterface $before): int;

    /**
     * Force la synchronisation avec la DB.
     */
    public function flush(): void;
}
