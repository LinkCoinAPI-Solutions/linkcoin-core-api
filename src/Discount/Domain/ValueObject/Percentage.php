<?php

namespace App\Discount\Domain\ValueObject;

use App\Discount\Domain\Exception\DiscountException;
use App\Shared\Domain\ValueObject\Money;

/**
 * Value Object représentant un pourcentage.
 * Encapsule la validation (0-100) et les calculs.
 * Immutable : ne peut pas être modifié après création.
 */
readonly class Percentage
{
    private const MIN_VALUE = 0.0;
    private const MAX_VALUE = 100.0;

    private function __construct(
        public float $value
    ) {}

    /**
     * Crée un pourcentage depuis un float.
     * 
     * @param float $value Valeur entre 0 et 100
     * @throws DiscountException Si hors limites
     */
    public static function fromFloat(float $value): self
    {
        self::validate($value);
        return new self($value);
    }

    /**
     * Crée un pourcentage de 0% (pour initialisation).
     */
    public static function zero(): self
    {
        return new self(0.0);
    }

    /**
     * Crée un pourcentage de 100%.
     */
    public static function full(): self
    {
        return new self(100.0);
    }

    /**
     * Valide le pourcentage.
     * Règles :
     * - Entre 0 et 100 inclus
     * - Fini (pas NaN, pas infini)
     * 
     * @throws DiscountException Si invalide
     */
    private static function validate(float $value): void
    {
        if ($value < self::MIN_VALUE || $value > self::MAX_VALUE) {
            throw DiscountException::invalidAmount(0, $value);
        }

        if (!is_finite($value)) {
            throw DiscountException::invalidAmount(0, $value);
        }
    }

    /**
     * Applique le pourcentage à un montant.
     * Retourne le montant de réduction (pas le reste).
     * 
     * @param Money $amount Montant sur lequel appliquer le pourcentage
     * @return Money Montant de réduction calculé
     */
    public function applyTo(Money $amount): Money
    {
        $discountAmount = $amount->amount * ($this->value / 100);
        return Money::fromFloat($discountAmount);
    }

    /**
     * Convertit en décimal (ex: 20% → 0.20).
     */
    public function toDecimal(): float
    {
        return $this->value / 100;
    }

    /**
     * Formate le pourcentage pour affichage.
     */
    public function format(): string
    {
        return sprintf('%.2f%%', $this->value);
    }

    /**
     * Vérifie si le pourcentage est nul.
     */
    public function isZero(): bool
    {
        return abs($this->value) < 0.01;
    }

    /**
     * Conversion en string pour affichage simple.
     */
    public function __toString(): string
    {
        return (string) $this->value;
    }
}
