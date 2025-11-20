<?php

namespace App\Account\Domain\Entity;

use App\Account\Infrastructure\Repository\UserRepository;
use App\Order\Domain\Entity\Order;
use App\Shared\Domain\ValueObject\Email;
use App\Shared\Domain\ValueObject\PersonName;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entité représentant un utilisateur.
 * Utilise des Value Objects pour garantir la cohérence des données.
 * Implémente UserInterface et PasswordAuthenticatedUserInterface pour Symfony Security.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['email'])]
#[ORM\Index(columns: ['username'])]
#[ORM\Index(columns: ['is_active'])]
#[ORM\Index(columns: ['created_at'])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['user:list', 'user:item', 'cart:item'])]
    private ?int $id = null;

    /**
     * Email de l'utilisateur (unique).
     */
    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide.')]
    #[Assert\Length(max: 180)]
    #[Groups(['user:list', 'user:item', 'user:write'])]
    private ?string $email = null;

    /**
     * Nom d'utilisateur (unique).
     */
    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\Length(max: 50)]
    #[Groups(['user:list', 'user:item', 'user:write', 'cart:item'])]
    private ?string $username = null;

    /**
     * Prénom de l'utilisateur.
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Le prénom ne peut pas être vide.')]
    #[Assert\Length(max: 100)]
    #[Groups(['user:item', 'user:write', 'cart:item'])]
    private ?string $firstName = null;

    /**
     * Nom de famille de l'utilisateur.
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank(message: 'Le nom de famille ne peut pas être vide.')]
    #[Assert\Length(max: 100)]
    #[Groups(['user:item', 'user:write', 'cart:item'])]
    private ?string $lastName = null;

    /**
     * Statut d'activation du compte.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:list', 'user:item'])]
    private bool $isActive = false;

    /**
     * Statut de vérification de l'email.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['user:item'])]
    private bool $isEmailVerified = false;

    /**
     * Rôles de l'utilisateur.
     */
    #[ORM\Column(type: Types::JSON)]
    #[Groups(['user:item', 'user:write'])]
    private array $roles = [];

    /**
     * Mot de passe hashé.
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(['user:write'])]
    private ?string $password = null;

    /**
     * Token de confirmation d'email.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $emailConfirmationToken = null;

    /**
     * Date de vérification de l'email.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $emailVerifiedAt = null;

    /**
     * Date de dernière connexion.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt = null;

    /**
     * Date de création du compte.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['user:list', 'user:item'])]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['user:item'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * Profil utilisateur (relation 1:1).
     */
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: UserProfile::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:item'])]
    private ?UserProfile $profile = null;

    /**
     * Préférences de notification (relation 1:1).
     */
    #[ORM\OneToOne(mappedBy: 'user', targetEntity: NotificationPreferences::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:item'])]
    private ?NotificationPreferences $notificationPreferences = null;

    /**
     * Commandes de l'utilisateur (relation 1:N).
     * 
     * @var Collection<int, Order>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->orders = new ArrayCollection();
        $this->roles = ['ROLE_USER'];
    }

    /**
     * Hook Doctrine : met à jour automatiquement updatedAt.
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ==================== GESTION EMAIL AVEC VALUE OBJECT ====================

    /**
     * Retourne l'email comme Value Object.
     */
    public function getEmailAsValueObject(): ?Email
    {
        return $this->email ? Email::fromString($this->email) : null;
    }

    /**
     * Définit l'email via Value Object.
     */
    public function setEmailFromValueObject(Email $email): self
    {
        $this->email = $email->value;
        $this->username = $email->value; // Username = email par défaut
        return $this;
    }

    /**
     * Retourne l'email (pour Doctrine/API/Symfony Security).
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Définit l'email (pour hydratation Doctrine).
     * Normalise automatiquement (lowercase + trim).
     */
    public function setEmail(string $email): self
    {
        $this->email = strtolower(trim($email));
        $this->username = $this->email;
        return $this;
    }

    // ==================== GESTION NOMS AVEC VALUE OBJECTS ====================

    /**
     * Retourne le prénom comme Value Object.
     */
    public function getFirstNameAsValueObject(): ?PersonName
    {
        return $this->firstName ? PersonName::fromString($this->firstName) : null;
    }

    /**
     * Définit le prénom via Value Object.
     */
    public function setFirstNameFromValueObject(PersonName $firstName): self
    {
        $this->firstName = $firstName->value;
        return $this;
    }

    /**
     * Retourne le prénom (pour Doctrine/API).
     */
    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    /**
     * Définit le prénom (pour hydratation Doctrine).
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = PersonName::createFirstName($firstName)->value;
        return $this;
    }

    /**
     * Retourne le nom comme Value Object.
     */
    public function getLastNameAsValueObject(): ?PersonName
    {
        return $this->lastName ? PersonName::fromString($this->lastName) : null;
    }

    /**
     * Définit le nom via Value Object.
     */
    public function setLastNameFromValueObject(PersonName $lastName): self
    {
        $this->lastName = $lastName->value;
        return $this;
    }

    /**
     * Retourne le nom (pour Doctrine/API).
     */
    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    /**
     * Définit le nom (pour hydratation Doctrine).
     */
    public function setLastName(string $lastName): self
    {
        $this->lastName = PersonName::createLastName($lastName)->value;
        return $this;
    }

    // ==================== GETTERS ET SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = trim($username);
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isEmailVerified(): bool
    {
        return $this->isEmailVerified;
    }

    public function setIsEmailVerified(bool $isEmailVerified): self
    {
        $this->isEmailVerified = $isEmailVerified;

        if ($isEmailVerified && !$this->emailVerifiedAt) {
            $this->emailVerifiedAt = new \DateTimeImmutable();
        }

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getProfile(): ?UserProfile
    {
        return $this->profile;
    }

    public function setProfile(?UserProfile $profile): self
    {
        if ($profile === null && $this->profile !== null) {
            $this->profile->setUser(null);
        }

        if ($profile !== null && $profile->getUser() !== $this) {
            $profile->setUser($this);
        }

        $this->profile = $profile;
        return $this;
    }

    public function getNotificationPreferences(): ?NotificationPreferences
    {
        return $this->notificationPreferences;
    }

    public function setNotificationPreferences(?NotificationPreferences $notificationPreferences): self
    {
        if ($notificationPreferences === null && $this->notificationPreferences !== null) {
            $this->notificationPreferences->setUser(null);
        }

        if ($notificationPreferences !== null && $notificationPreferences->getUser() !== $this) {
            $notificationPreferences->setUser($this);
        }

        $this->notificationPreferences = $notificationPreferences;
        return $this;
    }

    /**
     * @return Collection<int, Order>
     */
    public function getOrders(): Collection
    {
        return $this->orders;
    }

    // ==================== MÉTHODES UTILITAIRES ====================

    /**
     * Retourne le nom complet.
     */
    #[Groups(['user:list', 'user:item', 'cart:item'])]
    public function getFullName(): string
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /**
     * Retourne les initiales.
     */
    #[Groups(['user:list', 'user:item'])]
    public function getInitials(): string
    {
        $firstInitial = $this->firstName ? strtoupper($this->firstName[0]) : '';
        $lastInitial = $this->lastName ? strtoupper($this->lastName[0]) : '';
        return $firstInitial . $lastInitial;
    }

    /**
     * Retourne le nom d'affichage (fullName ou email).
     */
    #[Groups(['user:list', 'user:item'])]
    public function getDisplayName(): string
    {
        $fullName = $this->getFullName();
        return !empty(trim($fullName)) ? $fullName : $this->email;
    }

    #[Groups(['user:item'])]
    public function getAvatarUrl(): ?string
    {
        return $this->profile?->getAvatarUrl();
    }

    #[Groups(['user:item'])]
    public function getCompany(): ?string
    {
        return $this->profile?->getCompany();
    }

    #[Groups(['user:item'])]
    public function getPosition(): ?string
    {
        return $this->profile?->getPosition();
    }

    // ==================== SYMFONY SECURITY ====================

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        if (!in_array('ROLE_USER', $roles, true)) {
            $roles[] = 'ROLE_USER';
        }

        $this->roles = array_unique($roles);
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->roles, true);
    }

    public function addRole(string $role): self
    {
        if (!in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getEmailConfirmationToken(): ?string
    {
        return $this->emailConfirmationToken;
    }

    public function setEmailConfirmationToken(?string $emailConfirmationToken): self
    {
        $this->emailConfirmationToken = $emailConfirmationToken;
        return $this;
    }

    public function eraseCredentials(): void {}

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Vérifie si le profil est complet.
     */
    public function isComplete(): bool
    {
        return !empty($this->firstName)
            && !empty($this->lastName)
            && !empty($this->email)
            && $this->isEmailVerified;
    }

    /**
     * Vérifie si l'utilisateur est admin.
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->roles);
    }
}
