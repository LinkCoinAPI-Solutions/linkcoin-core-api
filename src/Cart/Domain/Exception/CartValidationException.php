<?php

namespace App\Cart\Domain\Exception;

/**
 * Exception pour les erreurs de validation métier du panier.
 * Utilisée pour agréger plusieurs erreurs de validation.
 */
final class CartValidationException extends CartException
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

        $this->validationErrors = array_filter(array_map('trim', $validationErrors));
        $message = $message ?? $this->generateValidationMessage();

        parent::__construct(
            $message,
            'cart_validation_failed',
            array_merge($additionalContext, ['validation_errors' => $this->validationErrors]),
            422
        );
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }

    /**
     * Crée une exception avec plusieurs erreurs.
     */
    public static function fromErrors(array $validationErrors, array $context = []): self
    {
        return new self($validationErrors, null, $context);
    }

    /**
     * Crée une exception avec une seule erreur.
     */
    public static function singleError(string $validationError, array $context = []): self
    {
        return new self([$validationError], $validationError, $context);
    }

    private function generateValidationMessage(): string
    {
        $errorCount = count($this->validationErrors);

        if ($errorCount === 1) {
            return $this->validationErrors[0];
        }

        return sprintf('Cart validation failed (%d errors)', $errorCount);
    }
}
