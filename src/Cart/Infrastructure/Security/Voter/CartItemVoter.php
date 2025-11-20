<?php

namespace App\Cart\Infrastructure\Security\Voter;

use App\Cart\Domain\Entity\CartItem;
use App\Account\Domain\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter pour les autorisations sur les items du panier.
 * Vérifie que l'utilisateur possède le panier contenant l'item.
 */
final class CartItemVoter extends Voter
{
    public const VIEW = 'CART_ITEM_VIEW';
    public const UPDATE = 'CART_ITEM_UPDATE';
    public const DELETE = 'CART_ITEM_DELETE';

    /**
     * Vérifie si le voter supporte l'attribut et le sujet.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::UPDATE, self::DELETE], true)
            && $subject instanceof CartItem;
    }

    /**
     * Détermine si l'utilisateur a l'autorisation.
     * Vérifie la propriété via le panier parent.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var CartItem $cartItem */
        $cartItem = $subject;

        // Vérifie la propriété via le panier parent
        return $cartItem->getCart()->getUser()->getId() === $user->getId();
    }
}
