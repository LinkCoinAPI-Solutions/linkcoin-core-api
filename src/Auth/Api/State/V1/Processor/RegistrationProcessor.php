<?php

declare(strict_types=1);

namespace App\Auth\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Auth\Application\Dto\V1\Input\RegisterInput;
use App\Auth\Application\Dto\V1\Output\RegisterOutput;
use App\Auth\Application\Service\RegistrationService;
use App\Auth\Domain\Exception\AuthException;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour l'inscription utilisateur.
 * Route : POST /api/v1/auth/register
 */
final readonly class RegistrationProcessor implements ProcessorInterface
{
    public function __construct(
        private RegistrationService $registrationService,
        private LoggerInterface $logger,
        private UserContextInterface $userContext
    ) {}

    /**
     * Crée un nouveau compte utilisateur et envoie l'email de confirmation.
     * Interdit si l'utilisateur est déjà connecté.
     */
    public function process(
        mixed $data,
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): RegisterOutput {
        assert($data instanceof RegisterInput);

        // Empêche l'inscription si déjà connecté
        if ($this->userContext->isUserAuthenticated()) {
            throw AuthException::authenticationFailed('User already authenticated');
        }

        try {
            // Crée le compte et envoie l'email
            $user = $this->registrationService->execute($data);

            $this->logger->info('User registration completed via API', [
                'user_id' => $user->getId(),
                'email' => $user->getEmail(),
                'operation' => $operation->getName(),
            ]);

            return new RegisterOutput(
                success: true,
                message: 'Your account has been created successfully! Please check your email to confirm your account before signing in.',
                userId: $user->getId(),
                userEmail: $user->getEmail()
            );
        } catch (\Throwable $e) {
            $this->logger->error('User registration failed via API', [
                'email' => $data->email,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'operation' => $operation->getName(),
            ]);

            throw $e;
        }
    }
}
