<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class ResetPasswordOutput
{
    public function __construct(
        #[Groups(['reset-password:read'])]
        public bool $success,

        #[Groups(['reset-password:read'])]
        public string $message,

        #[Groups(['reset-password:read'])]
        public ?string $userEmail = null,
    ) {
    }
}
