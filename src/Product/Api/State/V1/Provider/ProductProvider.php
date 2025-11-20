<?php

declare(strict_types=1);

namespace App\Product\Api\State\V1\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Product\Application\Dto\V1\Output\ProductOutput;
use App\Product\Application\Mapper\Contract\ProductMapperInterface;
use App\Product\Domain\Service\Contract\ProductServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Provider API Platform pour la collection des produits.
 * Route : GET /api/v1/products
 * 
 * Récupère les produits depuis le service métier (avec cache si disponible).
 * Transforme les entités en DTOs pour la réponse API.
 */
final readonly class ProductProvider implements ProviderInterface
{
    public function __construct(
        private ProductServiceInterface $productService,
        private ProductMapperInterface $productMapper,
        private LoggerInterface $logger
    ) {}

    /**
     * Fournit la collection paginée des produits.
     * 
     * Flux :
     * 1. Récupère les produits depuis le service (cache ou DB)
     * 2. Transforme les entités en DTOs
     * 3. Log le succès
     * 4. Retourne la collection (pagination gérée par API Platform)
     *
     * @param Operation $operation Opération API Platform
     * @param array<string, mixed> $uriVariables Variables d'URI (non utilisées pour collection)
     * @param array<string, mixed> $context Contexte de sérialisation
     * @return iterable<ProductOutput> Collection de DTOs produits
     * @throws \Throwable Si la récupération échoue
     */
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        try {
            // Récupère les produits depuis le service domaine
            $products = $this->productService->getAllProducts();

            // Transforme les entités en DTOs en une seule passe
            $mappedProducts = array_map(
                fn($product) => $this->productMapper->toApiOutput($product),
                $products
            );

            // Log léger pour audit et monitoring
            $this->logger->info('Products collection provided successfully', [
                'count' => count($mappedProducts),
                'operation' => $operation->getName(),
            ]);

            return $mappedProducts;

        } catch (\Throwable $e) {
            // Log détaillé de l'erreur pour debugging
            $this->logger->error('Error in ProductProvider', [
                'operation' => $operation->getName(),
                'error' => $e->getMessage(),
                'exception_class' => $e::class,
            ]);

            // Réémet l'exception pour que API Platform gère la réponse HTTP
            throw $e;
        }
    }
}
