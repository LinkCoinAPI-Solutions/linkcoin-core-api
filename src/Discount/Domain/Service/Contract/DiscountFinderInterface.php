<?php

namespace App\Discount\Domain\Service\Contract;

use App\Discount\Domain\Entity\Discount;

/**
 * Interface pour la recherche des réductions.
 * Centralise la logique de normalisation et de recherche.
 */
interface DiscountFinderInterface
{
    /**
     * Trouve une réduction valide par son code.
     * Normalise le code avant la recherche (trim + majuscules).
     * Retourne null si le code est vide ou introuvable.
     * 
     * @return Discount|null Réduction trouvée ou null
     */
    public function findValidDiscountByCode(string $discountCode): ?Discount;

    /**
     * Trouve une réduction valide par son code ou lance une exception.
     * Version stricte de findValidDiscountByCode.
     * 
     * @return Discount Réduction trouvée
     * @throws \App\Discount\Domain\Exception\DiscountException Si code introuvable
     */
    public function findValidDiscountByCodeOrFail(string $discountCode): Discount;
}
