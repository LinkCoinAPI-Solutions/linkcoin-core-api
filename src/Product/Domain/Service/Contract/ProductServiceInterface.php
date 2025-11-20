<?php

namespace App\Product\Domain\Service\Contract;

use App\Product\Domain\Entity\Product;

/**
 * Interface du service de gestion des produits.
 * Point d'entrée pour toutes les opérations métier sur les produits.
 */
interface ProductServiceInterface
{
    /**
     * Récupère tous les produits avec leurs plans d'abonnement.
     * Utilise le cache si ProductCacheInterface est injecté.
     * 
     * @return Product[] Liste des produits avec relations
     * @throws \App\Product\Domain\Exception\ProductException Si erreur
     */
    public function getAllProducts(): array;

    /**
     * Récupère un produit par son ID avec ses plans.
     * Vérifie la disponibilité du produit.
     * 
     * @param int $productId ID du produit
     * @return Product Produit avec relations
     * @throws \App\Product\Domain\Exception\ProductException Si introuvable ou indisponible
     */
    public function getProductById(int $productId): Product;
}
