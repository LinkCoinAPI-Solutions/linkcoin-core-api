<?php

declare(strict_types=1);

namespace App\Auth\Api\State\V1\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Auth\Application\Dto\V1\Output\VerifyResetPasswordTokenOutput;
use App\Auth\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\Auth\Domain\Exception\TokenException;
use App\Shared\Domain\ValueObject\SecureToken;
use Psr\Log\LoggerInterface;

/**
 * Provider API Platform pour vérifier un token de réinitialisation.
 * Route : GET /api/v1/auth/reset-password/{token}/verify
 */
final readonly class ResetTokenVerificationProvider implements ProviderInterface
{
    public function __construct(
        private PasswordResetTokenRepositoryInterface $tokenRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Vérifie la validité d'un token de réinitialisation.
     * Retourne les infos du token sans le consommer.
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): VerifyResetPasswordTokenOutput {
        $tokenString = $uriVariables['token'] ?? '';

        try {
            // Validation du format via SecureToken (lance SharedKernelException si invalide)
            $secureToken = SecureToken::fromString($tokenString);
            
            // Récupération et validation du token
            $tokenEntity = $this->tokenRepository->findOneByToken($secureToken->value);

            if (!$tokenEntity) {
                throw TokenException::tokenNotFound('password_reset');
            }

            if (!$tokenEntity->isValid()) {
                if ($tokenEntity->isExpired()) {
                    throw TokenException::expiredToken('password_reset', $tokenEntity->getExpiresAt());
                }
                
                if ($tokenEntity->isUsed()) {
                    throw TokenException::tokenAlreadyUsed('password_reset', $tokenEntity->getUsedAt());
                }
                
                throw TokenException::invalidToken('password_reset', $tokenString);
            }

            $user = $tokenEntity->getUser();

            $this->logger->info('Password reset token verification completed via API', [
                'token_masked' => $secureToken->getMasked(),
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'expires_at' => $tokenEntity->getExpiresAt()->format('Y-m-d H:i:s'),
                'operation' => $operation->getName(),
            ]);

            return new VerifyResetPasswordTokenOutput(
                valid: true,
                message: 'This password reset link is valid and ready to use.',
                userEmail: $user->getEmail(),
                expiresAt: $tokenEntity->getExpiresAt(),
                userId: $user->getId()
            );
        } catch (\Throwable $e) {
            $this->logger->error('Password reset token verification failed via API', [
                'token_prefix' => substr($tokenString, 0, 8) . '...',
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'operation' => $operation->getName(),
            ]);
            throw $e;
        }
    }
}
