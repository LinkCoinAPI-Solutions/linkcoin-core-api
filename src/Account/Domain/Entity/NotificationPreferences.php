<?php

namespace App\Account\Domain\Entity;

use App\Account\Infrastructure\Repository\NotificationPreferencesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Entité représentant les préférences de notification d'un utilisateur.
 * Gère les canaux de notification (email, push) et types (alerts, newsletter).
 */
#[ORM\Entity(repositoryClass: NotificationPreferencesRepository::class)]
#[ORM\Table(name: 'notification_preferences')]
#[ORM\HasLifecycleCallbacks]
class NotificationPreferences
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['notification:read'])]
    private ?int $id = null;

    /**
     * Utilisateur propriétaire des préférences (relation 1:1).
     */
    #[ORM\OneToOne(inversedBy: 'notificationPreferences', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['notification:read'])]
    private ?User $user = null;

    /**
     * Activer les notifications par email (général).
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['notification:read', 'notification:write'])]
    private bool $emailNotifications = true;

    /**
     * Activer les alertes de sécurité par email.
     * Recommandé : toujours activé pour la sécurité du compte.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['notification:read', 'notification:write'])]
    private bool $emailSecurityAlerts = true;

    /**
     * Activer les notifications push.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['notification:read', 'notification:write'])]
    private bool $pushNotifications = false;

    /**
     * Activer la newsletter.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['notification:read', 'notification:write'])]
    private bool $newsletter = false;

    /**
     * Date de création des préférences.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['notification:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['notification:read'])]
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

    // AJOUT: Getters get*() pour compatibilité avec l'Updater
    public function getEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function isEmailNotifications(): bool
    {
        return $this->emailNotifications;
    }

    public function setEmailNotifications(?bool $emailNotifications): self
    {
        if ($emailNotifications !== null) {
            $this->emailNotifications = $emailNotifications;
        }
        return $this;
    }

    public function getEmailSecurityAlerts(): bool
    {
        return $this->emailSecurityAlerts;
    }

    public function isEmailSecurityAlerts(): bool
    {
        return $this->emailSecurityAlerts;
    }

    public function setEmailSecurityAlerts(?bool $emailSecurityAlerts): self
    {
        if ($emailSecurityAlerts !== null) {
            $this->emailSecurityAlerts = $emailSecurityAlerts;
        }
        return $this;
    }

    public function getPushNotifications(): bool
    {
        return $this->pushNotifications;
    }

    public function isPushNotifications(): bool
    {
        return $this->pushNotifications;
    }

    public function setPushNotifications(?bool $pushNotifications): self
    {
        if ($pushNotifications !== null) {
            $this->pushNotifications = $pushNotifications;
        }
        return $this;
    }

    public function getNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function isNewsletter(): bool
    {
        return $this->newsletter;
    }

    public function setNewsletter(?bool $newsletter): self
    {
        if ($newsletter !== null) {
            $this->newsletter = $newsletter;
        }
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
     * Vérifie si toutes les notifications sont désactivées.
     */
    #[Groups(['notification:read'])]
    public function hasAllNotificationsDisabled(): bool
    {
        return !$this->emailNotifications
            && !$this->pushNotifications
            && !$this->emailSecurityAlerts
            && !$this->newsletter;
    }

    /**
     * Vérifie si au moins une notification est activée.
     */
    #[Groups(['notification:read'])]
    public function hasAnyNotificationEnabled(): bool
    {
        return $this->emailNotifications
            || $this->pushNotifications
            || $this->emailSecurityAlerts
            || $this->newsletter;
    }

    /**
     * Active toutes les notifications.
     */
    public function enableAll(): self
    {
        $this->emailNotifications = true;
        $this->emailSecurityAlerts = true;
        $this->pushNotifications = true;
        $this->newsletter = true;
        return $this;
    }

    /**
     * Désactive toutes les notifications (sauf security alerts).
     */
    public function disableAll(): self
    {
        $this->emailNotifications = false;
        $this->pushNotifications = false;
        $this->newsletter = false;
        // Security alerts restent actives par sécurité
        return $this;
    }

    /**
     * Active uniquement les notifications essentielles.
     */
    public function enableEssentialOnly(): self
    {
        $this->emailNotifications = true;
        $this->emailSecurityAlerts = true;
        $this->pushNotifications = false;
        $this->newsletter = false;
        return $this;
    }
}
