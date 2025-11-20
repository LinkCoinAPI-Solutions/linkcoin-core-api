<?php

namespace App\Account\Domain\Exception;

/**
 * Exception pour les erreurs de la couche Application du profil utilisateur.
 * Gère les erreurs de persistence, cache, factory et updaters.
 * Code HTTP 500 par défaut car ce sont des erreurs internes.
 */
final class UserProfileApplicationException extends UserProfileException
{
    /**
     * Erreur de persistence en base de données.
     */
    public static function persistenceFailed(int $userId, string $reason): self
    {
        return new self(
            'Error saving profile to database',
            self::ERROR_PERSISTENCE_FAILED,
            [
                'user_id' => $userId,
                'persistence_error' => $reason
            ],
            500
        );
    }

    /**
     * Erreur lors de l'exécution d'un updater.
     * Indique quel updater a échoué et pourquoi.
     */
    public static function updaterFailed(int $userId, string $updaterType, string $reason): self
    {
        return new self(
            sprintf('Updater %s failed for user: %s', $updaterType, $reason),
            self::ERROR_MAPPING_FAILED,
            [
                'user_id' => $userId,
                'updater_type' => $updaterType,
                'failure_reason' => $reason
            ],
            500
        );
    }

    /**
     * Entité UserProfile manquante (ne devrait jamais arriver).
     * Indique un problème de cohérence des données.
     */
    public static function missingProfile(int $userId): self
    {
        return new self(
            sprintf('UserProfile is missing for user #%d. This should not happen.', $userId),
            self::ERROR_MISSING_PROFILE_ENTITY,
            ['user_id' => $userId, 'entity_type' => 'UserProfile'],
            500
        );
    }

    /**
     * Entité NotificationPreferences manquante (ne devrait jamais arriver).
     * Indique un problème de cohérence des données.
     */
    public static function missingNotificationPreferences(int $userId): self
    {
        return new self(
            sprintf('NotificationPreferences are missing for user #%d. This should not happen.', $userId),
            self::ERROR_MISSING_PROFILE_ENTITY,
            ['user_id' => $userId, 'entity_type' => 'NotificationPreferences'],
            500
        );
    }
}
