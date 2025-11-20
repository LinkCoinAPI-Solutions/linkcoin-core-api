<?php

namespace App\Auth\Application\Dto\V1\Output;

use Symfony\Component\Serializer\Annotation\Groups;

final class VerifyTokenOutput
{
    #[Groups(['verify-token:read'])]
    public bool $valid = true;

    #[Groups(['verify-token:read'])]
    public UserOutput $user;

    #[Groups(['verify-token:read'])]
    public string $token;

    public function __construct(UserOutput $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }
}
