<?php

namespace App\Cart\Domain\Exception;

/**
 * Exception pour les erreurs de sécurité du panier.
 * Gère les problèmes d'accès et d'autorisation.
 */
final class CartSecurityException extends CartException
{
    // Codes d'erreur sécurité
    public const ERROR_ACCESS_DENIED = 'cart_access_denied';
    public const ERROR_OWNERSHIP_MISMATCH = 'ownership_mismatch';
    public const ERROR_USER_NOT_AUTHENTICATED = 'user_not_authenticated';

    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_ACCESS_DENIED => 'Cart Access Denied',
            self::ERROR_OWNERSHIP_MISMATCH => 'Ownership Mismatch',
            self::ERROR_USER_NOT_AUTHENTICATED => 'Authentication Required',
            default => parent::getTitle()
        };
    }

    public static function accessDenied(string $action = ''): self
    {
        $message = 'Access denied to cart';
        if ($action) {
            $message .= " for action: {$action}";
        }

        return new self(
            $message,
            self::ERROR_ACCESS_DENIED,
            ['action' => $action],
            403
        );
    }

    public static function ownershipMismatch(int $cartId, int $userId): self
    {
        return new self(
            "Cart {$cartId} does not belong to user {$userId}",
            self::ERROR_OWNERSHIP_MISMATCH,
            ['cart_id' => $cartId, 'user_id' => $userId],
            403
        );
    }

    public static function userNotAuthenticated(): self
    {
        return new self(
            'Authentication required to access cart',
            self::ERROR_USER_NOT_AUTHENTICATED,
            [],
            401
        );
    }
}
