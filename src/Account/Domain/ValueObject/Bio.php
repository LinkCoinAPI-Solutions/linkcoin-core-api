<?php

namespace App\Account\Domain\ValueObject;

use App\Account\Domain\Exception\UserProfileValidationException;

/**
 * Value Object représentant une biographie utilisateur.
 * Encapsule la règle métier de longueur maximale (1000 caractères).
 * Immutable : ne peut pas être modifié après création.
 */
readonly class Bio
{
    private const MAX_LENGTH = 1000;

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée une bio depuis une chaîne.
     * Trim automatiquement les espaces.
     * 
     * @throws UserProfileValidationException Si trop longue
     */
    public static function fromString(string $bio): self
    {
        $cleaned = trim($bio);
        self::validate($cleaned);
        
        return new self($cleaned);
    }

    /**
     * Valide la longueur de la bio.
     * 
     * @throws UserProfileValidationException Si > 1000 caractères
     */
    private static function validate(string $bio): void
    {
        if (mb_strlen($bio) > self::MAX_LENGTH) {
            throw UserProfileValidationException::bioTooLong(
                mb_strlen($bio),
                self::MAX_LENGTH
            );
        }
    }

    /**
     * Vérifie si la bio est vide (utile pour null coalescing).
     */
    public function isEmpty(): bool
    {
        return empty($this->value);
    }
}
