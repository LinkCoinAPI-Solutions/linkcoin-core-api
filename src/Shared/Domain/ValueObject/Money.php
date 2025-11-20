<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\SharedKernelException;

/**
 * Value Object représentant un montant monétaire.
 * Encapsule la validation (positif, fini) et les opérations.
 * Immutable : toute opération retourne une nouvelle instance.
 */
readonly class Money
{
    private const MAX_AMOUNT = 999999.99;
    private const PRECISION = 2;

    private function __construct(
        public float $amount
    ) {}

    /**
     * Crée un montant depuis un float.
     * Arrondit automatiquement à 2 décimales.
     * 
     * @throws SharedKernelException Si montant invalide
     */
    public static function fromFloat(float $amount): self
    {
        $rounded = round($amount, self::PRECISION);
        self::validate($rounded);
        
        return new self($rounded);
    }

    /**
     * Crée un montant de zéro (pour initialisation).
     */
    public static function zero(): self
    {
        return new self(0.0);
    }

    /**
     * Valide le montant.
     * Règles :
     * - Positif ou nul
     * - Fini (pas NaN, pas infini)
     * - Inférieur au plafond de sécurité
     * 
     * @throws SharedKernelException Si invalide
     */
    private static function validate(float $amount): void
    {
        if ($amount < 0) {
            throw SharedKernelException::negativeAmount($amount);
        }

        if (!is_finite($amount)) {
            throw SharedKernelException::invalidAmount($amount);
        }

        if ($amount > self::MAX_AMOUNT) {
            throw SharedKernelException::amountTooHigh($amount, self::MAX_AMOUNT);
        }
    }

    /**
     * Soustrait un montant (pour calculer le reste après réduction).
     * 
     * @return self Nouveau Money avec le résultat
     */
    public function subtract(self $other): self
    {
        return self::fromFloat($this->amount - $other->amount);
    }

    /**
     * Retourne le montant minimum entre deux montants.
     * Utile pour plafonner une réduction.
     */
    public function min(self $other): self
    {
        return $this->amount <= $other->amount ? $this : $other;
    }

    /**
     * Compare avec un autre montant.
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->amount > $other->amount;
    }

    /**
     * Compare avec un autre montant.
     */
    public function isGreaterThanOrEqual(self $other): bool
    {
        return $this->amount >= $other->amount;
    }

    /**
     * Vérifie si le montant est zéro.
     */
    public function isZero(): bool
    {
        return abs($this->amount) < 0.01;
    }

    /**
     * Formate le montant pour affichage (2 décimales + €).
     */
    public function format(): string
    {
        return sprintf('%.2f€', $this->amount);
    }

    /**
     * Conversion en string pour affichage simple.
     */
    public function __toString(): string
    {
        return (string) $this->amount;
    }
}
