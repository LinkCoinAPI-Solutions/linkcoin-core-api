<?php

namespace App\Auth\Application\Message;

/**
 * Message asynchrone déclenché après réinitialisation réussie.
 * Notifie l'utilisateur que son mot de passe a été changé.
 */
final readonly class PasswordResetConfirmationMessage
{
    public function __construct(
        private string $email,
        private string $firstName
    ) {}

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }
}
