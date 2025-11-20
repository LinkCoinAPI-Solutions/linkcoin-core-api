<?php

declare(strict_types=1);

namespace App\Account\Application\Mapper\Contract;

use App\Account\Application\Dto\V1\Output\UserProfileOutput;
use App\Account\Domain\Entity\User;

/**
 * Interface pour mapper l'entité User vers le DTO API.
 * Centralise la transformation des données pour la couche API.
 */
interface UserProfileMapperInterface
{
    /**
     * Transforme un User en DTO utilisable par l'API.
     * Agrège les données de User, Profile et NotificationPreferences.
     * 
     * @return UserProfileOutput DTO pour la réponse API
     */
    public function toApiOutput(User $user): UserProfileOutput;
}
