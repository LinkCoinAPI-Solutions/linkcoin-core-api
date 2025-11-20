<?php

namespace App\Account\Application\Service\Factory;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Entity\UserProfile;
use App\Account\Domain\Entity\NotificationPreferences;

/**
 * Factory responsable de la création des entités liées au profil utilisateur.
 * Garantit que les entités sont correctement initialisées et liées à l'utilisateur.
 */
readonly class UserProfileFactory
{
    /**
     * Crée un profil utilisateur vide lié à l'utilisateur.
     * Appelé automatiquement si le profil n'existe pas lors d'un accès.
     */
    public function createProfile(User $user): UserProfile
    {
        $profile = new UserProfile();
        $profile->setUser($user);
        
        return $profile;
    }

    /**
     * Crée les préférences de notification avec les valeurs par défaut.
     * Appelé automatiquement si les préférences n'existent pas.
     */
    public function createNotificationPreferences(User $user): NotificationPreferences
    {
        $preferences = new NotificationPreferences();
        $preferences->setUser($user);
        
        return $preferences;
    }
}
