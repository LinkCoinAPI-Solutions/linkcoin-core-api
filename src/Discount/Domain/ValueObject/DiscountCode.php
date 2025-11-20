<?php

namespace App\Discount\Domain\ValueObject;

use App\Discount\Domain\Exception\DiscountValidationException;

/**
 * Value Object représentant un code de réduction.
 * Encapsule la normalisation (majuscules) et la validation (format).
 * Immutable : ne peut pas être modifié après création.
 */
readonly class DiscountCode
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 50;
    private const VALID_PATTERN = '/^[A-Z0-9\-_]+$/';

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée un code de réduction depuis une chaîne.
     * Normalise automatiquement en majuscules et trim.
     * 
     * @throws DiscountValidationException Si format invalide
     */
    public static function fromString(string $code): self
    {
        $normalized = self::normalize($code);
        self::validate($normalized);
        
        return new self($normalized);
    }

    /**
     * Normalise le code : trim + majuscules.
     */
    private static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    /**
     * Valide le format du code.
     * Règles :
     * - Longueur entre 3 et 50 caractères
     * - Uniquement lettres majuscules, chiffres, tirets et underscores
     * 
     * @throws DiscountValidationException Si invalide
     */
    private static function validate(string $code): void
    {
        if (empty($code)) {
            throw DiscountValidationException::singleError('Discount code cannot be empty');
        }

        $length = strlen($code);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw DiscountValidationException::singleError(
                sprintf(
                    'Discount code must be between %d and %d characters (got %d)',
                    self::MIN_LENGTH,
                    self::MAX_LENGTH,
                    $length
                )
            );
        }

        if (!preg_match(self::VALID_PATTERN, $code)) {
            throw DiscountValidationException::singleError(
                'Discount code can only contain uppercase letters, numbers, hyphens and underscores'
            );
        }
    }

    /**
     * Retourne la valeur du code pour comparaison.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Conversion en string pour affichage.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
