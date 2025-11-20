<?php

namespace App\Product\Domain\ValueObject;

/**
 * Enum représentant les types de produits.
 * Remplace les strings magiques par des valeurs typées.
 */
enum ProductType: string
{
    case API_ACCESS = 'api_access';
    case DATA_PACKAGE = 'data_package';
    case PREMIUM_FEATURES = 'premium_features';

    /**
     * Retourne le nom affiché pour l'utilisateur.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::API_ACCESS => 'Accès API',
            self::DATA_PACKAGE => 'Package de données',
            self::PREMIUM_FEATURES => 'Fonctionnalités premium',
        };
    }

    /**
     * Vérifie si le type nécessite des endpoints API.
     */
    public function requiresApiEndpoints(): bool
    {
        return $this === self::API_ACCESS;
    }

    /**
     * Retourne les clés de métadonnées requises pour ce type.
     */
    public function getRequiredMetadataKeys(): array
    {
        return match ($this) {
            self::API_ACCESS => ['api_endpoints', 'supported_exchanges'],
            self::DATA_PACKAGE => ['data_sources', 'update_frequency'],
            self::PREMIUM_FEATURES => ['features_list'],
        };
    }
}
