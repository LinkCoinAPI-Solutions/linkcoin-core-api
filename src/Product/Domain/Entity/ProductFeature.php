<?php

namespace App\Product\Domain\Entity;

use App\Product\Domain\ValueObject\FeatureType;
use App\Product\Infrastructure\Repository\ProductFeatureRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant une caractéristique/fonctionnalité d'un produit.
 * Utilise FeatureType enum pour garantir la cohérence des tiers.
 */
#[ORM\Entity(repositoryClass: ProductFeatureRepository::class)]
#[ORM\Table(name: 'product_features')]
#[ORM\Index(name: 'IDX_product_feature_product', columns: ['product_id'])]
#[ORM\Index(name: 'IDX_product_feature_active', columns: ['is_active'])]
#[ORM\UniqueConstraint(name: 'UNIQ_product_feature_key', columns: ['product_id', 'feature_key'])]
#[ORM\HasLifecycleCallbacks]
class ProductFeature
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Produit auquel appartient cette caractéristique.
     */
    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'productFeatures')]
    #[ORM\JoinColumn(name: 'product_id', nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    /**
     * Clé unique de la feature par produit.
     */
    #[ORM\Column(type: 'string', length: 100)]
    private string $featureKey;

    /**
     * Nom affiché à l'utilisateur.
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /**
     * Description détaillée.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Type/tier de la feature (Value Object Enum).
     */
    #[ORM\Column(type: 'string', length: 20, enumType: FeatureType::class)]
    private FeatureType $featureType;

    /**
     * Configuration JSON spécifique.
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $configuration = null;

    /**
     * Activation de la feature.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    /**
     * Ordre d'affichage.
     */
    #[ORM\Column(type: 'integer')]
    private int $sortOrder = 0;

    /**
     * Date de création.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->featureType = FeatureType::STANDARD;
    }

    /**
     * Hook Doctrine : met à jour automatiquement updatedAt.
     */
    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTime();
    }

    // ==================== MÉTHODES MÉTIER AVEC VALUE OBJECT ====================

    /**
     * Vérifie si la feature est premium ou enterprise.
     */
    public function isPremium(): bool
    {
        return $this->featureType->requiresPaidSubscription();
    }

    /**
     * Vérifie si la feature est uniquement enterprise.
     */
    public function isEnterprise(): bool
    {
        return $this->featureType === FeatureType::ENTERPRISE;
    }

    /**
     * Vérifie si cette feature est accessible avec un certain tier.
     */
    public function isAccessibleWith(FeatureType $userTier): bool
    {
        return $userTier->isAtLeast($this->featureType);
    }

    /**
     * Récupère une valeur de configuration par clé.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->configuration[$key] ?? $default;
    }

    // ==================== GESTION DU TYPE AVEC ENUM ====================

    /**
     * Retourne le type de feature (pour usage métier).
     */
    public function getFeatureType(): FeatureType
    {
        return $this->featureType;
    }

    /**
     * Retourne le type comme string (pour Doctrine/API).
     */
    public function getFeatureTypeValue(): string
    {
        return $this->featureType->value;
    }

    /**
     * Définit le type via enum.
     */
    public function setFeatureType(FeatureType|string $type): self
    {
        if (is_string($type)) {
            $this->featureType = FeatureType::from($type);
        } else {
            $this->featureType = $type;
        }
        return $this;
    }

    /**
     * Retourne le label du type pour affichage.
     */
    public function getFeatureTypeLabel(): string
    {
        return $this->featureType->getLabel();
    }

    /**
     * Retourne le niveau de tier (pour comparaisons).
     */
    public function getTierLevel(): int
    {
        return $this->featureType->getTierLevel();
    }

    // ==================== GETTERS ET SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): self
    {
        $this->product = $product;
        return $this;
    }

    public function getFeatureKey(): string
    {
        return $this->featureKey;
    }

    public function setFeatureKey(string $featureKey): self
    {
        $this->featureKey = $featureKey;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getConfiguration(): ?array
    {
        return $this->configuration;
    }

    public function setConfiguration(?array $configuration): self
    {
        $this->configuration = $configuration;
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

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
