<?php

namespace App\Shared\Infrastructure\Validator\ConstraintValidator;

use App\Shared\Infrastructure\Validator\Constraints\ValidUrl;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidUrlValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidUrl) {
            throw new UnexpectedTypeException($constraint, ValidUrl::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $trimmedValue = trim($value);

        if (!filter_var($trimmedValue, FILTER_VALIDATE_URL)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->addViolation();
            return;
        }

        if (!preg_match('/^https?:\/\//', $trimmedValue)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->addViolation();
            return;
        }

        $parsedUrl = parse_url($trimmedValue);
        if (!isset($parsedUrl['host']) || !preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $parsedUrl['host'])) {
            $this->context->buildViolation($constraint->invalidDomainMessage)
                ->addViolation();
        }
    }
}
