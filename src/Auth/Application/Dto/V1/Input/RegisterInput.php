<?php

namespace App\Auth\Application\Dto\V1\Input;

use App\Auth\Infrastructure\Validator\Constraints\UniqueEmail;
use App\Auth\Infrastructure\Validator\Constraints\SecurePassword;
use App\Shared\Infrastructure\Validator\Constraints\ValidEmail;
use App\Shared\Infrastructure\Validator\Constraints\ValidName;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[SecurePassword]
final class RegisterInput
{
    public function __construct(
        #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
        #[ValidName]
        #[Groups(['register:write'])]
        public string $firstName = '',

        #[Assert\NotBlank(message: 'Le nom de famille ne peut pas être vide.')]
        #[ValidName]
        #[Groups(['register:write'])]
        public string $lastName = '',

        #[Assert\NotBlank(message: 'L\'adresse email ne peut pas être vide.')]
        #[UniqueEmail]
        #[ValidEmail]
        #[Groups(['register:write'])]
        public string $email = '',

        #[Assert\NotBlank(message: 'Le mot de passe est requis.')]
        #[Groups(['register:write'])]
        public string $password = '',

        #[Assert\NotBlank(message: 'La confirmation du mot de passe ne peut pas être vide.')]
        #[Assert\EqualTo(
            propertyPath: 'password', 
            message: 'Les mots de passe ne correspondent pas.'
        )]
        #[Groups(['register:write'])]
        public string $confirmPassword = '',
    ) {
    }
}
