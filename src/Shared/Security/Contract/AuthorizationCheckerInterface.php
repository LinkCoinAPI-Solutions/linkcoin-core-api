<?php

declare(strict_types=1);

namespace App\Shared\Security\Contract;

/**
 * Interface pour vérifier les autorisations.
 */
interface AuthorizationCheckerInterface
{
    /**
     * Vérifie si l’utilisateur est autorisé pour un attribut et un sujet donnés.
     */
    public function isGranted(string $attribute, $subject = null): bool;
}
