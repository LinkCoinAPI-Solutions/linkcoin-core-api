<?php

namespace App\Shared\Infrastructure\Validator\Constraints;

use App\Shared\Infrastructure\Validator\ConstraintValidator\ValidPhoneValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidPhone extends Constraint
{
    public string $invalidFormatMessage = 'Please enter a valid phone number.';
    public string $tooShortMessage = 'Phone number is too short.';
    public string $invalidCharactersMessage = 'Phone number contains invalid characters.';

    public function validatedBy(): string
    {
        return ValidPhoneValidator::class;
    }
}
