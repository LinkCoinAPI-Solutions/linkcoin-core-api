<?php

namespace App\Shared\Infrastructure\Validator\Constraints;

use App\Shared\Infrastructure\Validator\ConstraintValidator\ValidNameValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidName extends Constraint
{
    public string $invalidCharactersMessage = 'Name can only contain letters, spaces, hyphens and apostrophes.';
    public string $tooShortMessage = 'Name must be at least {{ min }} characters long.';
    public string $invalidFormatMessage = 'Name format is invalid.';

    public function validatedBy(): string
    {
        return ValidNameValidator::class;
    }
}
