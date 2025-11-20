<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class UserOutput
{
    public function __construct(
        #[Groups(['verify-token:read', 'register:read'])]
        public string $id,

        #[Groups(['verify-token:read', 'register:read'])]
        public string $email,

        #[Groups(['verify-token:read', 'register:read'])]
        public string $name,

        #[Groups(['verify-token:read', 'register:read'])]
        public ?string $avatar = null
    ) {}
}
