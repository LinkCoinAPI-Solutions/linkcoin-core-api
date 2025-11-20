<?php

namespace App\Auth\Domain\Event;

use App\Account\Domain\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Événement déclenché après création de compte réussie.
 * Permet d'envoyer l'email de confirmation via un listener.
 */
final class UserRegistrationEvent extends Event
{
    public const NAME = 'user.registration';

    public function __construct(
        private User $user,
        private string $confirmationUrl
    ) {}

    public function getUser(): User
    {
        return $this->user;
    }

    public function getConfirmationUrl(): string
    {
        return $this->confirmationUrl;
    }
}
