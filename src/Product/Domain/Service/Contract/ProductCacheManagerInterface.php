<?php

namespace App\Product\Domain\Service\Contract;

use App\Product\Domain\Entity\Product;

/**
 * Interface pour le cache des produits.
 * Implémentation possible : Redis, Memcached, Symfony Cache.
 * Injection optionnelle dans ProductService (null autorisé).
 */
interface ProductCacheManagerInterface
{
    /**
     * Récupère tous les produits depuis le cache.
     * Si absent du cache, charge depuis la DB et met en cache.
     * 
     * @return Product[] Liste des produits avec relations
     */
    public function getAllProducts(): array;

    /**
     * Invalide le cache de tous les produits.
     * Appelé après création/modification/suppression d'un produit.
     */
    public function invalidateAll(): void;
}
