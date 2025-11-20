<?php

namespace App\Product\Application\Service;

use App\Product\Domain\Service\Contract\ProductServiceInterface;
use App\Product\Domain\Service\Contract\ProductCacheManagerInterface;
use App\Product\Domain\Exception\ProductException;
use App\Product\Domain\Entity\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Service applicatif de gestion des produits.
 * Orchestre la récupération depuis le cache ou la base de données.
 * Gère les erreurs et la validation de disponibilité.
 */
readonly class ProductService implements ProductServiceInterface
{
    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private ?ProductCacheManagerInterface $productCache, // Injection optionnelle (peut être null)
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère tous les produits disponibles.
     * 
     * Stratégie de récupération :
     * 1. Si ProductCacheInterface injecté → utilise le cache
     * 2. Sinon → requête directe en base de données
     * 
     * Le repository précharge les relations (subscription plans, features)
     * pour éviter le problème N+1.
     * 
     * @return Product[] Liste des produits avec leurs relations
     * @throws ProductException Si la récupération échoue
     */
    public function getAllProducts(): array
    {
        try {
            // Récupération avec cache transparent si disponible
            $products = $this->productCache 
                ? $this->productCache->getAllProducts()
                : $this->productRepository->findAllWithSubscriptionPlansAndFeatures();
            
            $this->logger->info('Products retrieved successfully', [
                'count' => count($products),
                'from_cache' => $this->productCache !== null
            ]);
            
            return $products;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to retrieve products', [
                'error' => $e->getMessage()
            ]);
            
            throw ProductException::apiMisconfigured('product_retrieval', $e->getMessage());
        }
    }

    /**
     * Récupère un produit par son ID.
     * 
     * Règles métier :
     * - Le produit doit exister
     * - Le produit doit être disponible (isAvailable = true)
     * 
     * Le repository précharge les relations pour éviter le problème N+1.
     * 
     * @param int $productId ID du produit
     * @return Product Produit avec ses relations
     * @throws ProductException Si produit introuvable ou indisponible
     */
    public function getProductById(int $productId): Product
    {
        $product = $this->productRepository->findOneWithSubscriptionPlansAndFeatures($productId);
        
        // Vérifie l'existence et la disponibilité
        if (!$product || !$product->isAvailable()) {
            throw ProductException::notFound($productId);
        }
        
        return $product;
    }
}
