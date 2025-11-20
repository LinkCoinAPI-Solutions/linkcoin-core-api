<?php

namespace App\Product\Infrastructure\Repository;

use App\Product\Domain\Entity\Product;
use App\Product\Domain\Entity\ProductFeature;
use App\Product\Domain\Repository\ProductFeatureRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Doctrine pour l'entité ProductFeature.
 * Implémente l'interface domain ProductFeatureRepositoryInterface.
 * Fournit les opérations CRUD et les recherches métier sur les features.
 */
class ProductFeatureRepository extends ServiceEntityRepository implements ProductFeatureRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductFeature::class);
    }

    /**
     * Trouve toutes les caractéristiques actives d'un produit.
     * Filtre uniquement isActive = true.
     * Triées par ordre d'affichage (sortOrder ASC).
     * 
     * Utilisé pour afficher les features sur la page produit.
     * 
     * @return ProductFeature[]
     */
    public function findActiveByProduct(Product $product): array
    {
        return $this->createQueryBuilder('pf')
            ->where('pf.product = :product')
            ->andWhere('pf.isActive = :active')
            ->setParameter('product', $product)
            ->setParameter('active', true)
            ->orderBy('pf.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une caractéristique par sa clé et son produit.
     * Recherche par featureKey (ex: 'real_time_data').
     * 
     * Utilisé pour vérifier l'existence d'une feature avant ajout
     * ou pour récupérer une feature spécifique programmatiquement.
     * 
     * @param string $featureKey Clé unique de la feature
     * @param Product $product Produit parent
     * @return ProductFeature|null Feature trouvée ou null
     */
    public function findOneByKeyAndProduct(string $featureKey, Product $product): ?ProductFeature
    {
        return $this->findOneBy([
            'featureKey' => $featureKey,
            'product' => $product
        ]);
    }

    /**
     * Trouve toutes les caractéristiques d'un type spécifique.
     * Filtre uniquement les features actives.
     * Triées par ordre d'affichage.
     * 
     * Note : Avec FeatureType enum, le champ en DB est 'featureType' (string).
     * Doctrine gère automatiquement la conversion enum ↔ string.
     * 
     * Utilisé pour afficher toutes les features premium/enterprise
     * dans un listing global ou pour des comparaisons de plans.
     * 
     * @param string $featureType Type (ex: 'standard', 'premium', 'enterprise')
     * @return ProductFeature[]
     */
    public function findByType(string $featureType): array
    {
        return $this->findBy(
            ['featureType' => $featureType, 'isActive' => true],
            ['sortOrder' => 'ASC']
        );
    }

    /**
     * Persiste une caractéristique en base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function save(ProductFeature $feature, bool $flush = false): void
    {
        $this->getEntityManager()->persist($feature);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une caractéristique de la base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function remove(ProductFeature $feature, bool $flush = false): void
    {
        $this->getEntityManager()->remove($feature);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
