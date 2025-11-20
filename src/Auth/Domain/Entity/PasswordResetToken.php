<?php

namespace App\Auth\Domain\Entity;

use App\Account\Domain\Entity\User;
use App\Auth\Infrastructure\Repository\PasswordResetTokenRepository;
use App\Shared\Domain\ValueObject\SecureToken;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un token de réinitialisation de mot de passe.
 * Utilise SecureToken Value Object pour garantir la sécurité.
 * Suit le pattern : un token = une utilisation unique avec expiration.
 */
#[ORM\Entity(repositoryClass: PasswordResetTokenRepository::class)]
#[ORM\Table(name: 'password_reset_tokens')]
#[ORM\Index(columns: ['token'])]
#[ORM\Index(columns: ['user_id'])]
#[ORM\Index(columns: ['expires_at'])]
class PasswordResetToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Utilisateur associé au token.
     * Cascade DELETE : suppression du token si user supprimé.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Token de réinitialisation (64 caractères hex).
     */
    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private string $token;

    /**
     * Date de création du token.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * Date d'expiration du token.
     * Typiquement : createdAt + 1 heure.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $expiresAt;

    /**
     * Indique si le token a été utilisé.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $used = false;

    /**
     * Date d'utilisation du token.
     */
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    /**
     * Constructeur privé : utiliser les factory methods.
     */
    private function __construct(
        User $user,
        string $token,
        \DateTimeImmutable $expiresAt
    ) {
        $this->user = $user;
        $this->token = $token;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
        $this->used = false;
    }

    /**
     * Crée un nouveau token avec génération automatique.
     * 
     * @param User $user Utilisateur demandant la réinitialisation
     * @param int $validityInHours Durée de validité en heures (défaut: 1h)
     * @return self
     */
    public static function create(User $user, int $validityInHours = 1): self
    {
        $secureToken = SecureToken::generate();
        $expiresAt = new \DateTimeImmutable("+{$validityInHours} hours");

        return new self($user, $secureToken->value, $expiresAt);
    }

    /**
     * Crée un token depuis des valeurs existantes (hydratation Doctrine).
     * 
     * @internal Utilisé par Doctrine uniquement
     */
    public static function fromValues(
        User $user,
        string $token,
        \DateTimeImmutable $expiresAt
    ): self {
        return new self($user, $token, $expiresAt);
    }

    // ==================== GESTION DU TOKEN AVEC VALUE OBJECT ====================

    /**
     * Retourne le token comme Value Object.
     */
    public function getTokenAsValueObject(): SecureToken
    {
        return SecureToken::fromString($this->token);
    }

    /**
     * Retourne le token (pour Doctrine/API).
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Définit le token (pour hydratation Doctrine).
     * 
     * @internal Ne pas utiliser directement, utiliser create()
     */
    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    /**
     * Vérifie si le token correspond (timing-safe).
     */
    public function matchesToken(string $tokenToCheck): bool
    {
        return hash_equals($this->token, $tokenToCheck);
    }

    // ==================== GETTERS ET SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getExpiresAt(): \DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): self
    {
        $this->used = $used;
        return $this;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function setUsedAt(?\DateTimeImmutable $usedAt): self
    {
        $this->usedAt = $usedAt;
        return $this;
    }

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Marque le token comme utilisé.
     * Enregistre la date d'utilisation.
     * 
     * @throws \LogicException Si déjà utilisé
     */
    public function markAsUsed(): void
    {
        if ($this->used) {
            throw new \LogicException('Token has already been used');
        }

        if ($this->isExpired()) {
            throw new \LogicException('Cannot mark expired token as used');
        }

        $this->used = true;
        $this->usedAt = new \DateTimeImmutable();
    }

    /**
     * Vérifie si le token a expiré.
     */
    public function isExpired(): bool
    {
        return $this->expiresAt < new \DateTimeImmutable();
    }

    /**
     * Vérifie si le token est valide.
     * Valide = non expiré ET non utilisé.
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->used;
    }

    /**
     * Retourne le temps restant avant expiration.
     * 
     * @return \DateInterval|null Null si déjà expiré
     */
    public function getTimeUntilExpiration(): ?\DateInterval
    {
        if ($this->isExpired()) {
            return null;
        }

        $now = new \DateTimeImmutable();
        return $now->diff($this->expiresAt);
    }

    /**
     * Retourne le temps restant en secondes.
     * 
     * @return int 0 si déjà expiré
     */
    public function getSecondsUntilExpiration(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        $now = new \DateTimeImmutable();
        return $this->expiresAt->getTimestamp() - $now->getTimestamp();
    }

    /**
     * Prolonge la validité du token.
     * 
     * @param int $additionalHours Heures à ajouter
     * @throws \LogicException Si token déjà utilisé
     */
    public function extend(int $additionalHours): self
    {
        if ($this->used) {
            throw new \LogicException('Cannot extend used token');
        }

        $this->expiresAt = $this->expiresAt->modify("+{$additionalHours} hours");
        return $this;
    }

    /**
     * Invalide le token immédiatement.
     * Marque comme utilisé puis définit l'expiration à maintenant.
     */
    public function invalidate(): self
    {
        if (!$this->used) {
            $this->used = true;
            $this->usedAt = new \DateTimeImmutable();
        }

        // Puis expirer le token
        $this->expiresAt = new \DateTimeImmutable();

        return $this;
    }
}
