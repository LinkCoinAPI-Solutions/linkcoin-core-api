<?php

namespace App\Discount\Infrastructure\Repository;

use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Repository\DiscountRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Doctrine pour l'entité Discount.
 * Implémente l'interface domain DiscountRepositoryInterface.
 * Fournit les opérations CRUD et les recherches métier spécifiques.
 */
class DiscountRepository extends ServiceEntityRepository implements DiscountRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Discount::class);
    }

    /**
     * Trouve une réduction par son code.
     * Normalise le code (trim + majuscules) pour recherche insensible à la casse.
     * 
     * @return Discount|null Réduction trouvée ou null
     */
    public function findOneByCode(string $code): ?Discount
    {
        return $this->findOneBy(['code' => strtoupper(trim($code))]);
    }

    /**
     * Trouve toutes les réductions actives.
     * Actives = isActive = true (indépendamment des dates).
     * Triées de la plus récente à la plus ancienne.
     * 
     * @return Discount[]
     */
    public function findAllActive(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réductions valides à une date donnée.
     * 
     * Critères :
     * - Active (isActive = true)
     * - Dans la période de validité (validFrom ≤ date ≤ validUntil)
     * - Avec usages restants (usageCount < usageLimit)
     * 
     * Utile pour afficher les réductions disponibles à une date précise.
     * 
     * @return Discount[]
     */
    public function findValidAtDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isActive = :active')
            ->andWhere('d.validFrom <= :date OR d.validFrom IS NULL')
            ->andWhere('d.validUntil >= :date OR d.validUntil IS NULL')
            ->andWhere('d.usageLimit > d.usageCount OR d.usageLimit IS NULL')
            ->setParameter('active', true)
            ->setParameter('date', $date)
            ->orderBy('d.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réductions par type (fixed ou percentage).
     * Filtre uniquement les réductions actives.
     * Triées de la plus récente à la plus ancienne.
     * 
     * @return Discount[]
     */
    public function findByType(string $type): array
    {
        return $this->findBy(
            ['type' => $type, 'isActive' => true],
            ['createdAt' => 'DESC']
        );
    }

    /**
     * Trouve les réductions approchant de leur limite d'utilisation.
     * 
     * Critères :
     * - Active
     * - Avec limite d'utilisation définie
     * - Avec usages restants ≤ seuil
     * - Pas encore épuisée
     * 
     * Utile pour les alertes d'administration (réapprovisionner les codes).
     * 
     * @param int $threshold Seuil d'usages restants pour l'alerte
     * @return Discount[]
     */
    public function findNearingUsageLimit(int $threshold = 10): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isActive = :active')
            ->andWhere('d.usageLimit IS NOT NULL')
            ->andWhere('(d.usageLimit - d.usageCount) <= :threshold')
            ->andWhere('d.usageCount < d.usageLimit')
            ->setParameter('active', true)
            ->setParameter('threshold', $threshold)
            ->orderBy('d.usageCount', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réductions expirées qui n'ont jamais été utilisées.
     * 
     * Critères :
     * - Date de fin dépassée
     * - Compteur d'usage = 0
     * 
     * Utile pour :
     * - Nettoyage de la base (supprimer les codes inutilisés)
     * - Analyse marketing (pourquoi pas utilisés ?)
     * 
     * @return Discount[]
     */
    public function findExpiredUnused(): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.validUntil < :now')
            ->andWhere('d.usageCount = :zero')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('zero', 0)
            ->orderBy('d.validUntil', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Persiste une réduction en base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function save(Discount $discount, bool $flush = false): void
    {
        $this->getEntityManager()->persist($discount);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime une réduction de la base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function remove(Discount $discount, bool $flush = false): void
    {
        $this->getEntityManager()->remove($discount);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Compte le nombre total d'utilisations d'une réduction.
     * Retourne simplement le compteur d'usage de l'entité.
     * 
     * @return int Nombre d'utilisations
     */
    public function countUsages(Discount $discount): int
    {
        return $discount->getUsageCount();
    }

    /**
     * Trouve les réductions les plus utilisées.
     * 
     * Critères :
     * - Au moins 1 utilisation
     * - Triées par nombre d'usages décroissant
     * - Limitées aux N premiers résultats
     * 
     * Utile pour :
     * - Statistiques et rapports
     * - Identifier les codes populaires
     * - Dashboard d'administration
     * 
     * @param int $limit Nombre de résultats à retourner
     * @return Discount[]
     */
    public function findMostUsed(int $limit = 10): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.usageCount > :zero')
            ->setParameter('zero', 0)
            ->orderBy('d.usageCount', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réductions applicables à un montant de panier donné.
     * 
     * Critères :
     * - Active et valide (dates, usages)
     * - Montant minimum requis atteint ou pas de minimum
     * - Triées par valeur décroissante (meilleures offres en premier)
     * 
     * Utile pour suggérer des codes promo à l'utilisateur.
     * 
     * @param float $amount Montant du panier
     * @return Discount[]
     */
    public function findApplicableToAmount(float $amount): array
    {
        return $this->createQueryBuilder('d')
            ->where('d.isActive = :active')
            ->andWhere('d.validFrom <= :now OR d.validFrom IS NULL')
            ->andWhere('d.validUntil >= :now OR d.validUntil IS NULL')
            ->andWhere('d.usageLimit > d.usageCount OR d.usageLimit IS NULL')
            ->andWhere('d.minimumAmount <= :amount OR d.minimumAmount IS NULL')
            ->setParameter('active', true)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('amount', $amount)
            ->orderBy('d.value', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Supprime en masse les réductions expirées depuis une date donnée.
     * 
     * Opération de nettoyage (maintenance).
     * Utilisée par une commande Symfony pour purger les anciennes réductions.
     * 
     * Exemple : supprimer toutes les réductions expirées depuis plus de 6 mois.
     * 
     * @param \DateTimeInterface $date Date limite (supprimer si expiré avant)
     * @return int Nombre de réductions supprimées
     */
    public function removeExpiredSince(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('d')
            ->delete()
            ->where('d.validUntil < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }
}
