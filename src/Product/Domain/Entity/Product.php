<?php

namespace App\Product\Domain\Entity;

use App\Product\Domain\ValueObject\ProductType;
use App\Product\Domain\ValueObject\Stock;
use App\Product\Domain\ValueObject\Slug;
use App\Product\Infrastructure\Repository\ProductRepository;
use App\Subscription\Domain\Entity\SubscriptionPlan;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité représentant un produit.
 * Utilise des Value Objects pour garantir la cohérence des données.
 * Gère les produits API, packages de données et fonctionnalités premium.
 */
#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[ORM\Index(name: 'IDX_product_category', columns: ['category'])]
#[ORM\Index(name: 'IDX_product_available', columns: ['is_available'])]
#[ORM\Index(name: 'IDX_product_popular', columns: ['is_popular'])]
#[ORM\UniqueConstraint(name: 'UNIQ_product_slug', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * Nom du produit (affiché à l'utilisateur).
     */
    #[ORM\Column(type: 'string', length: 255)]
    private string $name;

    /**
     * Description détaillée du produit.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Catégorie du produit (ex: 'crypto', 'trading', 'analytics').
     */
    #[ORM\Column(type: 'string', length: 50)]
    private string $category;

    /**
     * Type de produit (Value Object Enum).
     */
    #[ORM\Column(type: 'string', length: 20, enumType: ProductType::class)]
    private ProductType $productType;

    /**
     * Métadonnées JSON spécifiques au produit.
     */
    #[ORM\Column(type: 'json')]
    private array $metadata = [];

    /**
     * Disponibilité du produit.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isAvailable = true;

    /**
     * Flag "produit populaire".
     */
    #[ORM\Column(type: 'boolean')]
    private bool $isPopular = false;

    /**
     * Stock disponible (nullable = illimité).
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $stock = null;

    /**
     * Slug du produit pour URLs SEO.
     */
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    private ?string $slug = null;

    /**
     * Date de création du produit.
     */
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification.
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $updatedAt = null;

    /**
     * Plans d'abonnement associés.
     * 
     * @var Collection<int, SubscriptionPlan>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: SubscriptionPlan::class, cascade: ['persist', 'remove'])]
    private Collection $subscriptionPlans;

    /**
     * Caractéristiques du produit.
     * 
     * @var Collection<int, ProductFeature>
     */
    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductFeature::class, cascade: ['persist', 'remove'])]
    private Collection $productFeatures;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->productType = ProductType::API_ACCESS;
        $this->subscriptionPlans = new ArrayCollection();
        $this->productFeatures = new ArrayCollection();
    }

    /**
     * Hook Doctrine : met à jour automatiquement updatedAt et génère le slug si absent.
     */
    #[ORM\PreUpdate]
    #[ORM\PrePersist]
    public function updateTimestamps(): void
    {
        $this->updatedAt = new \DateTime();

        // Génère automatiquement le slug depuis le nom si absent
        if ($this->slug === null && !empty($this->name)) {
            $this->setSlugFromValueObject(Slug::fromProductName($this->name));
        }
    }

    // ==================== MÉTHODES MÉTIER ====================

    /**
     * Vérifie si le produit est un accès API.
     */
    public function isApiProduct(): bool
    {
        return $this->productType === ProductType::API_ACCESS;
    }

    /**
     * Retourne les endpoints API disponibles.
     * 
     * @return string[]
     */
    public function getApiEndpoints(): array
    {
        return $this->metadata['api_endpoints'] ?? [];
    }

    /**
     * Retourne les exchanges crypto supportés.
     * 
     * @return string[]
     */
    public function getCryptoExchanges(): array
    {
        return $this->metadata['supported_exchanges'] ?? [];
    }

    // ==================== GESTION DU STOCK AVEC VALUE OBJECT ====================

    /**
     * Retourne le stock comme Value Object.
     */
    public function getStockAsValueObject(): Stock
    {
        return Stock::fromNullable($this->stock);
    }

    /**
     * Définit le stock via Value Object.
     */
    public function setStockFromValueObject(Stock $stock): self
    {
        $this->stock = $stock->toDatabase();
        return $this;
    }

    /**
     * Retourne la quantité de stock (pour Doctrine/API).
     */
    public function getStock(): ?int
    {
        return $this->stock;
    }

    /**
     * Définit la quantité de stock (pour hydratation Doctrine).
     */
    public function setStock(?int $stock): self
    {
        $this->stock = $stock;
        return $this;
    }

    /**
     * Vérifie si le produit a un stock illimité.
     */
    public function hasUnlimitedStock(): bool
    {
        return $this->getStockAsValueObject()->isUnlimited();
    }

    /**
     * Vérifie si le produit est en stock.
     */
    public function isInStock(): bool
    {
        return $this->getStockAsValueObject()->isAvailable();
    }

    /**
     * Vérifie si le produit est en rupture de stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->getStockAsValueObject()->isOutOfStock();
    }

    /**
     * Décrémente le stock via Value Object.
     * 
     * @throws \InvalidArgumentException Si stock insuffisant
     */
    public function decrementStock(int $quantity = 1): self
    {
        $newStock = $this->getStockAsValueObject()->decrement($quantity);
        $this->setStockFromValueObject($newStock);
        return $this;
    }

    /**
     * Incrémente le stock via Value Object.
     */
    public function incrementStock(int $quantity = 1): self
    {
        $newStock = $this->getStockAsValueObject()->increment($quantity);
        $this->setStockFromValueObject($newStock);
        return $this;
    }

    // ==================== GESTION DU SLUG AVEC VALUE OBJECT ====================

    /**
     * Retourne le slug comme Value Object.
     */
    public function getSlugAsValueObject(): ?Slug
    {
        return $this->slug ? Slug::fromString($this->slug) : null;
    }

    /**
     * Définit le slug via Value Object.
     */
    public function setSlugFromValueObject(Slug $slug): self
    {
        $this->slug = $slug->value;
        return $this;
    }

    /**
     * Retourne le slug (pour Doctrine/API).
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Définit le slug (pour hydratation Doctrine).
     */
    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Génère automatiquement le slug depuis le nom.
     */
    public function generateSlug(): self
    {
        if (!empty($this->name)) {
            $this->setSlugFromValueObject(Slug::fromProductName($this->name));
        }
        return $this;
    }

    // ==================== GESTION DU TYPE AVEC ENUM ====================

    /**
     * Retourne le type de produit (pour usage métier).
     */
    public function getProductType(): ProductType
    {
        return $this->productType;
    }

    /**
     * Retourne le type comme string (pour Doctrine/API).
     */
    public function getProductTypeValue(): string
    {
        return $this->productType->value;
    }

    /**
     * Définit le type via enum.
     */
    public function setProductType(ProductType|string $type): self
    {
        if (is_string($type)) {
            $this->productType = ProductType::from($type);
        } else {
            $this->productType = $type;
        }
        return $this;
    }

    /**
     * Retourne le label du type pour affichage.
     */
    public function getProductTypeLabel(): string
    {
        return $this->productType->getLabel();
    }

    // ==================== GESTION DES CARACTÉRISTIQUES ====================

    /**
     * @return Collection<int, ProductFeature>
     */
    public function getProductFeatures(): Collection
    {
        return $this->productFeatures;
    }

    /**
     * @return Collection<int, ProductFeature>
     */
    public function getActiveFeatures(): Collection
    {
        return $this->productFeatures->filter(
            fn(ProductFeature $feature) => $feature->isActive()
        );
    }

    /**
     * Vérifie si le produit possède une caractéristique active.
     */
    public function hasFeature(string $featureKey): bool
    {
        /** @var ProductFeature $feature */
        foreach ($this->productFeatures as $feature) {
            if ($feature->getFeatureKey() === $featureKey && $feature->isActive()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Récupère une caractéristique par sa clé.
     */
    public function getFeature(string $featureKey): ?ProductFeature
    {
        /** @var ProductFeature $feature */
        foreach ($this->productFeatures as $feature) {
            if ($feature->getFeatureKey() === $featureKey && $feature->isActive()) {
                return $feature;
            }
        }
        return null;
    }

    /**
     * Ajoute une caractéristique.
     */
    public function addProductFeature(ProductFeature $feature): self
    {
        if (!$this->productFeatures->contains($feature)) {
            $this->productFeatures->add($feature);
            $feature->setProduct($this);
        }
        return $this;
    }

    /**
     * Retire une caractéristique.
     */
    public function removeProductFeature(ProductFeature $feature): self
    {
        $this->productFeatures->removeElement($feature);
        return $this;
    }

    // ==================== GETTERS ET SETTERS ====================

    public function getId(): ?int
    {
        return $this->id;
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

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): self
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): self
    {
        $this->isAvailable = $isAvailable;
        return $this;
    }

    public function isPopular(): bool
    {
        return $this->isPopular;
    }

    public function setIsPopular(bool $isPopular): self
    {
        $this->isPopular = $isPopular;
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

    /**
     * @return Collection<int, SubscriptionPlan>
     */
    public function getSubscriptionPlans(): Collection
    {
        return $this->subscriptionPlans;
    }

    public function addSubscriptionPlan(SubscriptionPlan $plan): self
    {
        if (!$this->subscriptionPlans->contains($plan)) {
            $this->subscriptionPlans->add($plan);
            $plan->setProduct($this);
        }
        return $this;
    }

    public function removeSubscriptionPlan(SubscriptionPlan $plan): self
    {
        $this->subscriptionPlans->removeElement($plan);
        return $this;
    }
}
