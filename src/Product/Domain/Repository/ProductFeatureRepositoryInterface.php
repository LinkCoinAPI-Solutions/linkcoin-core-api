<?php

namespace App\Product\Domain\Repository;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Entity\ProductFeature;

/**
 * Interface de repository pour l'entité ProductFeature.
 * Définit les opérations de recherche des caractéristiques produit.
 */
interface ProductFeatureRepositoryInterface
{
    /**
     * Trouve toutes les caractéristiques actives d'un produit.
     * Filtre uniquement isActive = true.
     * 
     * @return ProductFeature[]
     */
    public function findActiveByProduct(Product $product): array;

    /**
     * Trouve une caractéristique par sa clé et son produit.
     * Recherche par featureKey (ex: 'real_time_data').
     * 
     * @param string $featureKey Clé unique de la feature
     * @param Product $product Produit parent
     * @return ProductFeature|null Feature trouvée ou null
     */
    public function findOneByKeyAndProduct(string $featureKey, Product $product): ?ProductFeature;

    /**
     * Trouve toutes les caractéristiques d'un type spécifique.
     * 
     * @param string $featureType Type (ex: 'standard', 'premium', 'enterprise')
     * @return ProductFeature[]
     */
    public function findByType(string $featureType): array;

    /**
     * Persiste une caractéristique en base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function save(ProductFeature $feature, bool $flush = false): void;

    /**
     * Supprime une caractéristique de la base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function remove(ProductFeature $feature, bool $flush = false): void;
}
