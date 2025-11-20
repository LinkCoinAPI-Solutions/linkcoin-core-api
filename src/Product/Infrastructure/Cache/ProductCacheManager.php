<?php

namespace App\Product\Infrastructure\Cache;

use App\Product\Domain\Repository\ProductRepositoryInterface;
use App\Shared\Infrastructure\Service\Cache\CacheService;
use App\Product\Domain\Service\Contract\ProductCacheManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestionnaire de cache pour les produits.
 * Implémente une stratégie de cache simple avec TTL.
 * Réduit la charge sur la base de données pour les listes de produits.
 */
readonly class ProductCacheManager implements ProductCacheManagerInterface
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_ALL = 'products_all';

    public function __construct(
        private ProductRepositoryInterface $productRepository,
        private CacheService $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère tous les produits depuis le cache.
     * 
     * Stratégie :
     * 1. Vérifie si les données sont en cache
     * 2. Si absent : charge depuis la DB et met en cache pour 5 min
     * 3. Si présent : retourne directement depuis le cache
     * 
     * Le repository précharge les relations (subscriptionPlans, productFeatures)
     * pour éviter le problème N+1.
     * 
     * @return Product[] Liste des produits avec leurs relations
     */
    public function getAllProducts(): array
    {
        return $this->cache->remember(
            self::CACHE_KEY_ALL, 
            fn() => $this->productRepository->findAllWithSubscriptionPlansAndFeatures(),
            self::CACHE_TTL
        );
    }

    /**
     * Invalide le cache de tous les produits.
     * 
     * À appeler après :
     * - Création d'un produit
     * - Modification d'un produit (nom, prix, disponibilité)
     * - Suppression d'un produit
     * - Modification d'un plan d'abonnement
     * - Modification d'une feature
     * 
     * Le prochain appel à getAllProducts() rechargera depuis la DB.
     */
    public function invalidateAll(): void
    {
        $this->cache->forget(self::CACHE_KEY_ALL);
        $this->logger->info('Products cache invalidated');
    }
}
