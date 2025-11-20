<?php

namespace App\Discount\Domain\Exception;

/**
 * Exception pour les erreurs de validation métier des réductions.
 * Peut agréger plusieurs erreurs de validation en une seule exception.
 * Code HTTP 422 (Unprocessable Entity) pour toutes les validations.
 */
final class DiscountValidationException extends DiscountException
{
    private array $validationErrors;

    public function __construct(
        array $validationErrors, 
        ?string $message = null, 
        array $additionalContext = []
    ) {
        if (empty($validationErrors)) {
            throw new \InvalidArgumentException('Validation errors cannot be empty');
        }

        $this->validationErrors = $this->normalizeValidationErrors($validationErrors);
        $message = $message ?? $this->generateValidationMessage();

        parent::__construct(
            $message,
            self::ERROR_VALIDATION_FAILED,
            array_merge($additionalContext, ['validation_errors' => $this->validationErrors]),
            422
        );
    }

    /**
     * Titre pour les erreurs de validation.
     */
    public function getTitle(): ?string
    {
        return 'Discount Validation Error';
    }

    /**
     * Retourne toutes les erreurs de validation.
     */
    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Vérifie si des erreurs de validation existent.
     */
    public function hasValidationErrors(): bool
    {
        return !empty($this->validationErrors);
    }

    /**
     * Compte le nombre d'erreurs de validation.
     */
    public function getValidationErrorsCount(): int
    {
        return count($this->validationErrors);
    }

    /**
     * Crée une exception avec plusieurs erreurs de validation.
     */
    public static function fromErrors(array $validationErrors, array $context = []): self
    {
        if (empty($validationErrors)) {
            $validationErrors = ['Unknown discount validation error'];
        }

        return new self($validationErrors, null, $context);
    }

    /**
     * Crée une exception avec une seule erreur de validation.
     */
    public static function singleError(string $validationError, array $context = []): self
    {
        if (trim($validationError) === '') {
            throw new \InvalidArgumentException('Validation error cannot be empty');
        }

        return new self([$validationError], $validationError, $context);
    }

    /**
     * Réduction déjà appliquée au panier.
     */
    public static function discountAlreadyApplied(string $discountCode): self
    {
        if (trim($discountCode) === '') {
            throw new \InvalidArgumentException('Discount code cannot be empty');
        }

        return self::singleError(
            sprintf('Discount code "%s" is already applied to this cart', $discountCode),
            ['discount_code' => $discountCode]
        );
    }

    /**
     * Montant minimum non atteint.
     */
    public static function minimumAmountNotMet(float $requiredAmount, float $currentAmount): self
    {
        if ($requiredAmount < 0 || $currentAmount < 0) {
            throw new \InvalidArgumentException('Amounts cannot be negative');
        }

        return self::singleError(
            sprintf(
                'Minimum amount requirement not met: %.2f€ required (%.2f€ current)',
                $requiredAmount,
                $currentAmount
            ),
            [
                'minimum_required' => $requiredAmount,
                'current_amount' => $currentAmount
            ]
        );
    }

    /**
     * Réduction expirée.
     */
    public static function expiredDiscount(string $discountCode, ?\DateTimeInterface $expirationDate = null): self
    {
        if (trim($discountCode) === '') {
            throw new \InvalidArgumentException('Discount code cannot be empty');
        }

        $context = ['discount_code' => $discountCode];
        if ($expirationDate) {
            $context['expiration_date'] = $expirationDate->format('Y-m-d H:i:s');
        }

        return self::singleError(
            sprintf('Discount code "%s" has expired', $discountCode),
            $context
        );
    }

    /**
     * Normalise les erreurs (trim et filtre les vides).
     */
    private function normalizeValidationErrors(array $validationErrors): array
    {
        $normalized = array_filter(array_map('trim', $validationErrors));
        
        if (empty($normalized)) {
            throw new \InvalidArgumentException('No valid validation errors provided');
        }

        return $normalized;
    }

    /**
     * Génère le message en fonction du nombre d'erreurs.
     */
    private function generateValidationMessage(): string
    {
        $errorCount = $this->getValidationErrorsCount();

        if ($errorCount === 1) {
            return $this->validationErrors[0];
        }

        return sprintf(
            'Discount validation errors (%d error%s)',
            $errorCount,
            $errorCount > 1 ? 's' : ''
        );
    }
}
