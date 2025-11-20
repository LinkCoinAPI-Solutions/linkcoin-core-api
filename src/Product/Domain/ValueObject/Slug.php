<?php

namespace App\Product\Domain\ValueObject;

/**
 * Value Object représentant un slug de produit.
 * Encapsule la validation et la normalisation des slugs.
 * Immutable : ne peut pas être modifié après création.
 */
readonly class Slug
{
    private const MIN_LENGTH = 3;
    private const MAX_LENGTH = 255;
    private const VALID_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée un slug depuis une chaîne.
     * Normalise automatiquement (minuscules, trim, tirets).
     * 
     * @throws \InvalidArgumentException Si format invalide
     */
    public static function fromString(string $slug): self
    {
        $normalized = self::normalize($slug);
        self::validate($normalized);
        
        return new self($normalized);
    }

    /**
     * Génère un slug depuis un nom de produit.
     * Transforme "Crypto API Premium" en "crypto-api-premium".
     */
    public static function fromProductName(string $name): self
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return self::fromString($slug);
    }

    /**
     * Normalise le slug : minuscules + trim.
     */
    private static function normalize(string $slug): string
    {
        return strtolower(trim($slug));
    }

    /**
     * Valide le format du slug.
     * Règles :
     * - Longueur entre 3 et 255 caractères
     * - Uniquement minuscules, chiffres et tirets
     * - Pas de tirets consécutifs ou aux extrémités
     * 
     * @throws \InvalidArgumentException Si invalide
     */
    private static function validate(string $slug): void
    {
        if (empty($slug)) {
            throw new \InvalidArgumentException('Slug cannot be empty');
        }

        $length = strlen($slug);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Slug must be between %d and %d characters', self::MIN_LENGTH, self::MAX_LENGTH)
            );
        }

        if (!preg_match(self::VALID_PATTERN, $slug)) {
            throw new \InvalidArgumentException(
                'Slug can only contain lowercase letters, numbers and hyphens (no consecutive hyphens)'
            );
        }
    }

    /**
     * Conversion en string pour stockage/affichage.
     */
    public function __toString(): string
    {
        return $this->value;
    }
}
