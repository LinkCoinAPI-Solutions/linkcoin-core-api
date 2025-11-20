<?php

declare(strict_types=1);

namespace App\Auth\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Auth\Application\Dto\V1\Input\ResetPasswordInput;
use App\Auth\Application\Dto\V1\Output\ResetPasswordOutput;
use App\Auth\Application\Service\PasswordResetService;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour réinitialiser le mot de passe.
 * Route : POST /api/v1/auth/reset-password/{token}
 */
final readonly class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private LoggerInterface $logger
    ) {}

    /**
     * Réinitialise le mot de passe avec le token fourni.
     * Valide le token, met à jour le mot de passe et invalide le token.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): ResetPasswordOutput {
        assert($data instanceof ResetPasswordInput);

        // Injecte le token depuis l'URI
        $inputWithToken = $data->withToken($uriVariables['token'] ?? '');

        try {
            // Valide le token et met à jour le mot de passe
            $user = $this->passwordResetService->execute($inputWithToken);

            $this->logger->info('Password reset completed via API', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'token_prefix' => substr($data->token, 0, 8) . '...',
            ]);

            return new ResetPasswordOutput(
                success: true,
                message: 'Your password has been updated successfully. You can now sign in with your new password.',
                userEmail: $user->getEmail()
            );
        } catch (\Throwable $e) {
            $this->logger->error('Password reset failed via API', [
                'token_prefix' => substr($data->token, 0, 8) . '...',
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            throw $e;
        }
    }
}
