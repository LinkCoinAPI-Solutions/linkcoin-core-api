<?php

namespace App\Account\Application\Service\Updater;

use App\Account\Domain\Entity\User;
use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Service\Contract\Updater\UserProfileUpdaterInterface;
use App\Account\Domain\Exception\UserProfileApplicationException;
use Psr\Log\LoggerInterface;

/**
 * Updater responsable des préférences de notification.
 * Priorité 10 : exécuté en dernier car moins critique.
 */
readonly class NotificationUpdater implements UserProfileUpdaterInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    /**
     * Supporté uniquement si au moins une préférence est fournie.
     */
    public function supports(UserProfileUpdateInput $input): bool
    {
        return $input->emailNotifications !== null
            || $input->emailSecurityAlerts !== null
            || $input->pushNotifications !== null
            || $input->newsletter !== null;
    }

    /**
     * Basse priorité : les préférences ne sont pas critiques.
     */
    public function getPriority(): int
    {
        return 10;
    }

    /**
     * Met à jour les préférences de notification.
     * Lance une exception si les préférences n'existent pas.
     */
    public function update(User $user, UserProfileUpdateInput $input): void
    {
        $preferences = $user->getNotificationPreferences();
        
        if ($preferences === null) {
            throw UserProfileApplicationException::missingNotificationPreferences($user->getId());
        }

        try {
            // Met à jour uniquement les champs non-null (fluent interface)
            $preferences
                ->setEmailNotifications($input->emailNotifications)
                ->setEmailSecurityAlerts($input->emailSecurityAlerts)
                ->setPushNotifications($input->pushNotifications)
                ->setNewsletter($input->newsletter);
            
            $this->logger->info('Notification preferences updated', [
                'user_id' => $user->getId()
            ]);
        } catch (\Throwable $e) {
            $this->handleUpdateFailure($user->getId(), $e);
        }
    }

    /**
     * Gère les échecs de mise à jour et transforme en exception métier.
     */
    private function handleUpdateFailure(int $userId, \Throwable $e): never
    {
        $this->logger->error('Failed to update notification preferences', [
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
