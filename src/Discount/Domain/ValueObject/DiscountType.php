<?php

namespace App\Discount\Domain\ValueObject;

/**
 * Enum représentant les types de réduction possibles.
 * Remplace les strings magiques par des valeurs typées.
 * Garantit que seuls les types valides existent.
 */
enum DiscountType: string
{
    case FIXED = 'fixed';
    case PERCENTAGE = 'percentage';
    case NONE = 'none';

    /**
     * Retourne le nom affiché pour l'utilisateur.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::FIXED => 'Montant fixe',
            self::PERCENTAGE => 'Pourcentage',
            self::NONE => 'Aucune réduction',
        };
    }

    /**
     * Vérifie si le type nécessite un montant.
     */
    public function requiresValue(): bool
    {
        return match ($this) {
            self::FIXED, self::PERCENTAGE => true,
            self::NONE => false,
        };
    }

    /**
     * Retourne l'unité affichée (€ ou %).
     */
    public function getUnit(): string
    {
        return match ($this) {
            self::FIXED => '€',
            self::PERCENTAGE => '%',
            self::NONE => '',
        };
    }

    /**
     * Crée depuis une chaîne (pour mapping depuis DB).
     * 
     * @throws \ValueError Si type invalide
     */
    public static function fromString(string $type): self
    {
        return self::from($type);
    }
}
