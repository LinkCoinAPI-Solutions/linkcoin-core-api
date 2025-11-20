<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security\Contract;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Exception\UserProfileSecurityException;

/**
 * Accès au contexte utilisateur courant.
 */
interface UserContextInterface
{
    /**
     * Retourne l'utilisateur authentifié.
     *
     * @throws UserProfileSecurityException Si non authentifié
     */
    public function getCurrentUser(): User;

    /**
     * Retourne l'ID utilisateur ou null si non authentifié.
     */
    public function getCurrentUserId(): ?int;

    /**
     * Indique si un utilisateur est authentifié.
     */
    public function isUserAuthenticated(): bool;
}
