<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class RequestPasswordResetOutput
{
    public function __construct(
        #[Groups(['password-reset:read'])]
        public bool $success,

        #[Groups(['password-reset:read'])]
        public string $message,

        #[Groups(['password-reset:read'])]
        public ?string $userEmail = null,
    ) {
    }
}
