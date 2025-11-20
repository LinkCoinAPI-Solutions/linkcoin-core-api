<?php

namespace App\Product\Infrastructure\Repository;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Repository\ProductRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Doctrine pour l'entité Product.
 * Implémente l'interface domain ProductRepositoryInterface.
 * Fournit les opérations CRUD et les recherches métier avec préchargement des relations.
 */
class ProductRepository extends ServiceEntityRepository implements ProductRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Trouve un produit par son ID.
     * Utilise find() de Doctrine (cache de niveau 1 automatique).
     * 
     * @return Product|null Produit trouvé ou null
     */
    public function findById(int $id): ?Product
    {
        return $this->find($id);
    }

    /**
     * Trouve tous les produits disponibles.
     * Filtre uniquement isAvailable = true.
     * Triés du plus récent au plus ancien.
     * 
     * Note : N'inclut PAS les relations (subscriptionPlans, features).
     * Pour inclure les relations, utiliser findAllWithSubscriptionPlansAndFeatures().
     * 
     * @return Product[]
     */
    public function findAllAvailable(): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les produits avec leurs relations préchargées.
     * 
     * Utilise LEFT JOIN avec addSelect pour éviter le problème N+1 :
     * - 1 requête au lieu de 1 + N + M requêtes
     * - Charge produits, plans et features en un seul hit SQL
     * 
     * Filtre uniquement les produits disponibles.
     * 
     * @return Product[] Produits avec subscriptionPlans et productFeatures hydratés
     */
    public function findAllWithSubscriptionPlansAndFeatures(): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subscriptionPlans', 'sp')
            ->leftJoin('p.productFeatures', 'f')
            ->addSelect('sp', 'f') // Précharge les relations
            ->where('p.isAvailable = :available')
            ->setParameter('available', true)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un produit par ID avec ses relations préchargées.
     * 
     * Version "single item" de findAllWithSubscriptionPlansAndFeatures().
     * Utilise la même stratégie de préchargement pour éviter N+1.
     * 
     * Filtre uniquement les produits disponibles.
     * 
     * @param int $productId ID du produit
     * @return Product|null Produit avec relations ou null
     */
    public function findOneWithSubscriptionPlansAndFeatures(int $productId): ?Product
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.subscriptionPlans', 'sp')
            ->leftJoin('p.productFeatures', 'f')
            ->addSelect('sp', 'f') // Précharge les relations
            ->where('p.id = :id')
            ->andWhere('p.isAvailable = :available')
            ->setParameter('id', $productId)
            ->setParameter('available', true)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un produit par son slug.
     * Utilisé pour les URLs SEO-friendly.
     * Filtre uniquement les produits disponibles.
     * 
     * Note : Le slug est stocké dans slugValue (pas slug) depuis la refacto VO.
     * Adapter si nécessaire : ['slugValue' => $slug]
     * 
     * @param string $slug Slug du produit (ex: 'crypto-api-premium')
     * @return Product|null Produit trouvé ou null
     */
    public function findOneBySlug(string $slug): ?Product
    {
        return $this->findOneBy(['slugValue' => $slug, 'isAvailable' => true]);
    }

    /**
     * Trouve les produits par catégorie.
     * Filtre uniquement les produits disponibles.
     * Triés du plus récent au plus ancien.
     * 
     * @param string $category Catégorie (ex: 'crypto', 'trading')
     * @return Product[]
     */
    public function findByCategory(string $category): array
    {
        return $this->findBy(
            ['category' => $category, 'isAvailable' => true],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Trouve les produits par type.
     * 
     * Note : Avec ProductType enum, le champ en DB est 'productType' (string).
     * Doctrine gère automatiquement la conversion enum ↔ string.
     * 
     * @param string $productType Type (ex: 'api_access', 'data_package')
     * @return Product[]
     */
    public function findByType(string $productType): array
    {
        return $this->findBy(
            ['productType' => $productType, 'isAvailable' => true],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Persiste un produit en base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function save(Product $product, bool $flush = false): void
    {
        $this->getEntityManager()->persist($product);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un produit de la base.
     * Les relations cascade (subscriptionPlans, productFeatures) seront supprimées automatiquement.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function remove(Product $product, bool $flush = false): void
    {
        $this->getEntityManager()->remove($product);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
