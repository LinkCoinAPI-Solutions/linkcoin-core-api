<?php

namespace App\Auth\Application\Dto\V1\Input;

use App\Shared\Infrastructure\Validator\Constraints\ValidEmail;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class RequestPasswordResetInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Email address is required.')]
        #[ValidEmail]
        #[Groups(['password-reset:write'])]
        public string $email = '',
    ) {
    }
}
