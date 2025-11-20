<?php

namespace App\Auth\Domain\Event;

use App\Account\Domain\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché après réinitialisation de mot de passe réussie.
 * Permet de notifier l'utilisateur que son mot de passe a été changé.
 */
final class PasswordResetConfirmationEvent extends Event
{
    public const NAME = 'user.password_reset_confirmation';

    public function __construct(
        private User $user
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }
}
