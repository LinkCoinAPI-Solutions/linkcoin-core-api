<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\SharedKernelException;

/**
 * Value Object représentant une adresse email.
 * SHARED KERNEL : Utilisé par Account, Auth, Notification.
 * 
 * Encapsule la validation et la normalisation (lowercase, trim).
 * Immutable : ne peut pas être modifié après création.
 */
readonly class Email
{
    private const MAX_LENGTH = 180;
    private const VALID_PATTERN = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée un email depuis une chaîne.
     * Normalise automatiquement (lowercase + trim).
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function fromString(string $email): self
    {
        $normalized = self::normalize($email);
        self::validate($normalized);
        
        return new self($normalized);
    }

    /**
     * Normalise l'email : lowercase + trim.
     */
    private static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Valide le format de l'email.
     * 
     * @throws SharedKernelException Si invalide
     */
    private static function validate(string $email): void
    {
        if (empty($email)) {
            throw SharedKernelException::emptyEmail();
        }

        if (strlen($email) > self::MAX_LENGTH) {
            throw SharedKernelException::emailTooLong(self::MAX_LENGTH, strlen($email));
        }

        if (!preg_match(self::VALID_PATTERN, $email)) {
            throw SharedKernelException::invalidEmailFormat($email);
        }
    }

    /**
     * Retourne le domaine de l'email.
     */
    public function getDomain(): string
    {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    /**
     * Retourne la partie locale (avant @).
     */
    public function getLocalPart(): string
    {
        return substr($this->value, 0, strpos($this->value, '@'));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
