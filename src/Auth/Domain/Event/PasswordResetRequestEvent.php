<?php

namespace App\Auth\Domain\Event;

use App\Account\Domain\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché lors d'une demande de réinitialisation de mot de passe.
 * Permet d'envoyer l'email avec le lien de réinitialisation via un listener.
 */
final class PasswordResetRequestEvent extends Event
{
    public const NAME = 'user.password_reset_request';

    public function __construct(
        private User $user,
        private string $resetLink
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getResetLink(): string
    {
        return $this->resetLink;
    }
}
