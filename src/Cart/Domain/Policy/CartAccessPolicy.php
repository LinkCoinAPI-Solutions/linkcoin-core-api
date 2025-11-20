<?php

declare(strict_types=1);

namespace App\Cart\Domain\Policy;

use App\Cart\Domain\Entity\Cart;
use App\Shared\Security\Contract\AuthorizationCheckerInterface;
use App\Cart\Domain\Exception\CartSecurityException;
use App\Cart\Infrastructure\Security\Voter\CartVoter;

/**
 * Politique d'accès pour les opérations sur le panier.
 * Vérifie les autorisations via les Voters Symfony.
 */
final readonly class CartAccessPolicy implements CartAccessPolicyInterface
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    public function assertCanAddItem(Cart $cart): void
    {
        if (!$this->authorizationChecker->isGranted(CartVoter::ADD_ITEM, $cart)) {
            throw CartSecurityException::accessDenied('add_item');
        }
    }

    public function assertCanUpdate(Cart $cart): void
    {
        if (!$this->authorizationChecker->isGranted(CartVoter::UPDATE, $cart)) {
            throw CartSecurityException::accessDenied('update');
        }
    }

    public function assertCanView(Cart $cart): void
    {
        if (!$this->authorizationChecker->isGranted(CartVoter::VIEW, $cart)) {
            throw CartSecurityException::accessDenied('view');
        }
    }

    public function assertCanDelete(Cart $cart): void
    {
        if (!$this->authorizationChecker->isGranted(CartVoter::DELETE, $cart)) {
            throw CartSecurityException::accessDenied('delete');
        }
    }
}
