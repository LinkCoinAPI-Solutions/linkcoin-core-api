<?php

namespace App\Product\Domain\Repository;

use App\Product\Domain\Entity\Product;

/**
 * Interface de repository pour l'entité Product.
 * Définit les opérations de persistence et de recherche des produits.
 */
interface ProductRepositoryInterface
{
    /**
     * Trouve un produit par son ID.
     * 
     * @return Product|null Produit trouvé ou null
     */
    public function findById(int $id): ?Product;

    /**
     * Trouve tous les produits disponibles.
     * Filtre uniquement les produits avec isAvailable = true.
     * 
     * @return Product[]
     */
    public function findAllAvailable(): array;

    /**
     * Trouve tous les produits avec leurs relations préchargées.
     * Évite le problème N+1 en chargeant plans et features en une requête.
     * 
     * @return Product[] Produits avec subscriptionPlans et productFeatures
     */
    public function findAllWithSubscriptionPlansAndFeatures(): array;

    /**
     * Trouve un produit par ID avec ses relations préchargées.
     * 
     * @param int $productId ID du produit
     * @return Product|null Produit avec relations ou null
     */
    public function findOneWithSubscriptionPlansAndFeatures(int $productId): ?Product;

    /**
     * Trouve un produit par son slug.
     * Utilisé pour les URLs SEO-friendly.
     * 
     * @param string $slug Slug du produit (ex: 'crypto-api-premium')
     * @return Product|null Produit trouvé ou null
     */
    public function findOneBySlug(string $slug): ?Product;

    /**
     * Trouve les produits par catégorie.
     * 
     * @param string $category Catégorie (ex: 'crypto', 'trading')
     * @return Product[]
     */
    public function findByCategory(string $category): array;

    /**
     * Trouve les produits par type.
     * 
     * @param string $productType Type (ex: 'api_access', 'data_package')
     * @return Product[]
     */
    public function findByType(string $productType): array;

    /**
     * Persiste un produit en base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function save(Product $product, bool $flush = false): void;

    /**
     * Supprime un produit de la base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function remove(Product $product, bool $flush = false): void;
}
