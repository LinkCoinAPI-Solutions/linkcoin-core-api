<?php

namespace App\Account\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DTO de sortie représentant le profil complet d'un utilisateur.
 * Readonly pour garantir l'immutabilité après création.
 */
readonly class UserProfileOutput
{
    public function __construct(
        public int $id,

        #[Groups(['profile:read'])]
        public string $email,

        #[Groups(['profile:read'])]
        public string $firstName,

        #[Groups(['profile:read'])]
        public string $lastName,

        #[Groups(['profile:read'])]
        public ?string $phone,

        #[Groups(['profile:read'])]
        public ?string $company,

        #[Groups(['profile:read'])]
        public ?string $position,

        #[Groups(['profile:read'])]
        public ?string $bio,

        #[Groups(['profile:read'])]
        public ?string $website,

        #[Groups(['profile:read'])]
        public ?string $location,

        #[Groups(['profile:read'])]
        public ?string $avatarUrl,

        #[Groups(['profile:read'])]
        public bool $isActive,

        #[Groups(['profile:read'])]
        public bool $isEmailVerified,

        #[Groups(['profile:read'])]
        public bool $emailNotifications,

        #[Groups(['profile:read'])]
        public bool $emailSecurityAlerts,

        #[Groups(['profile:read'])]
        public bool $pushNotifications,

        #[Groups(['profile:read'])]
        public bool $newsletter,

        #[Groups(['profile:read'])]
        public string $createdAt,

        #[Groups(['profile:read'])]
        public ?string $updatedAt,
    ) {}
}
