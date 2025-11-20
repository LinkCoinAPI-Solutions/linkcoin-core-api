<?php

namespace App\Auth\Application\Message;

/**
 * Message asynchrone déclenché après inscription.
 * Envoie l'email de bienvenue avec le lien de confirmation.
 */
final readonly class RegistrationWelcomeMessage
{
    public function __construct(
        private string $email,
        private string $firstName,
        private string $confirmationUrl
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getConfirmationUrl(): string
    {
        return $this->confirmationUrl;
    }
}
