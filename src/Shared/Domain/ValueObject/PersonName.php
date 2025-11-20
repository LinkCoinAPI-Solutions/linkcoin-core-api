<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\SharedKernelException;

/**
 * Value Object représentant un nom de personne.
 * SHARED KERNEL : Utilisé par Account, UserProfile, etc.
 * 
 * Encapsule la validation (longueur, caractères autorisés).
 * Immutable : ne peut pas être modifié après création.
 * Flexible : formatage optionnel selon le contexte.
 */
readonly class PersonName
{
    private const MIN_LENGTH = 1;
    private const MAX_LENGTH = 100;
    private const VALID_PATTERN = '/^[a-zA-ZÀ-ÿ\s\'-]+$/u';

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée un nom depuis une chaîne.
     * Trim automatique, pas de formatage forcé.
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function fromString(string $name): self
    {
        $trimmed = trim($name);
        self::validate($trimmed);
        
        return new self($trimmed);
    }

    /**
     * Crée un prénom formaté (Title Case).
     * Gère les prénoms composés avec tiret (Jean-Paul → Jean-Paul).
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function createFirstName(string $firstName): self
    {
        $formatted = self::formatAsFirstName($firstName);
        self::validate($formatted);
        
        return new self($formatted);
    }

    /**
     * Crée un nom de famille formaté (MAJUSCULES).
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function createLastName(string $lastName): self
    {
        $formatted = self::formatAsLastName($lastName);
        self::validate($formatted);
        
        return new self($formatted);
    }

    /**
     * Valide le nom.
     * 
     * @throws SharedKernelException Si invalide
     */
    private static function validate(string $name): void
    {
        if (empty($name)) {
            throw SharedKernelException::emptyName();
        }

        $length = mb_strlen($name);
        if ($length < self::MIN_LENGTH || $length > self::MAX_LENGTH) {
            throw SharedKernelException::invalidNameLength(self::MIN_LENGTH, self::MAX_LENGTH, $length);
        }

        if (!preg_match(self::VALID_PATTERN, $name)) {
            throw SharedKernelException::invalidNameCharacters();
        }
    }

    /**
     * Formate en Title Case (pour prénoms).
     * Gère les tirets : Jean-Paul → Jean-Paul
     */
    private static function formatAsFirstName(string $value): string
    {
        $trimmed = trim($value);
        $titleCase = mb_convert_case($trimmed, MB_CASE_TITLE, 'UTF-8');
        
        // Majuscule après chaque tiret
        return preg_replace_callback(
            '/(-[a-zA-ZÀ-ÿ])/u',
            fn(array $matches): string => '-' . mb_strtoupper($matches[1][1], 'UTF-8'),
            $titleCase
        );
    }

    /**
     * Formate en MAJUSCULES (pour noms de famille).
     */
    private static function formatAsLastName(string $value): string
    {
        return mb_strtoupper(trim($value), 'UTF-8');
    }

    /**
     * Retourne le nom avec la première lettre en majuscule.
     * Méthode utilitaire pour formatage post-création.
     */
    public function capitalize(): string
    {
        return mb_convert_case($this->value, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Retourne le nom en majuscules.
     * Méthode utilitaire pour formatage post-création.
     */
    public function toUpperCase(): string
    {
        return mb_strtoupper($this->value, 'UTF-8');
    }

    /**
     * Retourne le nom en minuscules.
     * Méthode utilitaire pour formatage post-création.
     */
    public function toLowerCase(): string
    {
        return mb_strtolower($this->value, 'UTF-8');
    }

    /**
     * Retourne la première lettre (pour initiales).
     */
    public function getInitial(): string
    {
        return mb_strtoupper(mb_substr($this->value, 0, 1));
    }

    /**
     * Compare avec un autre nom (insensible à la casse).
     */
    public function equalsIgnoreCase(self $other): bool
    {
        return mb_strtolower($this->value, 'UTF-8') === mb_strtolower($other->value, 'UTF-8');
    }

    /**
     * Compare avec un autre nom (sensible à la casse).
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
