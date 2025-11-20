<?php

namespace App\Cart\Domain\Exception;

/**
 * Exception pour les erreurs sur les items du panier.
 * Gère les validations et opérations spécifiques aux CartItem.
 */
final class CartItemException extends CartException
{
    // Codes d'erreur spécifiques aux items
    public const ERROR_ITEM_NOT_FOUND = 'cart_item_not_found';
    public const ERROR_INVALID_QUANTITY = 'invalid_quantity';
    public const ERROR_CANNOT_MODIFY = 'cannot_modify_cart_item';

    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_ITEM_NOT_FOUND => 'Cart Item Not Found',
            self::ERROR_INVALID_QUANTITY => 'Invalid Quantity',
            self::ERROR_CANNOT_MODIFY => 'Cannot Modify Cart Item',
            default => parent::getTitle()
        };
    }

    public static function itemNotFound(int $itemId): self
    {
        return new self(
            "Cart item not found: {$itemId}",
            self::ERROR_ITEM_NOT_FOUND,
            ['item_id' => $itemId],
            404
        );
    }

    public static function invalidQuantityWithLimits(int $quantity, int $min, int $max): self
    {
        return new self(
            "Invalid quantity {$quantity}. Must be between {$min} and {$max}",
            self::ERROR_INVALID_QUANTITY,
            [
                'quantity' => $quantity,
                'min_quantity' => $min,
                'max_quantity' => $max
            ],
            422
        );
    }

    public static function cannotModifyCartItem(int $cartItemId, string $reason): self
    {
        return new self(
            "Cannot modify cart item {$cartItemId}: {$reason}",
            self::ERROR_CANNOT_MODIFY,
            [
                'cart_item_id' => $cartItemId,
                'reason' => $reason
            ],
            409
        );
    }

    public static function cartInInvalidState(int $cartItemId, string $cartStatus): self
    {
        return new self(
            "Cannot modify cart item {$cartItemId}: cart is in '{$cartStatus}' state",
            self::ERROR_CANNOT_MODIFY,
            [
                'cart_item_id' => $cartItemId,
                'cart_status' => $cartStatus
            ],
            409
        );
    }
}
