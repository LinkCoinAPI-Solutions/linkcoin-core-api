<?php

declare(strict_types=1);

namespace App\Auth\Api\State\V1\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Auth\Application\Dto\V1\Output\ConfirmEmailOutput;
use App\Auth\Application\Service\EmailConfirmationService;
use App\Shared\Domain\ValueObject\SecureToken;
use Psr\Log\LoggerInterface;

/**
 * Provider API Platform pour confirmer l'email.
 * Route : GET /api/v1/auth/confirm-email/{token}
 */
final readonly class EmailConfirmationProvider implements ProviderInterface
{
    public function __construct(
        private EmailConfirmationService $emailConfirmationService,
        private LoggerInterface $logger
    ) {}

    /**
     * Confirme l'email de l'utilisateur via le token.
     * Active le compte et marque l'email comme vérifié.
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ConfirmEmailOutput {
        $token = $uriVariables['token'] ?? '';

        try {
            // Valide le token et confirme l'email
            $user = $this->emailConfirmationService->execute($token);

            // Masquage sécurisé du token pour le logging
            $tokenMasked = $this->maskToken($token);

            $this->logger->info('Email confirmation completed via API', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'token_masked' => $tokenMasked,
                'operation' => $operation->getName(),
            ]);

            return new ConfirmEmailOutput(
                success: true,
                message: 'Your email address has been successfully confirmed. You can now sign in to your account.',
                userEmail: $user->getEmail()
            );
        } catch (\Throwable $e) {
            $this->logger->error('Email confirmation failed via API', [
                'token_prefix' => substr($token, 0, 8) . '...',
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'operation' => $operation->getName(),
            ]);
            throw $e;
        }
    }

    /**
     * Masque le token pour le logging sécurisé.
     * Utilise SecureToken::getMasked() si le token est valide, sinon substr().
     */
    private function maskToken(string $token): string
    {
        try {
            $secureToken = SecureToken::fromString($token);
            return $secureToken->getMasked();
        } catch (\Throwable) {
            // Si le token est invalide, utilise substr() comme fallback
            return substr($token, 0, 8) . '...';
        }
    }
}
