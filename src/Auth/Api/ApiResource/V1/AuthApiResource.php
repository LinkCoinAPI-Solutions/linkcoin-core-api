<?php

declare(strict_types=1);

namespace App\Auth\Api\ApiResource\V1;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Auth\Application\Dto\V1\Input\RegisterInput;
use App\Auth\Application\Dto\V1\Input\RequestPasswordResetInput;
use App\Auth\Application\Dto\V1\Input\ResetPasswordInput;
use App\Auth\Application\Dto\V1\Output\RegisterOutput;
use App\Auth\Application\Dto\V1\Output\RequestPasswordResetOutput;
use App\Auth\Application\Dto\V1\Output\ResetPasswordOutput;
use App\Auth\Application\Dto\V1\Output\VerifyResetPasswordTokenOutput;
use App\Auth\Application\Dto\V1\Output\ConfirmEmailOutput;
use App\Auth\Application\Dto\V1\Output\VerifyTokenOutput;
use App\Auth\Api\State\V1\Provider\VerifyTokenProvider;
use App\Auth\Api\State\V1\Provider\ResetTokenVerificationProvider;
use App\Auth\Api\State\V1\Provider\EmailConfirmationProvider;
use App\Auth\Api\State\V1\Processor\RegistrationProcessor;
use App\Auth\Api\State\V1\Processor\PasswordResetRequestProcessor;
use App\Auth\Api\State\V1\Processor\ResetPasswordProcessor;

/**
 * Ressource API Platform regroupant toutes les opérations d'authentification.
 * Gère l'inscription, la réinitialisation de mot de passe et la confirmation d'email.
 */
#[ApiResource(
    shortName: 'Auth',
    operations: [
        // Vérification du token Bearer actuel
        new Get(
            uriTemplate: '/v1/auth/verify',
            security: "is_granted('ROLE_USER')",
            name: 'api_v1_auth_verify_token',
            output: VerifyTokenOutput::class,
            provider: VerifyTokenProvider::class,
            normalizationContext: ['groups' => ['verify-token:read']],
        ),
        
        // Inscription d'un nouvel utilisateur
        new Post(
            uriTemplate: '/v1/auth/register',
            security: "is_granted('PUBLIC_ACCESS')",
            name: 'api_v1_auth_register',
            input: RegisterInput::class,
            output: RegisterOutput::class,
            processor: RegistrationProcessor::class,
            denormalizationContext: ['groups' => ['register:write']],
            normalizationContext: ['groups' => ['register:read']],
        ),
        
        // Demande de réinitialisation de mot de passe
        new Post(
            uriTemplate: '/v1/auth/password-reset/request',
            security: "is_granted('PUBLIC_ACCESS')",
            name: 'api_v1_auth_request_password_reset',
            input: RequestPasswordResetInput::class,
            output: RequestPasswordResetOutput::class,
            processor: PasswordResetRequestProcessor::class,
            denormalizationContext: ['groups' => ['password-reset:write']],
            normalizationContext: ['groups' => ['password-reset:read']],
        ),
        
        // Vérification d'un token de réinitialisation
        new Get(
            uriTemplate: '/v1/auth/reset-password/{token}/verify',
            security: "is_granted('PUBLIC_ACCESS')",
            name: 'api_v1_auth_verify_reset_password_token',
            output: VerifyResetPasswordTokenOutput::class,
            provider: ResetTokenVerificationProvider::class,
            normalizationContext: ['groups' => ['verify-reset-password-token:read']],
        ),
        
        // Réinitialisation effective du mot de passe
        new Post(
            uriTemplate: '/v1/auth/reset-password/{token}',
            security: "is_granted('PUBLIC_ACCESS')",
            name: 'api_v1_auth_reset_password',
            input: ResetPasswordInput::class,
            output: ResetPasswordOutput::class,
            processor: ResetPasswordProcessor::class,
            denormalizationContext: ['groups' => ['reset-password:write']],
            normalizationContext: ['groups' => ['reset-password:read']],
        ),
        
        // Confirmation d'email via token
        new Get(
            uriTemplate: '/v1/auth/confirm-email/{token}',
            security: "is_granted('PUBLIC_ACCESS')",
            name: 'api_v1_auth_confirm_email',
            output: ConfirmEmailOutput::class,
            provider: EmailConfirmationProvider::class,
            normalizationContext: ['groups' => ['confirm-email:read']],
        ),
    ]
)]
final readonly class AuthApiResource
{
    public const VERSION = 'v1';
    public const BASE_PATH = '/api/v1/auth';
}
