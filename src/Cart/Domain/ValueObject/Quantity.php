<?php

namespace App\Cart\Domain\ValueObject;

use App\Cart\Domain\Exception\CartItemException;

/**
 * Value Object représentant une quantité d'article.
 * Garantit qu'une quantité est toujours entre 1 et 100.
 * Immutable : toute modification retourne une nouvelle instance.
 */
readonly class Quantity
{
    private const MIN_VALUE = 1;
    private const MAX_VALUE = 100;

    private function __construct(
        public int $value
    ) {}

    /**
     * Crée une quantité depuis un entier.
     * 
     * @throws CartItemException Si hors limites (1-100)
     */
    public static function fromInt(int $quantity): self
    {
        if ($quantity < self::MIN_VALUE || $quantity > self::MAX_VALUE) {
            throw CartItemException::invalidQuantityWithLimits(
                $quantity,
                self::MIN_VALUE,
                self::MAX_VALUE
            );
        }
        
        return new self($quantity);
    }

    /**
     * Augmente la quantité et retourne une nouvelle instance.
     * Garantit que le résultat ne dépasse pas 100.
     * 
     * @throws CartItemException Si le résultat dépasse 100
     */
    public function increase(int $amount): self
    {
        return self::fromInt($this->value + $amount);
    }

    /**
     * Diminue la quantité et retourne une nouvelle instance.
     * Garantit que le résultat ne descend pas sous 1.
     * 
     * @throws CartItemException Si le résultat est inférieur à 1
     */
    public function decrease(int $amount): self
    {
        return self::fromInt($this->value - $amount);
    }

    /**
     * Vérifie si la quantité est au minimum (1).
     */
    public function isMin(): bool
    {
        return $this->value === self::MIN_VALUE;
    }

    /**
     * Vérifie si la quantité est au maximum (100).
     */
    public function isMax(): bool
    {
        return $this->value === self::MAX_VALUE;
    }

    /**
     * Retourne la quantité minimale (1).
     */
    public static function min(): self
    {
        return new self(self::MIN_VALUE);
    }

    /**
     * Retourne la quantité maximale (100).
     */
    public static function max(): self
    {
        return new self(self::MAX_VALUE);
    }
}
