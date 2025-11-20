<?php

namespace App\Cart\Infrastructure\Security\Voter;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les autorisations sur les paniers.
 * Vérifie que l'utilisateur est propriétaire du panier.
 */
final class CartVoter extends Voter
{
    public const VIEW = 'CART_VIEW';
    public const UPDATE = 'CART_UPDATE';
    public const DELETE = 'CART_DELETE';
    public const ADD_ITEM = 'CART_ADD_ITEM';

    /**
     * Vérifie si le voter supporte l'attribut et le sujet.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::UPDATE, self::DELETE, self::ADD_ITEM], true)
            && $subject instanceof Cart;
    }

    /**
     * Détermine si l'utilisateur a l'autorisation.
     * Retourne true si l'utilisateur possède le panier.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Cart $cart */
        $cart = $subject;

        // Vérifie la propriété du panier
        return $cart->getUser()->getId() === $user->getId();
    }
}
