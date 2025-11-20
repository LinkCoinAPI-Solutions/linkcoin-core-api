<?php

declare(strict_types=1);

namespace App\Cart\Application\Mapper;

use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Domain\Entity\Cart;
use App\Cart\Domain\Enum\CartStatus;
use App\Cart\Application\Mapper\Contract\CartMapperInterface;
use App\Cart\Application\Mapper\Contract\CartItemMapperInterface;
use App\Discount\Application\Mapper\Contract\DiscountMapperInterface;

/**
 * Mapper pour transformer les paniers en DTOs API.
 * Agrège les items, réductions et totaux calculés.
 */
final readonly class CartMapper implements CartMapperInterface
{
    public function __construct(
        private CartItemMapperInterface $cartItemMapper,
        private DiscountMapperInterface $discountMapper
    ) {}

    /**
     * Transforme un Cart complet en DTO avec tous ses items et réductions.
     */
    public function toApiOutput(Cart $cart): CartOutput
    {
        // Mapping de tous les items du panier
        $items = array_values(array_map(
            fn($item) => $this->cartItemMapper->toApiOutput($item),
            $cart->getItems()->toArray()
        ));

        // Mapping de la réduction si présente
        $discount = $cart->getDiscount();
        $discountDto = null;

        if ($discount) {
            $discountDto = $this->discountMapper->toApiOutput(
                $discount,
                (float) $cart->getDiscountAmount(),
                (float) $discount->getDiscountPercentage($cart->getTotalAmount())
            );
        }

        return new CartOutput(
            cartId: $cart->getId(),
            userId: $cart->getUser()?->getId(),
            status: $cart->getStatus()->value,
            totalAmount: (float) $cart->getTotalAmount(),
            discountAmount: (float) ($cart->getDiscountAmount()),
            finalAmount: (float) ($cart->getFinalAmount()),
            createdAt: $cart->getCreatedAt(),
            updatedAt: $cart->getUpdatedAt(),
            items: $items,
            totalItems: $cart->getItemCount(),
            totalQuantity: $cart->getTotalQuantity(),
            sessionId: $cart->getSessionId(),
            discount: $discountDto
        );
    }

    /**
     * Crée un DTO représentant un panier vide.
     * Utilisé quand aucun panier n'existe encore pour l'utilisateur.
     */
    public function createEmptyCartApiOutput(): CartOutput
    {
        return new CartOutput(
            cartId: null,
            userId: null,
            status: CartStatus::PENDING->value,
            totalAmount: 0.0,
            discountAmount: 0.0,
            finalAmount: 0.0,
            createdAt: new \DateTimeImmutable(),
            updatedAt: null,
            items: [],
            totalItems: 0,
            totalQuantity: 0,
            sessionId: null,
            discount: null
        );
    }
}
