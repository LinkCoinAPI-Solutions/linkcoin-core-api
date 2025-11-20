<?php

namespace App\Discount\Domain\Service\Contract;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Domain\Entity\Discount;

/**
 * Interface pour la validation des réductions.
 * Applique toutes les règles métier avant application.
 */
interface DiscountValidatorInterface
{
    /**
     * Valide qu'une réduction peut être appliquée à un panier.
     * Agrège toutes les erreurs de validation et les lance en bloc.
     * 
     * Règles validées :
     * - Réduction active et dans sa période de validité
     * - Usages restants disponibles
     * - Montant minimum du panier atteint
     * - Pas de conflit avec une réduction existante
     * 
     * @param Cart $cart Panier cible
     * @param Discount $discount Réduction à valider
     * @param bool $ignoreAlreadyApplied Ignore le conflit si true (pour recalcul)
     * @throws \App\Discount\Domain\Exception\DiscountValidationException Si validation échoue
     */
    public function validate(Cart $cart, Discount $discount, bool $ignoreAlreadyApplied = false): void;
}
