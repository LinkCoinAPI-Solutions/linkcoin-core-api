<?php

namespace App\Discount\Infrastructure\Validator\Constraints;

use App\Discount\Infrastructure\Validator\ConstraintValidator\ValidDiscountCodeValidator;
use Attribute;
use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour le format d'un code de réduction.
 * 
 * Règles :
 * - Uniquement majuscules (A-Z)
 * - Chiffres (0-9)
 * - Tirets (-) et underscores (_)
 * - Pas d'espaces, caractères spéciaux ou minuscules
 * 
 * Utilisée sur la propriété `code` de l'entité Discount.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidDiscountCode extends Constraint
{
    public string $message = 'Le code promo "{{ code }}" n\'est pas valide. Il doit contenir uniquement des majuscules, chiffres, "_" ou "-".';

    /**
     * Retourne le validateur associé à cette contrainte.
     */
    public function validatedBy(): string
    {
        return ValidDiscountCodeValidator::class;
    }
}
