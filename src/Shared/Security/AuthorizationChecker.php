<?php

declare(strict_types=1);

namespace App\Shared\Security;

use App\Shared\Security\Contract\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface as SymfonyAuthChecker;

/**
 * Adaptateur Symfony pour l'interface AuthorizationCheckerInterface.
 */
final class AuthorizationChecker implements AuthorizationCheckerInterface
{
    public function __construct(
        private SymfonyAuthChecker $authorizationChecker
    ) {}

    public function isGranted(string $attribute, $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $subject);
    }
}
