<?php

namespace App\Account\Domain\Exception;

/**
 * Exception pour les erreurs de sécurité du profil utilisateur.
 * Gère l'authentification, l'autorisation et l'accès aux profils.
 */
final class UserProfileSecurityException extends UserProfileException
{

    /**
     * Token invalide ou expiré (401 Unauthorized).
     * Masque le token pour la sécurité (8 premiers caractères).
     */
    public static function invalidToken(string $token): self
    {
        return new self(
            sprintf('Invalid or expired token: %s', substr($token, 0, 8) . '...'),
            self::ERROR_INVALID_TOKEN,
            ['token_preview' => substr($token, 0, 8) . '...'],
            401
        );
    }

    /**
     * Utilisateur inactif (403 Forbidden).
     * Les comptes inactifs ne peuvent pas accéder aux opérations de profil.
     */
    public static function inactiveUser(int $userId): self
    {
        return new self(
            'User is inactive and cannot access profile operations.',
            self::ERROR_INACTIVE_USER,
            ['user_id' => $userId],
            403
        );
    }
}
