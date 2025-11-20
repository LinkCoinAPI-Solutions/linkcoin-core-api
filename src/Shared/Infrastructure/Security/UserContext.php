<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Exception\UserProfileSecurityException;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Implémentation du contexte utilisateur.
 */
final readonly class UserContext implements UserContextInterface
{
    public function __construct(private Security $security) {}

    public function getCurrentUser(): User
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw UserProfileSecurityException::notAuthenticated();
        }

        return $user;
    }

    public function getCurrentUserId(): ?int
    {
        $user = $this->security->getUser();
        return $user instanceof User ? $user->getId() : null;
    }

    public function isUserAuthenticated(): bool
    {
        return $this->security->getUser() instanceof User;
    }
}
