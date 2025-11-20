<?php

namespace App\Subscription\Domain\Repository;

use App\Product\Domain\Entity\Product;
use App\Subscription\Domain\Entity\SubscriptionPlan;

interface SubscriptionPlanRepositoryInterface
{

    /**
     * Trouve tous les plans actifs d'un produit
     */
    public function findActiveByProduct(Product $product): array;

    /**
     * Trouve les plans par niveau de tier
     */
    public function findByTierLevel(int $tierLevel): array;

    /**
     * Trouve les plans par cycle de facturation
     */
    public function findByBillingCycle(string $billingCycle): array;

    /**
     * Trouve le plan le plus populaire d'un produit
     */
    public function findMostPopularByProduct(Product $product): ?SubscriptionPlan;

    /**
     * Sauvegarde un plan d'abonnement
     */
    public function save(SubscriptionPlan $plan, bool $flush = false): void;

    /**
     * Supprime un plan d'abonnement
     */
    public function remove(SubscriptionPlan $plan, bool $flush = false): void;

    /**
     * Compte le nombre d'abonnements actifs pour un plan
     */
    public function countActiveSubscriptions(SubscriptionPlan $plan): int;
}
