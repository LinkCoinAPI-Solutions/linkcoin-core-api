<?php

namespace App\Auth\Application\Message;

/**
 * Message asynchrone déclenché lors d'une demande de réinitialisation.
 * Envoie l'email avec le lien de réinitialisation.
 */
final readonly class PasswordResetRequestMessage
{
    public function __construct(
        private string $email,
        private string $firstName,
        private string $resetLink
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getResetLink(): string
    {
        return $this->resetLink;
    }
}
