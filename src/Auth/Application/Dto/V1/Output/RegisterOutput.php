<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class RegisterOutput
{
    public function __construct(
        #[Groups(['register:read'])]
        public bool $success,

        #[Groups(['register:read'])]
        public string $message,

        #[Groups(['register:read'])]
        public ?int $userId = null,

        #[Groups(['register:read'])]
        public ?string $userEmail = null,
    ) {
    }
}
