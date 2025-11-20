<?php

namespace App\Account\Application\Dto\V1\Input;

use App\Account\Infrastructure\Validator\Constraints\ValidProfileData;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * DTO pour la mise à jour du profil utilisateur.
 * Validation technique au niveau Infrastructure/Application.
 */
#[ValidProfileData]
class UserProfileUpdateInput
{
    #[Assert\NotBlank(message: 'First name is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['profile:write'])]
    public string $firstName;

    #[Assert\NotBlank(message: 'Last name is required')]
    #[Assert\Length(min: 2, max: 100)]
    #[Groups(['profile:write'])]
    public string $lastName;

    #[Assert\Length(max: 20)]
    #[Groups(['profile:write'])]
    public ?string $phone = null;

    #[Assert\Length(max: 100)]
    #[Groups(['profile:write'])]
    public ?string $company = null;

    #[Assert\Length(max: 100)]
    #[Groups(['profile:write'])]
    public ?string $position = null;

    #[Groups(['profile:write'])]
    public ?string $bio = null;

    #[Assert\Url]
    #[Assert\Length(max: 255)]
    #[Groups(['profile:write'])]
    public ?string $website = null;

    #[Assert\Length(max: 100)]
    #[Groups(['profile:write'])]
    public ?string $location = null;

    #[Groups(['profile:write'])]
    public ?bool $emailNotifications = null;

    #[Groups(['profile:write'])]
    public ?bool $emailSecurityAlerts = null;

    #[Groups(['profile:write'])]
    public ?bool $pushNotifications = null;

    #[Groups(['profile:write'])]
    public ?bool $newsletter = null;

    /**
     * Constructeur avec paramètres nommés.
     */
    public function __construct(
        string $firstName,
        string $lastName,
        ?string $phone = null,
        ?string $company = null,
        ?string $position = null,
        ?string $bio = null,
        ?string $website = null,
        ?string $location = null,
        ?bool $emailNotifications = null,
        ?bool $emailSecurityAlerts = null,
        ?bool $pushNotifications = null,
        ?bool $newsletter = null
    ) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->phone = $phone;
        $this->company = $company;
        $this->position = $position;
        $this->bio = $bio;
        $this->website = $website;
        $this->location = $location;
        $this->emailNotifications = $emailNotifications;
        $this->emailSecurityAlerts = $emailSecurityAlerts;
        $this->pushNotifications = $pushNotifications;
        $this->newsletter = $newsletter;
    }
}
