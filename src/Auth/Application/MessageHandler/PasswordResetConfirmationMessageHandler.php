<?php

namespace App\Auth\Application\MessageHandler;

use App\Auth\Application\Message\PasswordResetConfirmationMessage;
use App\Auth\Domain\Exception\AuthValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler Messenger pour notifier l'utilisateur après réinitialisation réussie.
 * Traité de manière asynchrone via la queue Messenger.
 */
#[AsMessageHandler]
final readonly class PasswordResetConfirmationMessageHandler extends AbstractAuthMessageHandler
{
    /**
     * Point d'entrée Symfony Messenger.
     */
    public function __invoke(PasswordResetConfirmationMessage $message): void
    {
        $this->handleMessage($message);
    }

    /**
     * Valide que l'email et le prénom sont présents.
     * 
     * @throws AuthValidationException Si une donnée manque
     */
    protected function validateMessage(object $message): void
    {
        if (!$message instanceof PasswordResetConfirmationMessage) {
            throw AuthValidationException::singleError(
                'Invalid message type: PasswordResetConfirmationMessage expected',
                ['provided_type' => get_class($message)]
            );
        }

        $errors = [];

        if (empty($message->getEmail())) {
            $errors[] = 'Email is required in PasswordResetConfirmationMessage';
        }

        if (empty($message->getFirstName())) {
            $errors[] = 'First name is required in PasswordResetConfirmationMessage';
        }

        if (!empty($errors)) {
            throw AuthValidationException::fromErrors($errors, [
                'message_type' => 'PasswordResetConfirmationMessage'
            ]);
        }
    }

    /**
     * Envoie l'email de confirmation de réinitialisation.
     */
    protected function processMessage(object $message): void
    {
        $this->authMailerService->sendPasswordResetConfirmation(
            $message->getEmail(),
            $message->getFirstName()
        );
    }

    /**
     * Extrait l'email pour le logging.
     */
    protected function getEmailFromMessage(object $message): string
    {
        return $message->getEmail();
    }

    /**
     * Log spécifique au succès de cet email.
     */
    protected function logSuccess(object $message): void
    {
        $this->logger->info('Password reset confirmation sent successfully', [
            'email' => $message->getEmail(),
            'first_name' => $message->getFirstName(),
            'handler' => static::class
        ]);
    }
}
