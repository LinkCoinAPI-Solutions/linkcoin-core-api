<?php

namespace App\Shared\Infrastructure\Validator\ConstraintValidator;

use App\Shared\Infrastructure\Validator\Constraints\ValidEmail;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ValidEmailValidator extends ConstraintValidator
{
    private const DISPOSABLE_DOMAINS = [
        '10minutemail.com', 'guerrillamail.com', 'mailinator.com', 
        'temp-mail.org', 'yopmail.com', 'throwaway.email'
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidEmail) {
            throw new UnexpectedTypeException($constraint, ValidEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $email = strtolower(trim($value));

        // 1. Validation de la longueur
        if (strlen($email) > $constraint->maxLength) {
            $this->context->buildViolation($constraint->tooLongMessage)
                ->setParameter('{{ limit }}', (string) $constraint->maxLength)
                ->addViolation();
            return;
        }

        // 2. Validation du format basique
        if (!$this->isValidFormat($email)) {
            $this->context->buildViolation($constraint->invalidFormatMessage)
                ->setParameter('{{ email }}', $value)
                ->addViolation();
            return;
        }

        $domain = explode('@', $email)[1];

        // 3. Validation des domaines autorisés/bloqués
        if (!$this->isDomainAllowed($domain, $constraint)) {
            $this->context->buildViolation($constraint->invalidDomainMessage)
                ->setParameter('{{ domain }}', $domain)
                ->addViolation();
            return;
        }

        // 4. Détection des emails jetables (optionnel)
        if ($constraint->blockDisposableEmails && $this->isDisposableEmail($domain)) {
            $this->context->buildViolation($constraint->disposableEmailMessage)
                ->addViolation();
            return;
        }

        // 5. Vérification DNS (optionnel)
        if ($constraint->checkDnsRecord && !$this->hasDnsRecord($domain)) {
            $this->context->buildViolation($constraint->invalidDomainMessage)
                ->setParameter('{{ domain }}', $domain)
                ->addViolation();
        }
    }

    private function isValidFormat(string $email): bool
    {
        // Validation de base avec filter_var
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        // Vérifications simples supplémentaires
        if (str_contains($email, '..') || str_contains($email, '  ')) {
            return false;
        }

        // Vérifier la structure de base
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return false;
        }

        [$localPart, $domain] = $parts;

        // Partie locale : 1-64 caractères
        if (strlen($localPart) < 1 || strlen($localPart) > 64) {
            return false;
        }

        // Domaine : doit contenir un point et un TLD valide
        if (!str_contains($domain, '.')) {
            return false;
        }

        $tld = substr($domain, strrpos($domain, '.') + 1);
        return strlen($tld) >= 2 && ctype_alpha($tld);
    }

    private function isDomainAllowed(string $domain, ValidEmail $constraint): bool
    {
        // Liste blanche (si définie)
        if (!empty($constraint->allowedDomains)) {
            return in_array($domain, $constraint->allowedDomains, true);
        }

        // Liste noire (si définie)
        if (!empty($constraint->blockedDomains)) {
            return !in_array($domain, $constraint->blockedDomains, true);
        }

        return true;
    }

    private function isDisposableEmail(string $domain): bool
    {
        return in_array($domain, self::DISPOSABLE_DOMAINS, true);
    }

    private function hasDnsRecord(string $domain): bool
    {
        try {
            return checkdnsrr($domain, 'MX') || checkdnsrr($domain, 'A');
        } catch (\Throwable) {
            return true; // En cas d'erreur, on considère comme valide
        }
    }
}
