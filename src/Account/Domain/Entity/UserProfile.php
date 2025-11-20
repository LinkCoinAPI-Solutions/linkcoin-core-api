<?php

namespace App\Account\Domain\Entity;

use App\Account\Infrastructure\Repository\UserProfileRepository;
use App\Shared\Domain\ValueObject\PersonName;
use App\Shared\Domain\ValueObject\PhoneNumber;
use App\Shared\Domain\ValueObject\Url;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entité représentant le profil utilisateur.
 * Utilise des Value Objects pour garantir la cohérence des données.
 * Stocke les informations complémentaires (bio, entreprise, téléphone, etc.).
 */
#[ORM\Entity(repositoryClass: UserProfileRepository::class)]
#[ORM\Table(name: 'user_profiles')]
#[ORM\HasLifecycleCallbacks]
class UserProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['profile:read'])]
    private ?int $id = null;

    /**
     * Utilisateur propriétaire du profil (relation 1:1).
     */
    #[ORM\OneToOne(inversedBy: 'profile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['profile:read'])]
    private ?User $user = null;

    /**
     * Numéro de téléphone.
     */
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $phone = null;

    /**
     * Biographie de l'utilisateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'La bio ne peut pas dépasser {{ limit }} caractères.')]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $bio = null;

    /**
     * Entreprise de l'utilisateur.
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $company = null;

    /**
     * Poste occupé.
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $position = null;

    /**
     * Site web personnel ou professionnel.
     */
    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Url(message: 'Veuillez entrer une URL valide.')]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $website = null;

    /**
     * Localisation géographique.
     */
    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\Length(max: 100)]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $location = null;

    /**
     * URL de l'avatar.
     */
    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: 'Veuillez entrer une URL valide pour l\'avatar.')]
    #[Assert\Length(max: 500)]
    #[Groups(['profile:read', 'profile:write'])]
    private ?string $avatarUrl = null;

    /**
     * Date de création du profil.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['profile:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['profile:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Hook Doctrine : met à jour automatiquement updatedAt.
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ==================== GESTION TÉLÉPHONE AVEC VALUE OBJECT ====================

    /**
     * Retourne le téléphone comme Value Object.
     */
    public function getPhoneAsValueObject(): ?PhoneNumber
    {
        return $this->phone ? PhoneNumber::fromString($this->phone) : null;
    }

    /**
     * Définit le téléphone via Value Object.
     */
    public function setPhoneFromValueObject(PhoneNumber $phone): self
    {
        $this->phone = $phone->value;
        return $this;
    }

    /**
     * Retourne le téléphone (pour Doctrine/API).
     */
    public function getPhone(): ?string
    {
        return $this->phone;
    }

    /**
     * Définit le téléphone (pour hydratation Doctrine).
     */
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone ? trim($phone) : null;
        return $this;
    }

    // ==================== GESTION WEBSITE AVEC VALUE OBJECT ====================

    /**
     * Retourne le site web comme Value Object.
     */
    public function getWebsiteAsValueObject(): ?Url
    {
        return $this->website ? Url::fromString($this->website) : null;
    }

    /**
     * Définit le site web via Value Object.
     */
    public function setWebsiteFromValueObject(Url $website): self
    {
        $this->website = $website->value;
        return $this;
    }

    /**
     * Retourne le site web (pour Doctrine/API).
     */
    public function getWebsite(): ?string
    {
        return $this->website;
    }

    /**
     * Définit le site web (pour hydratation Doctrine).
     */
    public function setWebsite(?string $website): self
    {
        $this->website = $website ? trim($website) : null;
        return $this;
    }

    // ==================== GESTION AVATAR AVEC VALUE OBJECT ====================

    /**
     * Retourne l'avatar URL comme Value Object.
     */
    public function getAvatarUrlAsValueObject(): ?Url
    {
        return $this->avatarUrl ? Url::fromString($this->avatarUrl) : null;
    }

    /**
     * Définit l'avatar URL via Value Object.
     */
    public function setAvatarUrlFromValueObject(Url $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl->value;
        return $this;
    }

    /**
     * Retourne l'avatar URL (pour Doctrine/API).
     */
    public function getAvatarUrl(): ?string
    {
        return $this->avatarUrl;
    }

    /**
     * Définit l'avatar URL (pour hydratation Doctrine).
     */
    public function setAvatarUrl(?string $avatarUrl): self
    {
        $this->avatarUrl = $avatarUrl ? trim($avatarUrl) : null;
        return $this;
    }

    // ==================== GESTION COMPANY/POSITION AVEC VALUE OBJECT ====================

    /**
     * Retourne l'entreprise comme Value Object.
     */
    public function getCompanyAsValueObject(): ?PersonName
    {
        return $this->company ? PersonName::fromString($this->company) : null;
    }

    /**
     * Définit l'entreprise via Value Object.
     */
    public function setCompanyFromValueObject(PersonName $company): self
    {
        $this->company = $company->value;
        return $this;
    }

    /**
     * Retourne l'entreprise (pour Doctrine/API).
     */
    public function getCompany(): ?string
    {
        return $this->company;
    }

    /**
     * Définit l'entreprise (pour hydratation Doctrine).
     */
    public function setCompany(?string $company): self
    {
        $this->company = $company ? PersonName::fromString($company)->value : null;
        return $this;
    }

    /**
     * Retourne le poste comme Value Object.
     */
    public function getPositionAsValueObject(): ?PersonName
    {
        return $this->position ? PersonName::fromString($this->position) : null;
    }

    /**
     * Définit le poste via Value Object.
     */
    public function setPositionFromValueObject(PersonName $position): self
    {
        $this->position = $position->value;
        return $this;
    }

    /**
     * Retourne le poste (pour Doctrine/API).
     */
    public function getPosition(): ?string
    {
        return $this->position;
    }

    /**
     * Définit le poste (pour hydratation Doctrine).
     */
    public function setPosition(?string $position): self
    {
        $this->position = $position ? PersonName::fromString($position)->value : null;
        return $this;
    }

    // ==================== GETTERS ET SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): self
    {
        $this->bio = $bio ? trim($bio) : null;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location ? trim($location) : null;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Vérifie si le profil est complet.
     */
    #[Groups(['profile:read'])]
    public function isComplete(): bool
    {
        return $this->hasContactInfo() && $this->hasWorkInfo();
    }

    /**
     * Vérifie si les informations de contact sont renseignées.
     */
    #[Groups(['profile:read'])]
    public function hasContactInfo(): bool
    {
        return !empty($this->phone) || !empty($this->bio);
    }

    /**
     * Vérifie si les informations professionnelles sont renseignées.
     */
    #[Groups(['profile:read'])]
    public function hasWorkInfo(): bool
    {
        return !empty($this->company) && !empty($this->position);
    }

    /**
     * Retourne le nom complet de l'utilisateur.
     */
    #[Groups(['profile:read'])]
    public function getFullName(): string
    {
        return trim(($this->user?->getFirstName() ?? '') . ' ' . ($this->user?->getLastName() ?? ''));
    }
}
