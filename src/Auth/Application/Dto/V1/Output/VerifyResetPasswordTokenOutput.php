<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class VerifyResetPasswordTokenOutput
{
    public function __construct(
        #[Groups(['verify-reset-password-token:read'])]
        public bool $valid,

        #[Groups(['verify-reset-password-token:read'])]
        public string $message,

        #[Groups(['verify-reset-password-token:read'])]
        public ?string $userEmail = null,

        #[Groups(['verify-reset-password-token:read'])]
        public ?\DateTimeInterface $expiresAt = null,

        #[Groups(['verify-reset-password-token:read'])]
        public ?int $userId = null,
    ) {
    }
}
