<?php

namespace App\Auth\Application\MessageHandler;

use App\Auth\Application\Message\RegistrationWelcomeMessage;
use App\Auth\Domain\Exception\AuthValidationException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler Messenger pour envoyer l'email de bienvenue après inscription.
 * Traité de manière asynchrone via la queue Messenger.
 */
#[AsMessageHandler]
final readonly class RegistrationWelcomeMessageHandler extends AbstractAuthMessageHandler
{
    /**
     * Point d'entrée Symfony Messenger.
     */
    public function __invoke(RegistrationWelcomeMessage $message): void
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
        if (!$message instanceof RegistrationWelcomeMessage) {
            throw AuthValidationException::singleError(
                'Invalid message type: RegistrationWelcomeMessage expected',
                ['provided_type' => get_class($message)]
            );
        }

        $errors = [];

        if (empty($message->getEmail())) {
            $errors[] = 'Email is required in RegistrationWelcomeMessage';
        }

        if (empty($message->getFirstName())) {
            $errors[] = 'First name is required in RegistrationWelcomeMessage';
        }

        if (empty($message->getConfirmationUrl())) {
            $errors[] = 'Confirmation URL is required in RegistrationWelcomeMessage';
        }

        if (!empty($errors)) {
            throw AuthValidationException::fromErrors($errors, [
                'message_type' => 'RegistrationWelcomeMessage'
            ]);
        }
    }

    /**
     * Envoie l'email de bienvenue avec le lien de confirmation.
     */
    protected function processMessage(object $message): void
    {
        $this->authMailerService->sendWelcomeEmail(
            $message->getEmail(),
            $message->getFirstName(),
            $message->getConfirmationUrl()
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
        $this->logger->info('Registration welcome email sent successfully', [
            'email' => $message->getEmail(),
            'first_name' => $message->getFirstName(),
            'confirmation_url_provided' => !empty($message->getConfirmationUrl()),
            'handler' => static::class
        ]);
    }
}
