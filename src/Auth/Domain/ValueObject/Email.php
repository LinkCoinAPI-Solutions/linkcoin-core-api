<?php

namespace App\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\AuthValidationException;

/**
 * Value Object représentant une adresse email.
 * Normalise en minuscules et valide le format RFC.
 * Immutable : une fois créé, ne peut pas être modifié.
 */
readonly class Email
{
    private function __construct(
        public string $value
    ) {}

    /**
     * Crée un email depuis une chaîne.
     * Normalise automatiquement en minuscules et trim.
     * 
     * @throws AuthValidationException Si le format est invalide
     */
    public static function fromString(string $email): self
    {
        $normalized = self::normalize($email);
        self::validate($normalized);
        
        return new self($normalized);
    }

    /**
     * Normalise l'email : trim + minuscules.
     */
    private static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Valide le format de l'email selon RFC 5322.
     * Vérifie également que le domaine est présent.
     * 
     * @throws AuthValidationException Si invalide
     */
    private static function validate(string $email): void
    {
        if (empty($email)) {
            throw AuthValidationException::singleError('Email cannot be empty');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw AuthValidationException::invalidEmailFormat($email);
        }

        // Vérifie que le domaine existe
        $domain = substr(strrchr($email, "@"), 1);
        if (empty($domain)) {
            throw AuthValidationException::invalidEmailFormat($email);
        }
    }

    /**
     * Extrait le domaine de l'email (ex: example.com).
     */
    public function getDomain(): string
    {
        return substr(strrchr($this->value, "@"), 1);
    }

    /**
     * Extrait la partie locale de l'email (avant le @).
     */
    public function getLocalPart(): string
    {
        return strstr($this->value, '@', true);
    }
}
