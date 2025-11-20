<?php

namespace App\Auth\Application\MessageHandler;

use App\Auth\Application\Message\EmailConfirmationMessage;
use App\Auth\Domain\Exception\AuthValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler Messenger pour envoyer l'email de bienvenue après confirmation.
 * Traité de manière asynchrone via la queue Messenger.
 */
#[AsMessageHandler]
final readonly class EmailConfirmationMessageHandler extends AbstractAuthMessageHandler
{
    /**
     * Point d'entrée Symfony Messenger.
     */
    public function __invoke(EmailConfirmationMessage $message): void
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
        if (!$message instanceof EmailConfirmationMessage) {
            throw AuthValidationException::singleError(
                'Invalid message type: EmailConfirmationMessage expected',
                ['provided_type' => get_class($message)]
            );
        }

        $errors = [];

        if (empty($message->getUserEmail())) {
            $errors[] = 'Email is required in EmailConfirmationMessage';
        }

        if (empty($message->getFirstName())) {
            $errors[] = 'First name is required in EmailConfirmationMessage';
        }

        if (!empty($errors)) {
            throw AuthValidationException::fromErrors($errors, [
                'message_type' => 'EmailConfirmationMessage'
            ]);
        }
    }

    /**
     * Envoie l'email de bienvenue post-confirmation.
     */
    protected function processMessage(object $message): void
    {
        $this->authMailerService->sendEmailConfirmedWelcome(
            $message->getUserEmail(),
            $message->getFirstName()
        );
    }

    /**
     * Extrait l'email pour le logging.
     */
    protected function getEmailFromMessage(object $message): string
    {
        return $message->getUserEmail();
    }

    /**
     * Log spécifique au succès de cet email.
     */
    protected function logSuccess(object $message): void
    {
        $this->logger->info('Email confirmation welcome sent successfully', [
            'email' => $message->getUserEmail(),
            'first_name' => $message->getFirstName(),
            'handler' => static::class
        ]);
    }
}
