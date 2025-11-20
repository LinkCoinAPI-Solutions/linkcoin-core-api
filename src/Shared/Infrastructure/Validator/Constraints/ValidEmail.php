<?php

namespace App\Shared\Infrastructure\Validator\Constraints;

use App\Shared\Infrastructure\Validator\ConstraintValidator\ValidEmailValidator;
use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ValidEmail extends Constraint
{
    public string $invalidFormatMessage = 'L\'adresse email "{{ email }}" n\'est pas valide.';
    public string $invalidDomainMessage = 'Le domaine "{{ domain }}" n\'est pas valide.';
    public string $disposableEmailMessage = 'Les adresses email jetables ne sont pas autorisées.';
    public string $tooLongMessage = 'L\'adresse email ne peut pas dépasser {{ limit }} caractères.';
    
    public int $maxLength = 254;
    public bool $checkDnsRecord = false;
    public bool $blockDisposableEmails = false;
    public array $allowedDomains = [];
    public array $blockedDomains = [];

    public function validatedBy(): string
    {
        return ValidEmailValidator::class;
    }
}
