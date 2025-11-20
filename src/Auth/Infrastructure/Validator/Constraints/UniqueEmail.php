<?php

namespace App\Auth\Infrastructure\Validator\Constraints;

use App\Auth\Infrastructure\Validator\ConstraintValidator\UniqueEmailValidator;
use Symfony\Component\Validator\Constraint;

/**
 * Contrainte de validation pour l'unicité de l'email.
 * Vérifie qu'un email n'est pas déjà enregistré lors de l'inscription.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
class UniqueEmail extends Constraint
{
    public string $message = 'This email address is already registered.';

    /**
     * Retourne le validateur associé à cette contrainte.
     */
    public function validatedBy(): string
    {
        return UniqueEmailValidator::class;
    }
}
