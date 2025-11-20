<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\SharedKernelException;

/**
 * Value Object représentant un numéro de téléphone.
 * SHARED KERNEL : Utilisé par Account (UserProfile).
 * 
 * Encapsule la validation et la normalisation.
 * Immutable : ne peut pas être modifié après création.
 */
readonly class PhoneNumber
{
    private const MAX_LENGTH = 20;
    private const VALID_PATTERN = '/^[\d\s\+\(\)\-\.]+$/';

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée un numéro depuis une chaîne.
     * Trim automatique.
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function fromString(string $phone): self
    {
        $trimmed = trim($phone);
        self::validate($trimmed);
        
        return new self($trimmed);
    }

    /**
     * Valide le numéro.
     * 
     * @throws SharedKernelException Si invalide
     */
    private static function validate(string $phone): void
    {
        if (empty($phone)) {
            throw SharedKernelException::emptyPhoneNumber();
        }

        if (strlen($phone) > self::MAX_LENGTH) {
            throw SharedKernelException::phoneNumberTooLong(self::MAX_LENGTH, strlen($phone));
        }

        if (!preg_match(self::VALID_PATTERN, $phone)) {
            throw SharedKernelException::invalidPhoneNumberFormat();
        }
    }

    /**
     * Retourne le numéro sans formatage (digits only).
     */
    public function getDigitsOnly(): string
    {
        return preg_replace('/[^\d]/', '', $this->value);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
