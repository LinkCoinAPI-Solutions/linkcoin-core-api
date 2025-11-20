<?php

declare(strict_types=1);

namespace App\Cart\Api\ApiResource\V1;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Delete;
use App\Cart\Application\Dto\V1\Input\AddCartItemInput;
use App\Cart\Application\Dto\V1\Input\UpdateCartItemInput;
use App\Cart\Application\Dto\V1\Input\ApplyDiscountInput;
use App\Cart\Application\Dto\V1\Output\CartOutput;
use App\Cart\Api\State\V1\Provider\CartProvider;
use App\Cart\Api\State\V1\Processor\AddCartItemProcessor;
use App\Cart\Api\State\V1\Processor\UpdateCartItemProcessor;
use App\Cart\Api\State\V1\Processor\RemoveCartItemProcessor;
use App\Cart\Api\State\V1\Processor\ClearCartProcessor;
use App\Cart\Api\State\V1\Processor\ApplyDiscountProcessor;
use App\Cart\Api\State\V1\Processor\RemoveDiscountProcessor;

/**
 * Ressource API Platform pour la gestion complète du panier.
 * Regroupe toutes les opérations CRUD sur le panier et ses items.
 * Requiert une authentification pour toutes les routes.
 */
#[ApiResource(
    shortName: 'Cart',
    output: CartOutput::class,
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    operations: [
        // Récupération du panier courant
        new Get(
            uriTemplate: '/v1/cart',
            name: 'api_v1_cart_get',
            provider: CartProvider::class,
            normalizationContext: ['groups' => ['cart:read']],
        ),
        
        // Ajout d'un produit au panier
        new Post(
            uriTemplate: '/v1/cart/items',
            name: 'api_v1_cart_add_item',
            input: AddCartItemInput::class,
            processor: AddCartItemProcessor::class,
            denormalizationContext: ['groups' => ['cart:write']],
            normalizationContext: ['groups' => ['cart:read', 'product:read']],
        ),
        
        // Modification de la quantité d'un item
        new Put(
            uriTemplate: '/v1/cart/items/{itemId}',
            name: 'api_v1_cart_update_item',
            read: false, // Pas de lecture préalable, l'ID est dans l'URI
            input: UpdateCartItemInput::class,
            processor: UpdateCartItemProcessor::class,
            denormalizationContext: ['groups' => ['cart:write']],
            normalizationContext: ['groups' => ['cart:read', 'product:read']],
        ),
        
        // Suppression d'un item du panier
        new Delete(
            uriTemplate: '/v1/cart/items/{itemId}',
            name: 'api_v1_cart_remove_item',
            read: false, // Pas de lecture préalable
            status: 200, // Retourne 200 avec le panier mis à jour (pas 204)
            processor: RemoveCartItemProcessor::class,
            normalizationContext: ['groups' => ['cart:read', 'product:read']],
        ),
        
        // Vidage complet du panier
        new Delete(
            uriTemplate: '/v1/cart',
            name: 'api_v1_remove_cart',
            processor: ClearCartProcessor::class,
            normalizationContext: ['groups' => ['cart:read']],
        ),
        
        // Application d'un code promo
        new Post(
            uriTemplate: '/v1/cart/discount',
            name: 'api_v1_cart_apply_discount',
            input: ApplyDiscountInput::class,
            processor: ApplyDiscountProcessor::class,
            denormalizationContext: ['groups' => ['cart:write']],
            normalizationContext: ['groups' => ['cart:read', 'product:read']],
        ),
        
        // Retrait du code promo
        new Delete(
            uriTemplate: '/v1/cart/discount',
            name: 'api_v1_cart_remove_discount',
            read: false,
            status: 200, // Retourne 200 avec le panier mis à jour
            processor: RemoveDiscountProcessor::class,
            normalizationContext: ['groups' => ['cart:read', 'product:read']],
        ),
    ]
)]
final readonly class CartApiResource
{
    public const VERSION = 'v1';
    public const BASE_PATH = '/api/v1/cart';
}
