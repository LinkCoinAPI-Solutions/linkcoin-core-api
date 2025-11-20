<?php

namespace App\Shared\Infrastructure\Validator\ConstraintValidator;

use App\Shared\Infrastructure\Validator\Constraints\ValidName;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidNameValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidName) {
            throw new UnexpectedTypeException($constraint, ValidName::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $trimmedValue = trim($value);

        if (!preg_match('/^[a-zA-ZÀ-ÿ\s\'-]+$/u', $trimmedValue)) {
            $this->context->buildViolation($constraint->invalidCharactersMessage)
                ->addViolation();
            return;
        }

        if (strlen($trimmedValue) < 2) {
            $this->context->buildViolation($constraint->tooShortMessage)
                ->setParameter('{{ min }}', 2)
                ->addViolation();
            return;
        }

        if (!$this->isValidFormat($trimmedValue)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->addViolation();
        }
    }

    private function isValidFormat(string $name): bool
    {
        if (empty(trim($name))) {
            return false;
        }

        if (preg_match('/\s{2,}/', $name)) {
            return false;
        }

        if (preg_match('/(.)\1{4,}/', $name)) {
            return false;
        }

        if (preg_match('/^[\s\'-]|[\s\'-]$/', $name)) {
            return false;
        }

        return true;
    }
}
