<?php

namespace App\Discount\Infrastructure\Validator\ConstraintValidator;

use App\Discount\Infrastructure\Validator\Constraints\ValidDiscountCode;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Validateur pour la contrainte ValidDiscountCode.
 * Vérifie que le code respecte le format requis : [A-Z0-9_-]+
 */
final class ValidDiscountCodeValidator extends ConstraintValidator
{
    /**
     * Valide le format du code de réduction.
     * 
     * Règles :
     * - Si null ou vide : pas de validation (géré par NotBlank)
     * - Sinon : vérifie le pattern regex
     * 
     * Pattern : ^[A-Z0-9_-]+$
     * - ^ : début de chaîne
     * - [A-Z0-9_-]+ : au moins 1 caractère parmi A-Z, 0-9, _, -
     * - $ : fin de chaîne
     * 
     * @throws UnexpectedTypeException Si la contrainte n'est pas ValidDiscountCode
     */
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidDiscountCode) {
            throw new UnexpectedTypeException($constraint, ValidDiscountCode::class);
        }

        // NotBlank s'occupe de la validation si vide
        if ($value === null || $value === '') {
            return;
        }

        // Vérifie le format avec regex
        if (!preg_match('/^[A-Z0-9_-]+$/', $value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ code }}', (string) $value)
                ->addViolation();
        }
    }
}
