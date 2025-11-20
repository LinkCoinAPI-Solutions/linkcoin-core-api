<?php

namespace App\Auth\Application\MessageHandler;

use App\Auth\Application\Message\PasswordResetRequestMessage;
use App\Auth\Domain\Exception\AuthValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler Messenger pour envoyer le lien de réinitialisation de mot de passe.
 * Traité de manière asynchrone via la queue Messenger.
 */
#[AsMessageHandler]
final readonly class PasswordResetRequestMessageHandler extends AbstractAuthMessageHandler
{
    /**
     * Point d'entrée Symfony Messenger.
     */
    public function __invoke(PasswordResetRequestMessage $message): void
    {
        $this->handleMessage($message);
    }

    /**
     * Valide que toutes les données requises sont présentes.
     * 
     * @throws AuthValidationException Si une donnée manque
     */
    protected function validateMessage(object $message): void
    {
        if (!$message instanceof PasswordResetRequestMessage) {
            throw AuthValidationException::singleError(
                'Invalid message type: PasswordResetRequestMessage expected',
                ['provided_type' => get_class($message)]
            );
        }

        $errors = [];

        if (empty($message->getEmail())) {
            $errors[] = 'Email is required in PasswordResetRequestMessage';
        }

        if (empty($message->getFirstName())) {
            $errors[] = 'First name is required in PasswordResetRequestMessage';
        }

        if (empty($message->getResetLink())) {
            $errors[] = 'Reset link is required in PasswordResetRequestMessage';
        }

        if (!empty($errors)) {
            throw AuthValidationException::fromErrors($errors, [
                'message_type' => 'PasswordResetRequestMessage'
            ]);
        }
    }

    /**
     * Envoie l'email avec le lien de réinitialisation.
     */
    protected function processMessage(object $message): void
    {
        $this->authMailerService->sendRequestPasswordReset(
            $message->getEmail(),
            $message->getFirstName(),
            $message->getResetLink()
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
        $this->logger->info('Password reset request sent successfully', [
            'email' => $message->getEmail(),
            'first_name' => $message->getFirstName(),
            'reset_link_provided' => !empty($message->getResetLink()),
            'handler' => static::class
        ]);
    }
}
