<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class ConfirmEmailOutput
{
    public function __construct(
        #[Groups(['confirm-email:read'])]
        public bool $success,

        #[Groups(['confirm-email:read'])]
        public string $message,

        #[Groups(['confirm-email:read'])]
        public ?string $userEmail = null,
    ) {
    }
}
