<?php

namespace App\Shared\Infrastructure\Validator\ConstraintValidator;

use App\Shared\Infrastructure\Validator\Constraints\ValidPhone;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidPhoneValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPhone) {
            throw new UnexpectedTypeException($constraint, ValidPhone::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $cleanPhone = preg_replace('/[\s\-\(\)\.]/u', '', $value);

        if (!preg_match('/^[\+]?[0-9]{8,15}$/', $cleanPhone)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->addViolation();
            return;
        }

        if (strlen($cleanPhone) < 8) {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->addViolation();
        }
    }
}
