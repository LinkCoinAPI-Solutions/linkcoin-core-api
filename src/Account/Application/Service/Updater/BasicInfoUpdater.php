<?php

namespace App\Account\Application\Service\Updater;

use App\Account\Domain\Entity\User;
use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\Contract\Updater\UserProfileUpdaterInterface;
use App\Account\Domain\Exception\UserProfileApplicationException;
use App\Shared\Domain\ValueObject\PersonName;
use Psr\Log\LoggerInterface;

/**
 * Updater responsable des informations de base (nom et prénom).
 * Utilise le Value Object PersonName pour garantir la validité.
 * Priorité 100 : exécuté en premier car ce sont des champs requis.
 */
readonly class BasicInfoUpdater implements UserProfileUpdaterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Toujours supporté car firstName et lastName sont requis.
     */
    public function supports(UserProfileUpdateInput $input): bool
    {
        return true;
    }

    /**
     * Haute priorité : les infos de base doivent être mises à jour en premier.
     */
    public function getPriority(): int
    {
        return 100;
    }

    /**
     * Met à jour le nom et le prénom de l'utilisateur.
     * Valide via PersonName Value Object.
     * Log les changements pour audit.
     */
    public function update(User $user, UserProfileUpdateInput $input): void
    {
        try {
            // Validation via Value Objects (séparés)
            $newFirstName = PersonName::createFirstName($input->firstName);
            $newLastName = PersonName::createLastName($input->lastName);

            if ($this->hasChanged($user, $newFirstName, $newLastName)) {
                $oldFirstName = $user->getFirstName();
                $oldLastName = $user->getLastName();

                $user->setFirstName($newFirstName->value);
                $user->setLastName($newLastName->value);

                $this->logChange($user->getId(), $oldFirstName, $oldLastName, $newFirstName, $newLastName);
            }
        } catch (\Throwable $e) {
            $this->handleUpdateFailure($user->getId(), $e);
        }
    }

    /**
     * Vérifie si le nom a changé pour éviter les logs inutiles.
     */
    private function hasChanged(User $user, PersonName $newFirstName, PersonName $newLastName): bool
    {
        return $user->getFirstName() !== $newFirstName->value
            || $user->getLastName() !== $newLastName->value;
    }

    /**
     * Log les modifications pour l'audit.
     */
    private function logChange(
        int $userId,
        string $oldFirstName,
        string $oldLastName,
        PersonName $newFirstName,
        PersonName $newLastName
    ): void {
        $this->logger->info('User name updated', [
            'user_id' => $userId,
            'old_first_name' => $oldFirstName,
            'old_last_name' => $oldLastName,
            'new_first_name' => $newFirstName->value,
            'new_last_name' => $newLastName->value
        ]);
    }

    /**
     * Gère les échecs de mise à jour et transforme en exception métier.
     */
    private function handleUpdateFailure(int $userId, \Throwable $e): never
    {
        $this->logger->error('Failed to update basic info', [
            'user_id' => $userId,
            'error' => $e->getMessage(),
        ]);

        throw UserProfileApplicationException::updaterFailed(
            $userId,
            self::class,
            $e->getMessage()
        );
    }
}
