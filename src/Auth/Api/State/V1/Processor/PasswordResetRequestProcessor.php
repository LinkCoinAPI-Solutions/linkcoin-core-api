<?php

declare(strict_types=1);

namespace App\Auth\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Auth\Application\Dto\V1\Input\RequestPasswordResetInput;
use App\Auth\Application\Dto\V1\Output\RequestPasswordResetOutput;
use App\Auth\Application\Service\PasswordResetRequestService;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour demander une réinitialisation de mot de passe.
 * Route : POST /api/v1/auth/password-reset/request
 */
final readonly class PasswordResetRequestProcessor implements ProcessorInterface
{
    public function __construct(
        private PasswordResetRequestService $passwordResetRequestService,
        private LoggerInterface $logger
    ) {}

    /**
     * Génère un token de réinitialisation et envoie l'email.
     * Retourne toujours un succès pour éviter l'énumération d'emails.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): RequestPasswordResetOutput {
        assert($data instanceof RequestPasswordResetInput);

        try {
            // Génère le token et envoie l'email
            $this->passwordResetRequestService->execute($data);

            $this->logger->info('Password reset request processed via API', [
                'email' => $data->email,
            ]);

            // Message volontairement vague pour la sécurité
            return new RequestPasswordResetOutput(
                success: true,
                message: 'If your email exists in our system, a password reset link has been sent.',
                userEmail: $data->email
            );
        } catch (\Throwable $e) {
            $this->logger->error('Password reset request failed via API', [
                'email' => $data->email,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
            ]);
            throw $e;
        }
    }
}
