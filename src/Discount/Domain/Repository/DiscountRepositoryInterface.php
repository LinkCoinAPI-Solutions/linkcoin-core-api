<?php

namespace App\Discount\Domain\Repository;

use App\Discount\Domain\Entity\Discount;

/**
 * Interface de repository pour l'entité Discount.
 * Définit les opérations de persistence et de recherche des réductions.
 */
interface DiscountRepositoryInterface
{
    /**
     * Trouve une réduction par son code.
     * Le code est normalisé (majuscules) pour la recherche.
     * 
     * @return Discount|null Réduction trouvée ou null
     */
    public function findOneByCode(string $code): ?Discount;

    /**
     * Trouve toutes les réductions actives.
     * Actives = isActive = true (indépendamment des dates).
     * 
     * @return Discount[]
     */
    public function findAllActive(): array;

    /**
     * Trouve les réductions valides à une date donnée.
     * Valides = actives ET dans leur période de validité ET avec usages restants.
     * 
     * @return Discount[]
     */
    public function findValidAtDate(\DateTimeInterface $date): array;

    /**
     * Trouve les réductions par type (fixed, percentage).
     * 
     * @return Discount[]
     */
    public function findByType(string $type): array;

    /**
     * Trouve les réductions approchant de leur limite d'utilisation.
     * Utile pour les alertes d'administration.
     * 
     * @param int $threshold Nombre d'usages restants pour l'alerte
     * @return Discount[]
     */
    public function findNearingUsageLimit(int $threshold = 10): array;

    /**
     * Trouve les réductions expirées qui n'ont jamais été utilisées.
     * Utile pour le nettoyage ou l'analyse.
     * 
     * @return Discount[]
     */
    public function findExpiredUnused(): array;

    /**
     * Persiste une réduction en base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function save(Discount $discount, bool $flush = false): void;

    /**
     * Supprime une réduction de la base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function remove(Discount $discount, bool $flush = false): void;

    /**
     * Compte le nombre total d'utilisations d'une réduction.
     * Retourne le compteur d'usage actuel.
     * 
     * @return int Nombre d'utilisations
     */
    public function countUsages(Discount $discount): int;

    /**
     * Trouve les réductions les plus utilisées.
     * Triées par nombre d'usages décroissant.
     * Utile pour les statistiques et rapports.
     * 
     * @param int $limit Nombre de résultats à retourner
     * @return Discount[]
     */
    public function findMostUsed(int $limit = 10): array;
}
