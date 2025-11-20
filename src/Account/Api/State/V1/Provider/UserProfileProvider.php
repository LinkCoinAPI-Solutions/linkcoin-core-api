<?php

declare(strict_types=1);

namespace App\Account\Api\State\V1\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Account\Application\Dto\V1\Output\UserProfileOutput;
use App\Account\Application\Service\UserProfileService;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Provider API Platform pour récupérer le profil utilisateur.
 * Route : GET /api/v1/account/profile
 */
final readonly class UserProfileProvider implements ProviderInterface
{
    public function __construct(
        private UserProfileService $service,
        private UserContextInterface $userContext,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère et retourne le profil de l'utilisateur connecté.
     * Inclut les informations personnelles, préférences et dates.
     */
    public function provide(
        Operation $operation, 
        array $uriVariables = [], 
        array $context = []
    ): UserProfileOutput {
        // Récupère l'utilisateur authentifié
        $user = $this->userContext->getCurrentUser();

        $this->logger->info('Récupération du profil utilisateur', [
            'user_id' => $user->getId(),
            'operation' => $operation->getName(),
        ]);

        // Récupère le profil via le service métier
        $profile = $this->service->getUserProfile($user);

        $this->logger->info('Profil utilisateur récupéré avec succès', [
            'user_id' => $user->getId(),
            'operation' => $operation->getName(),
        ]);

        return $profile;
    }
}
