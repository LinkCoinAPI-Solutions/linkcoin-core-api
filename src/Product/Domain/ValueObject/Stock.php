<?php

namespace App\Product\Domain\ValueObject;

/**
 * Value Object représentant le stock d'un produit.
 * Encapsule la logique de stock limité vs illimité.
 * Immutable : toute opération retourne une nouvelle instance.
 */
readonly class Stock
{
    private function __construct(
        public ?int $quantity
    ) {}

    /**
     * Crée un stock avec quantité limitée.
     * 
     * @throws \InvalidArgumentException Si quantité négative
     */
    public static function limited(int $quantity): self
    {
        if ($quantity < 0) {
            throw new \InvalidArgumentException('Stock quantity cannot be negative');
        }
        
        return new self($quantity);
    }

    /**
     * Crée un stock illimité (produits digitaux).
     */
    public static function unlimited(): self
    {
        return new self(null);
    }

    /**
     * Crée un stock depuis une valeur nullable.
     * null = illimité, int = limité.
     */
    public static function fromNullable(?int $quantity): self
    {
        return $quantity === null ? self::unlimited() : self::limited($quantity);
    }

    /**
     * Vérifie si le stock est illimité.
     */
    public function isUnlimited(): bool
    {
        return $this->quantity === null;
    }

    /**
     * Vérifie si le produit est en stock.
     */
    public function isAvailable(): bool
    {
        return $this->isUnlimited() || $this->quantity > 0;
    }

    /**
     * Vérifie si le produit est en rupture de stock.
     */
    public function isOutOfStock(): bool
    {
        return !$this->isUnlimited() && $this->quantity <= 0;
    }

    /**
     * Décrémente le stock.
     * Retourne une nouvelle instance (immutabilité).
     * 
     * @throws \InvalidArgumentException Si stock insuffisant
     */
    public function decrement(int $amount = 1): self
    {
        if ($this->isUnlimited()) {
            return $this;
        }

        if ($this->quantity < $amount) {
            throw new \InvalidArgumentException(
                sprintf('Insufficient stock: %d available, %d requested', $this->quantity, $amount)
            );
        }

        return new self($this->quantity - $amount);
    }

    /**
     * Incrémente le stock.
     * Retourne une nouvelle instance (immutabilité).
     */
    public function increment(int $amount = 1): self
    {
        if ($this->isUnlimited()) {
            return $this;
        }

        return new self($this->quantity + $amount);
    }

    /**
     * Retourne la quantité pour stockage en DB.
     */
    public function toDatabase(): ?int
    {
        return $this->quantity;
    }

    /**
     * Conversion en string pour affichage.
     */
    public function __toString(): string
    {
        return $this->isUnlimited() ? 'Illimité' : (string) $this->quantity;
    }
}
