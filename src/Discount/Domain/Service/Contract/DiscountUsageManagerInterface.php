<?php

namespace App\Discount\Domain\Service\Contract;

use App\Discount\Domain\Entity\Discount;

/**
 * Interface pour la gestion du compteur d'usage des réductions.
 * Gère l'incrémentation et la décrémentation dans des transactions.
 */
interface DiscountUsageManagerInterface
{
    /**
     * Incrémente le compteur d'usage d'une réduction.
     * Appelé lorsqu'une réduction est appliquée.
     * 
     * @throws \App\Discount\Domain\Exception\DiscountException Si mise à jour échoue
     */
    public function incrementDiscountUsage(Discount $discount): void;

    /**
     * Décrémente le compteur d'usage d'une réduction.
     * Appelé lorsqu'une réduction est retirée.
     * 
     * @throws \App\Discount\Domain\Exception\DiscountException Si mise à jour échoue
     */
    public function decrementDiscountUsage(Discount $discount): void;
}
