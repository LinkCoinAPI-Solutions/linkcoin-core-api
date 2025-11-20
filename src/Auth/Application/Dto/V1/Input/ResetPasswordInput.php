<?php

namespace App\Auth\Application\Dto\V1\Input;

use App\Auth\Infrastructure\Validator\Constraints\ResetPassword;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ResetPassword]
final class ResetPasswordInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le nouveau mot de passe est requis.')]
        #[Groups(['reset-password:write'])]
        public string $newPassword = '',

        #[Assert\NotBlank(message: 'La confirmation du mot de passe est requise.')]
        #[Groups(['reset-password:write'])]
        public string $confirmPassword = '',

        public string $token = '',
    ) {}

    public function withToken(string $token): self
    {
        return new self(
            $this->newPassword,
            $this->confirmPassword,
            $token
        );
    }
}
