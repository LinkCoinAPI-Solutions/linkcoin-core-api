<?php

declare(strict_types=1);

namespace App\Account\Api\State\V1\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Dto\V1\Output\UserProfileOutput;
use App\Account\Application\Mapper\Contract\UserProfileMapperInterface;
use App\Account\Application\Service\UserProfileService;
use App\Shared\Infrastructure\Security\Contract\UserContextInterface;
use Psr\Log\LoggerInterface;

/**
 * Processor API Platform pour mettre à jour le profil utilisateur.
 * Route : PATCH /api/v1/account/profile
 */
final readonly class UserProfileUpdateProcessor implements ProcessorInterface
{
    public function __construct(
        private UserProfileService $service,
        private UserProfileMapperInterface $mapper,
        private UserContextInterface $userContext,
        private LoggerInterface $logger
    ) {}

    /**
     * Traite la mise à jour du profil.
     * Valide l'input, met à jour l'entité et retourne le DTO mis à jour.
     */
    public function process(
        mixed $data, 
        Operation $operation, 
        array $uriVariables = [], 
        array $context = []
    ): UserProfileOutput {
        assert($data instanceof UserProfileUpdateInput);

        // Récupère l'utilisateur authentifié
        $user = $this->userContext->getCurrentUser();

        $this->logger->info('Demande de mise à jour de profil', [
            'user_id' => $user->getId(),
            'operation' => $operation->getName(),
        ]);

        // Met à jour le profil via le service métier
        $updatedUser = $this->service->updateProfile($user, $data);

        $this->logger->info('Profil utilisateur mis à jour avec succès', [
            'user_id' => $user->getId(),
            'operation' => $operation->getName(),
        ]);

        // Transforme en DTO pour la réponse API
        return $this->mapper->toApiOutput($updatedUser);
    }
}
