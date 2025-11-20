<?php

namespace App\Account\Application\Service\Updater;

use App\Account\Domain\Entity\User;
use App\Account\Domain\ValueObject\Bio;
use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\Contract\Updater\UserProfileUpdaterInterface;
use App\Account\Domain\Exception\UserProfileApplicationException;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Url;
use Psr\Log\LoggerInterface;

/**
 * Updater responsable des informations détaillées du profil.
 * Utilise les Value Objects (PhoneNumber, Url, Bio) pour garantir la validité.
 * Priorité 50 : exécuté entre les infos de base et les préférences.
 */
readonly class ProfileInfoUpdater implements UserProfileUpdaterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Supporté si au moins un champ de profil détaillé est fourni.
     */
    public function supports(UserProfileUpdateInput $input): bool
    {
        return $input->phone !== null
            || $input->company !== null
            || $input->position !== null
            || $input->bio !== null
            || $input->website !== null
            || $input->location !== null;
    }

    /**
     * Priorité moyenne : entre les infos de base et les préférences.
     */
    public function getPriority(): int
    {
        return 50;
    }

    /**
     * Met à jour les informations détaillées du profil.
     * Utilise les Value Objects pour validation.
     * Lance une exception si le profil n'existe pas.
     */
    public function update(User $user, UserProfileUpdateInput $input): void
    {
        $profile = $user->getProfile();
        
        if ($profile === null) {
            throw UserProfileApplicationException::missingProfile($user->getId());
        }

        try {
            if ($input->phone !== null) {
                $this->updatePhone($profile, $input->phone);
            }
            
            if ($input->website !== null) {
                $this->updateWebsite($profile, $input->website);
            }
            
            if ($input->bio !== null) {
                $this->updateBio($profile, $input->bio);
            }
            
            if ($input->company !== null || $input->position !== null) {
                $this->updateCompanyInfo($profile, $input->company, $input->position);
            }
            
            if ($input->location !== null) {
                $this->updateLocation($profile, $input->location);
            }
            
            $this->logger->info('Profile info updated', ['user_id' => $user->getId()]);
        } catch (\Throwable $e) {
            $this->handleUpdateFailure($user->getId(), $e);
        }
    }

    /**
     * Met à jour le téléphone avec validation via Value Object.
     * Empty string pour supprimer le téléphone.
     */
    private function updatePhone($profile, string $phoneInput): void
    {
        // Empty string clears the field
        if ($phoneInput === '') {
            $profile->setPhone(null);
            return;
        }

        $phone = PhoneNumber::fromString($phoneInput);
        $profile->setPhone($phone->value);
    }

    /**
     * Met à jour le site web avec validation via Value Object.
     * Empty string pour supprimer le site web.
     */
    private function updateWebsite($profile, string $websiteInput): void
    {
        // Empty string clears the field
        if ($websiteInput === '') {
            $profile->setWebsite(null);
            return;
        }

        $website = Url::fromString($websiteInput);
        $profile->setWebsite($website->value);
    }

    /**
     * Met à jour la bio avec validation via Value Object.
     * Empty string pour supprimer la bio.
     */
    private function updateBio($profile, string $bioInput): void
    {
        // Empty string clears the field
        if ($bioInput === '') {
            $profile->setBio(null);
            return;
        }

        $bio = Bio::fromString($bioInput);
        $profile->setBio($bio->isEmpty() ? null : $bio->value);
    }

    /**
     * Met à jour l'entreprise et le poste (pas de validation spécifique).
     */
    private function updateCompanyInfo($profile, ?string $company, ?string $position): void
    {
        if ($company !== null) {
            $profile->setCompany($this->trimOrNull($company));
        }
        
        if ($position !== null) {
            $profile->setPosition($this->trimOrNull($position));
        }
    }

    /**
     * Met à jour la localisation (pas de validation spécifique).
     */
    private function updateLocation($profile, string $location): void
    {
        $profile->setLocation($this->trimOrNull($location));
    }

    /**
     * Trim une valeur et retourne null si vide.
     */
    private function trimOrNull(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $trimmed = trim($value);
        return $trimmed !== '' ? $trimmed : null;
    }

    /**
     * Gère les échecs de mise à jour et transforme en exception métier.
     */
    private function handleUpdateFailure(int $userId, \Throwable $e): never
    {
        $this->logger->error('Failed to update profile info', [
            'user_id' => $userId,
            'error' => $e->getMessage()
        ]);
        
        throw UserProfileApplicationException::updaterFailed(
            $userId,
            self::class,
            $e->getMessage()
        );
    }
}
