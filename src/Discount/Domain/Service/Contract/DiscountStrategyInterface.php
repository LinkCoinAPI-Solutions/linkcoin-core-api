<?php 

namespace App\Discount\Domain\Service\Contract;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * Interface pour les stratégies de calcul des réductions (Strategy Pattern).
 * Chaque type de réduction (fixed, percentage) implémente cette interface.
 * Les implémentations sont auto-tagguées par Symfony pour injection.
 */
#[Autoconfigure(tags: ['app.discount_strategy'])]
interface DiscountStrategyInterface
{
    /**
     * Calcule le montant de réduction selon la stratégie.
     * 
     * @param float $totalAmount Montant total du panier
     * @param float $discountValue Valeur de la réduction (montant fixe ou %)
     * @return float Montant de réduction calculé
     * @throws \App\Discount\Domain\Exception\DiscountException Si calcul invalide
     */
    public function calculateDiscountAmount(float $totalAmount, float $discountValue): float;

    /**
     * Retourne le type de stratégie pour l'identification.
     * Utilisé pour mapper les stratégies par type.
     * 
     * @return string Type de stratégie (ex: 'fixed', 'percentage')
     */
    public function getStrategyType(): string;
}
