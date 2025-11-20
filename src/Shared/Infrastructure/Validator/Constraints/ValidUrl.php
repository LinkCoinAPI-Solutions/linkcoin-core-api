<?php

namespace App\Shared\Infrastructure\Validator\Constraints;

use App\Shared\Infrastructure\Validator\ConstraintValidator\ValidUrlValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidUrl extends Constraint
{
    public string $invalidFormatMessage = 'Please enter a valid URL (must start with http:// or https://).';
    public string $invalidDomainMessage = 'Invalid domain in URL.';

    public function validatedBy(): string
    {
        return ValidUrlValidator::class;
    }
}
