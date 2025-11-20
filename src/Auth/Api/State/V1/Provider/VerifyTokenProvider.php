<?php

declare(strict_types=1);

namespace App\Auth\Api\State\V1\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Auth\Application\Dto\V1\Output\UserOutput;
use App\Auth\Application\Dto\V1\Output\VerifyTokenOutput;
use App\Auth\Domain\Exception\AuthException;
use App\Account\Domain\Entity\User;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Psr\Log\LoggerInterface;

/**
 * Provider API Platform pour vérifier le token Bearer.
 * Route : GET /api/v1/auth/verify
 */
final readonly class VerifyTokenProvider implements ProviderInterface
{
    public function __construct(
        private UserContextInterface $userContext,
        private RequestStack $requestStack,
        private LoggerInterface $logger
    ) {}

    /**
     * Vérifie le token Bearer et retourne les infos utilisateur.
     * Utilisé par le frontend pour valider la session.
     */
    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = []
    ): VerifyTokenOutput {
        try {
            $user = $this->userContext->getCurrentUser();

            // Extrait le token depuis l'entête Authorization
            $token = $this->extractBearerToken();

            // Transforme l'utilisateur en DTO
            $userOutput = $this->buildUserOutput($user);

            $this->logger->info('Token verification completed via API', [
                'user_id' => $user->getId(),
                'user_email' => $user->getEmail(),
                'operation' => $operation->getName(),
            ]);

            return new VerifyTokenOutput($userOutput, $token);
        } catch (\Throwable $e) {
            $this->logger->error('Token verification failed via API', [
                'error' => $e->getMessage(),
                'exception_class' => get_class($e),
                'operation' => $operation->getName(),
            ]);
            throw $e;
        }
    }

    /**
     * Récupère la requête HTTP courante.
     */
    private function getCurrentRequest(): ?\Symfony\Component\HttpFoundation\Request
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * Extrait le token Bearer depuis l'entête Authorization.
     * Format attendu : "Bearer {token}"
     * 
     * @throws AuthException Si le token est absent ou mal formé
     */
    private function extractBearerToken(): string
    {
        $request = $this->getCurrentRequest();
        if (!$request) {
            throw AuthException::invalidToken('no_request');
        }

        $authorization = trim((string) $request->headers->get('Authorization'));
        if ($authorization === '') {
            throw AuthException::invalidToken('bearer_empty');
        }

        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            throw AuthException::invalidToken('bearer_format');
        }

        return $matches[1];
    }

    /**
     * Transforme l'entité User en DTO pour l'API.
     */
    private function buildUserOutput(User $user): UserOutput
    {
        $profile = $user->getProfile();

        return new UserOutput(
            id: (string) $user->getId(),
            email: $user->getEmail(),
            name: $user->getFirstname(),
            avatar: $profile?->getAvatarUrl() ?? null
        );
    }
}
