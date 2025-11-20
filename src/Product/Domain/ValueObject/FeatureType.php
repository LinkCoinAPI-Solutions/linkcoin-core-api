<?php

namespace App\Product\Domain\ValueObject;

/**
 * Enum représentant les types de caractéristiques produit.
 * Structure en tiers pour différencier les niveaux d'abonnement.
 */
enum FeatureType: string
{
    case STANDARD = 'standard';
    case PREMIUM = 'premium';
    case ENTERPRISE = 'enterprise';

    /**
     * Retourne le nom affiché pour l'utilisateur.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::PREMIUM => 'Premium',
            self::ENTERPRISE => 'Enterprise',
        };
    }

    /**
     * Vérifie si le type nécessite un abonnement payant.
     */
    public function requiresPaidSubscription(): bool
    {
        return $this !== self::STANDARD;
    }

    /**
     * Retourne le niveau de tier (pour comparaisons).
     * Standard < Premium < Enterprise
     */
    public function getTierLevel(): int
    {
        return match ($this) {
            self::STANDARD => 1,
            self::PREMIUM => 2,
            self::ENTERPRISE => 3,
        };
    }

    /**
     * Vérifie si ce type est supérieur ou égal à un autre.
     */
    public function isAtLeast(self $other): bool
    {
        return $this->getTierLevel() >= $other->getTierLevel();
    }
}
