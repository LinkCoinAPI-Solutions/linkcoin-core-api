<?php

namespace App\Discount\Application\Service;

use App\Cart\Domain\Entity\Cart;
use App\Discount\Domain\Entity\Discount;
use App\Discount\Domain\Exception\DiscountValidationException;
use App\Discount\Domain\Service\Contract\DiscountValidatorInterface;

/**
 * Service responsable de la validation des réductions.
 * Applique toutes les règles métier avant l'application d'une réduction.
 * Agrège les erreurs pour un retour complet à l'utilisateur.
 */
final readonly class DiscountValidator implements DiscountValidatorInterface
{
    // Messages d'erreur standardisés
    private const ERRORS = [
        'inactive' => 'Ce code de réduction n\'est plus actif',
        'expired'  => 'Ce code de réduction a expiré',
        'limit'    => 'Ce code de réduction a atteint sa limite d\'utilisation',
        'invalid'  => 'Ce code de réduction n\'est plus valide',
        'already'  => 'Ce code de réduction est déjà appliqué au panier',
        'conflict' => 'Un autre code de réduction est déjà appliqué. Supprimez-le d\'abord.',
    ];

    /**
     * Valide qu'une réduction peut être appliquée à un panier.
     * Agrège toutes les erreurs de validation et les lance en bloc.
     * 
     * Règles validées :
     * 1. Réduction active et dans sa période de validité
     * 2. Usages restants disponibles
     * 3. Montant minimum du panier atteint
     * 4. Pas de conflit avec une réduction existante
     * 
     * @param bool $ignoreAlreadyApplied Ignore le conflit si true (pour recalcul)
     * @throws DiscountValidationException Si au moins une règle est violée
     */
    public function validate(Cart $cart, Discount $discount, bool $ignoreAlreadyApplied = false): void
    {
        $errors = [];

        // Validation 1 : État général de la réduction
        if (!$discount->isCurrentlyValid()) {
            $errors[] = match (true) {
                !$discount->isActive()       => self::ERRORS['inactive'],
                $discount->isExpired()       => self::ERRORS['expired'],
                !$discount->hasUsagesLeft()  => self::ERRORS['limit'],
                default                      => self::ERRORS['invalid'],
            };
        }

        // Validation 2 : Montant minimum requis
        if (!$discount->canApplyToAmount((float) $cart->getTotalAmount())) {
            $errors[] = sprintf(
                'Montant minimum requis: %.2f€ (montant actuel: %.2f€)',
                $discount->getMinimumAmount(),
                $cart->getTotalAmount()
            );
        }

        // Validation 3 : Conflit avec réduction existante (sauf si recalcul)
        if (!$ignoreAlreadyApplied) {
            if ($conflict = $this->getDiscountConflict($cart, $discount)) {
                $errors[] = $conflict;
            }
        }

        // Lance l'exception avec toutes les erreurs agrégées
        if (!empty($errors)) {
            throw DiscountValidationException::fromErrors($errors);
        }
    }

    /**
     * Détecte un conflit entre réductions.
     * 
     * Cas possibles :
     * - Même code déjà appliqué → erreur "already"
     * - Code différent appliqué → erreur "conflict"
     * - Aucune réduction → null
     * 
     * @return string|null Message d'erreur ou null
     */
    private function getDiscountConflict(Cart $cart, Discount $discount): ?string
    {
        $currentDiscount = $cart->getDiscount()?->getCode();

        if (!$currentDiscount) {
            return null;
        }

        return $currentDiscount === $discount->getCode()
            ? self::ERRORS['already']
            : self::ERRORS['conflict'];
    }
}
