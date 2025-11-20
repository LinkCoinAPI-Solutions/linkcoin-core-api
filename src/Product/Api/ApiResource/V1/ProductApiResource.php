<?php

declare(strict_types=1);

namespace App\Product\Api\ApiResource\V1;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Product\Application\Dto\V1\Output\ProductOutput;
use App\Product\Api\State\V1\Provider\ProductProvider;

/**
 * Ressource API Platform pour la gestion des produits.
 * Fournit uniquement la lecture (GET collection) pour les utilisateurs authentifiés.
 * La gestion CRUD complète (création/modification/suppression) est réservée à l'admin.
 */
#[ApiResource(
    shortName: 'Product',
    output: ProductOutput::class,
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    operations: [
        // Récupération de la liste des produits avec pagination
        new GetCollection(
            uriTemplate: '/v1/products',
            provider: ProductProvider::class,
            normalizationContext: ['groups' => ['product:list']],
            paginationEnabled: true,
            paginationItemsPerPage: 30, // 30 produits par page
        ),
    ]
)]
final readonly class ProductApiResource
{
    public const VERSION = 'v1';
    public const BASE_PATH = '/api/v1/products';
}
