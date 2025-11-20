<?php

namespace App\Discount\Domain\Entity;

use App\Discount\Domain\ValueObject\DiscountCode;
use App\Discount\Domain\ValueObject\DiscountType;
use App\Shared\Domain\ValueObject\Money;
use App\Discount\Domain\ValueObject\Percentage;
use App\Discount\Infrastructure\Repository\DiscountRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité représentant une réduction (code promo).
 * Gère les réductions fixes et en pourcentage avec règles de validation.
 * Utilise des Value Objects pour garantir la cohérence des données.
 */
#[ORM\Entity(repositoryClass: DiscountRepository::class)]
#[ORM\Table(name: 'discounts')]
#[ORM\HasLifecycleCallbacks]
#[ORM\Index(columns: ['code'], name: 'idx_discount_code')]
#[ORM\Index(columns: ['is_active'], name: 'idx_discount_active')]
#[ORM\Index(columns: ['valid_from'], name: 'idx_discount_valid_from')]
#[ORM\Index(columns: ['valid_until'], name: 'idx_discount_valid_until')]
#[UniqueEntity(fields: ['code'], message: 'This discount code already exists')]
class Discount
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['discount:read', 'cart:read'])]
    private ?int $id = null;

    /**
     * Code de la réduction (ex: PROMO2024).
     * Stocké en majuscules pour unicité insensible à la casse.
     */
    #[ORM\Column(type: Types::STRING, length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Discount code is required')]
    #[Assert\Length(min: 3, max: 50)]
    #[Groups(['discount:read', 'discount:write', 'cart:read'])]
    private ?string $code = null;

    /**
     * Description de la réduction (affichée à l'utilisateur).
     */
    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Assert\NotBlank(message: 'Description is required')]
    #[Assert\Length(min: 5, max: 255)]
    #[Groups(['discount:read', 'discount:write', 'cart:read'])]
    private string $description;

    /**
     * Type de réduction : 'fixed' ou 'percentage'.
     * Utilise DiscountType enum pour type safety.
     */
    #[ORM\Column(type: Types::STRING, length: 20, enumType: DiscountType::class)]
    #[Groups(['discount:read', 'discount:write', 'cart:read'])]
    private DiscountType $type;

    /**
     * Valeur de la réduction.
     * - Si fixed : montant en euros (ex: 10.00)
     * - Si percentage : pourcentage (ex: 20.00 pour 20%)
     * Stocké en DECIMAL pour précision.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Groups(['discount:read', 'discount:write', 'cart:read'])]
    private string $value = '0.00';

    /**
     * Montant minimum du panier requis (optionnel).
     * Null = pas de minimum.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\PositiveOrZero]
    #[Groups(['discount:read', 'discount:write'])]
    private ?string $minimumAmount = null;

    /**
     * Plafond de réduction (optionnel).
     * Limite le montant maximal de réduction applicable.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    #[Assert\Positive]
    #[Groups(['discount:read', 'discount:write'])]
    private ?string $maximumDiscount = null;

    /**
     * Nombre maximum d'utilisations (optionnel).
     * Null = illimité.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    #[Assert\Positive]
    #[Groups(['discount:read', 'discount:write'])]
    private ?int $usageLimit = null;

    /**
     * Compteur d'utilisations actuel.
     * Incrémenté/décrémenté par DiscountUsageManager.
     */
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(['discount:read'])]
    private int $usageCount = 0;

    /**
     * Date de début de validité (optionnel).
     * Null = valide dès maintenant.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['discount:read', 'discount:write'])]
    private ?\DateTimeImmutable $validFrom = null;

    /**
     * Date de fin de validité (optionnel).
     * Null = pas d'expiration.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['discount:read', 'discount:write'])]
    private ?\DateTimeImmutable $validUntil = null;

    /**
     * Statut d'activation.
     * False = désactivé manuellement (indépendamment des dates).
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    #[Groups(['discount:read', 'discount:write', 'cart:read'])]
    private bool $isActive = true;

    /**
     * Date de création (audit).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['discount:read'])]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification (audit).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Groups(['discount:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->type = DiscountType::PERCENTAGE;
    }

    /**
     * Hook Doctrine : met à jour automatiquement updatedAt.
     */
    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // ==================== GETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Retourne le type de réduction.
     * Pour Doctrine : retourne la valeur enum.
     */
    public function getType(): string
    {
        return $this->type->value;
    }

    /**
     * Retourne le type comme enum.
     * Utile pour la logique métier avec type safety.
     */
    public function getTypeEnum(): DiscountType
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Retourne la valeur comme Money VO.
     */
    public function getValueAsMoney(): Money
    {
        return Money::fromFloat((float) $this->value);
    }

    /**
     * Retourne la valeur comme Percentage VO (si type percentage).
     * 
     * @throws \InvalidArgumentException Si le type n'est pas percentage
     */
    public function getValueAsPercentage(): Percentage
    {
        if ($this->type !== DiscountType::PERCENTAGE) {
            throw new \InvalidArgumentException('Cannot get percentage value for non-percentage discount');
        }
        
        return Percentage::fromFloat((float) $this->value);
    }

    public function getMinimumAmount(): ?string
    {
        return $this->minimumAmount;
    }

    /**
     * Retourne le montant minimum comme Money VO.
     */
    public function getMinimumAmountAsMoney(): ?Money
    {
        return $this->minimumAmount ? Money::fromFloat((float) $this->minimumAmount) : null;
    }

    public function getMaximumDiscount(): ?string
    {
        return $this->maximumDiscount;
    }

    /**
     * Retourne le plafond de réduction comme Money VO.
     */
    public function getMaximumDiscountAsMoney(): ?Money
    {
        return $this->maximumDiscount ? Money::fromFloat((float) $this->maximumDiscount) : null;
    }

    public function getUsageLimit(): ?int
    {
        return $this->usageLimit;
    }

    public function getUsageCount(): int
    {
        return $this->usageCount;
    }

    public function getValidFrom(): ?\DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function getValidUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    // ==================== SETTERS ====================

    /**
     * Définit le code de réduction.
     * Normalise automatiquement en majuscules.
     * Utilise DiscountCode VO pour validation.
     */
    public function setCode(?string $code): static
    {
        if ($code === null) {
            $this->code = null;
            return $this;
        }

        // Validation via Value Object
        $discountCode = DiscountCode::fromString($code);
        $this->code = $discountCode->value;
        
        return $this;
    }

    public function setDescription(string $description): static
    {
        $this->description = trim($description);
        return $this;
    }

    /**
     * Définit le type de réduction.
     * Accepte string ou DiscountType enum.
     */
    public function setType(string|DiscountType $type): static
    {
        if (is_string($type)) {
            $this->type = DiscountType::from($type);
        } else {
            $this->type = $type;
        }
        
        return $this;
    }

    /**
     * Définit la valeur de la réduction.
     * Valide selon le type (percentage ≤ 100).
     */
    public function setValue(string|float $value): static
    {
        $floatValue = is_string($value) ? (float) $value : $value;

        if ($floatValue <= 0) {
            throw new \InvalidArgumentException('Discount value must be positive');
        }

        // Validation spécifique au pourcentage
        if ($this->type === DiscountType::PERCENTAGE && $floatValue > 100) {
            throw new \InvalidArgumentException('Percentage discount cannot exceed 100%');
        }

        $this->value = number_format($floatValue, 2, '.', '');
        return $this;
    }

    public function setMinimumAmount(?string $minimumAmount): static
    {
        $this->minimumAmount = $minimumAmount;
        return $this;
    }

    public function setMaximumDiscount(?string $maximumDiscount): static
    {
        $this->maximumDiscount = $maximumDiscount;
        return $this;
    }

    public function setUsageLimit(?int $usageLimit): static
    {
        if ($usageLimit !== null && $usageLimit < 0) {
            throw new \InvalidArgumentException('Usage limit cannot be negative');
        }
        
        $this->usageLimit = $usageLimit;
        return $this;
    }

    public function setValidFrom(?\DateTimeImmutable $validFrom): static
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function setValidUntil(?\DateTimeImmutable $validUntil): static
    {
        $this->validUntil = $validUntil;
        return $this;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Vérifie si la réduction est actuellement valide et utilisable.
     * 
     * Règles :
     * - Doit être active
     * - Dans sa période de validité
     * - Avec des usages restants disponibles
     */
    #[Groups(['discount:read', 'cart:read'])]
    public function isCurrentlyValid(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        $now = new \DateTimeImmutable();

        // Vérifie la date de début
        if ($this->validFrom && $now < $this->validFrom) {
            return false;
        }

        // Vérifie la date de fin
        if ($this->validUntil && $now > $this->validUntil) {
            return false;
        }

        // Vérifie la limite d'utilisation
        if ($this->usageLimit && $this->usageCount >= $this->usageLimit) {
            return false;
        }

        return true;
    }

    /**
     * Vérifie si la réduction peut s'appliquer à un montant donné.
     * 
     * @param float|string|Money $amount Montant du panier
     */
    public function canApplyToAmount(float|string|Money $amount): bool
    {
        if (!$this->isCurrentlyValid()) {
            return false;
        }

        // Conversion en Money pour comparaison
        $cartAmount = $amount instanceof Money 
            ? $amount 
            : Money::fromFloat(is_string($amount) ? (float) $amount : $amount);

        $minimumRequired = $this->getMinimumAmountAsMoney();
        
        if ($minimumRequired && !$cartAmount->isGreaterThanOrEqual($minimumRequired)) {
            return false;
        }

        return true;
    }

    /**
     * Calcule le montant de réduction pour un prix donné.
     * Applique automatiquement les plafonds (maximum et prix).
     * 
     * @param float|string|Money $price Prix du panier
     * @return Money Montant de réduction calculé
     */
    public function calculateDiscountAmount(float|string|Money $price): Money
    {
        // Conversion en Money
        $cartTotal = $price instanceof Money 
            ? $price 
            : Money::fromFloat(is_string($price) ? (float) $price : $price);

        if (!$this->canApplyToAmount($cartTotal)) {
            return Money::zero();
        }

        // Calcul selon le type
        $discountAmount = match ($this->type) {
            DiscountType::FIXED => $this->getValueAsMoney(),
            DiscountType::PERCENTAGE => $this->getValueAsPercentage()->applyTo($cartTotal),
            DiscountType::NONE => Money::zero(),
        };

        // Application du plafond maximum
        $maximumDiscount = $this->getMaximumDiscountAsMoney();
        if ($maximumDiscount) {
            $discountAmount = $discountAmount->min($maximumDiscount);
        }

        // La réduction ne peut pas dépasser le prix
        $discountAmount = $discountAmount->min($cartTotal);

        return $discountAmount;
    }

    /**
     * Retourne le pourcentage effectif de réduction pour un prix donné.
     * Utile pour l'affichage, même pour les réductions fixes.
     */
    #[Groups(['discount:read', 'cart:read'])]
    public function getDiscountPercentage(string $originalPrice = '100.00'): string
    {
        if ($this->type === DiscountType::PERCENTAGE) {
            return $this->value;
        }

        $price = Money::fromFloat((float) $originalPrice);
        
        if ($price->isZero()) {
            return '0.00';
        }

        $discountAmount = $this->getValueAsMoney();
        $percentage = ($discountAmount->amount / $price->amount) * 100;

        return number_format($percentage, 2, '.', '');
    }

    /**
     * Vérifie s'il reste des usages disponibles.
     * True si pas de limite ou si limite non atteinte.
     */
    #[Groups(['discount:read'])]
    public function hasUsagesLeft(): bool
    {
        if (!$this->usageLimit) {
            return true; // Illimité
        }

        return $this->usageCount < $this->usageLimit;
    }

    /**
     * Active la réduction.
     */
    public function activate(): static
    {
        $this->isActive = true;
        return $this;
    }

    /**
     * Désactive la réduction.
     */
    public function deactivate(): static
    {
        $this->isActive = false;
        return $this;
    }

    /**
     * Vérifie si la réduction a expiré.
     * False si pas de date de fin.
     */
    #[Groups(['discount:read'])]
    public function isExpired(): bool
    {
        if (!$this->validUntil) {
            return false;
        }

        return new \DateTimeImmutable() > $this->validUntil;
    }

    /**
     * Retourne la valeur formatée pour affichage (10€ ou 20%).
     */
    #[Groups(['discount:read', 'cart:read'])]
    public function getFormattedValue(): string
    {
        return $this->type === DiscountType::PERCENTAGE
            ? $this->value . '%'
            : $this->value . '€';
    }

    /**
     * Incrémente le compteur d'usage.
     * Appelé par DiscountUsageManager lors de l'application.
     */
    public function incrementUsage(): void
    {
        $this->usageCount++;
    }

    /**
     * Décrémente le compteur d'usage.
     * Appelé par DiscountUsageManager lors du retrait.
     * Ne descend jamais en dessous de 0.
     */
    public function decrementUsage(): void
    {
        if ($this->usageCount > 0) {
            $this->usageCount--;
        }
    }

    /**
     * Retourne le nombre d'usages restants.
     * Null si illimité.
     */
    #[Groups(['discount:read'])]
    public function getRemainingUsages(): ?int
    {
        if ($this->usageLimit === null) {
            return null; // Illimité
        }

        return max(0, $this->usageLimit - $this->usageCount);
    }

    /**
     * Représentation string (pour debug/log).
     */
    public function __toString(): string
    {
        return $this->code ?? 'N/A';
    }
}
