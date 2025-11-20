<?php

declare(strict_types=1);

namespace App\Account\Application\Mapper;

use App\Account\Application\Dto\V1\Output\UserProfileOutput;
use App\Account\Application\Mapper\Contract\UserProfileMapperInterface;
use App\Account\Domain\Entity\User;

/**
 * Mapper pour transformer User en UserProfileOutput.
 * Agrège les données de User, Profile et NotificationPreferences.
 */
final readonly class UserProfileMapper implements UserProfileMapperInterface
{
    /**
     * Transforme l'entité User en DTO API.
     * Récupère les relations Profile et NotificationPreferences.
     * Formate les dates au format ISO 8601 (DATE_ATOM).
     */
    public function toApiOutput(User $user): UserProfileOutput
    {
        $profile = $user->getProfile();
        $preferences = $user->getNotificationPreferences();

        return new UserProfileOutput(
            id: $user->getId(),
            email: $user->getEmail(),
            firstName: $user->getFirstName(),
            lastName: $user->getLastName(),
            
            // Données du profil (optionnelles)
            phone: $profile?->getPhone(),
            company: $profile?->getCompany(),
            position: $profile?->getPosition(),
            bio: $profile?->getBio(),
            website: $profile?->getWebsite(),
            location: $profile?->getLocation(),
            avatarUrl: $profile?->getAvatarUrl(),
            
            // Statuts du compte
            isActive: $user->isActive(),
            isEmailVerified: $user->isEmailVerified(),
            
            // Préférences de notification (optionnelles)
            emailNotifications: $preferences?->isEmailNotifications(),
            emailSecurityAlerts: $preferences?->isEmailSecurityAlerts(),
            pushNotifications: $preferences?->isPushNotifications(),
            newsletter: $preferences?->isNewsletter(),
            
            // Dates
            createdAt: $this->formatDate($user->getCreatedAt()),
            updatedAt: $this->formatDate($user->getUpdatedAt()),
        );
    }

    /**
     * Formate une date au format ISO 8601.
     * Retourne null si la date est absente.
     */
    private function formatDate(?\DateTimeImmutable $date): ?string
    {
        return $date?->format(DATE_ATOM);
    }
}
