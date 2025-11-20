<?php

namespace App\Auth\Application\Message;

/**
 * Message asynchrone déclenché après confirmation d'email.
 * Envoie un email de bienvenue à l'utilisateur.
 */
final readonly class EmailConfirmationMessage
{
    public function __construct(
        private string $userEmail,
        private string $firstName
    ) {}

    public function getUserEmail(): string
    {
        return $this->userEmail;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }
}
