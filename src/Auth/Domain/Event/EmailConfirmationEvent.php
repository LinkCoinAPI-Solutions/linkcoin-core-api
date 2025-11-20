<?php

namespace App\Auth\Domain\Event;

use App\Account\Domain\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché après confirmation d'email réussie.
 * Permet d'envoyer l'email de bienvenue via un listener.
 */
final class EmailConfirmationEvent extends Event
{
    public const NAME = 'user.email_confirmation';

    public function __construct(
        private User $user
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }
}
