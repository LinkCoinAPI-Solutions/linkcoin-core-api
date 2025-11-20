<?php

declare(strict_types=1);

namespace App\Account\Api\ApiResource\V1;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Patch;
use App\Account\Api\State\V1\Processor\UserProfileUpdateProcessor;
use App\Account\Api\State\V1\Provider\UserProfileProvider;
use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Dto\V1\Output\UserProfileOutput;

/**
 * Ressource API Platform pour la gestion du profil utilisateur.
 * Permet de consulter et modifier les informations du compte.
 * Requiert une authentification complète pour toutes les routes.
 */
#[ApiResource(
    shortName: 'Account',
    security: "is_granted('IS_AUTHENTICATED_FULLY')",
    operations: [
        // Récupération du profil utilisateur courant
        new Get(
            uriTemplate: '/v1/account/profile',
            name: 'api_v1_account_profile_get',
            output: UserProfileOutput::class,
            provider: UserProfileProvider::class,
            normalizationContext: ['groups' => ['profile:read']],
        ),
        
        // Mise à jour partielle du profil (PATCH)
        new Patch(
            uriTemplate: '/v1/account/profile',
            name: 'api_v1_account_profile_update',
            input: UserProfileUpdateInput::class,
            output: UserProfileOutput::class,
            processor: UserProfileUpdateProcessor::class,
            denormalizationContext: ['groups' => ['profile:write']],
            normalizationContext: ['groups' => ['profile:read']],
            validationContext: ['groups' => ['Default', 'profile:write']],
        ),
    ]
)]
final readonly class AccountApiResource
{
    public const VERSION = 'v1';
    public const BASE_PATH = '/api/v1/account';
}
