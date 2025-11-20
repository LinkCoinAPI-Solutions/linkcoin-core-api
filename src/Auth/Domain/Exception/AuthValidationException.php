<?php

namespace App\Auth\Domain\Exception;

/**
 * Exception pour les erreurs de validation d'authentification.
 * Peut agréger plusieurs erreurs de validation en une seule exception.
 * Utilisée principalement pour les formulaires avec validation multiple.
 */
final class AuthValidationException extends AuthException
{
    private array $validationErrors;

    public function __construct(
        array $validationErrors,
        ?string $message = null,
        array $additionalContext = []
    ) {
        $this->validationErrors = $this->normalizeValidationErrors($validationErrors);
        $message = $message ?? $this->generateValidationMessage();

        parent::__construct(
            $message,
            'auth_validation_failed',
            array_merge($additionalContext, ['validation_errors' => $this->validationErrors]),
            422
        );
    }

    /**
     * Titre pour les erreurs de validation (422 Unprocessable Entity).
     */
    public function getTitle(): ?string
    {
        return 'Authentication Validation Error';
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
            $validationErrors = ['Unknown authentication validation error'];
        }

        return new self($validationErrors, null, $context);
    }

    /**
     * Crée une exception avec une seule erreur de validation.
     * 
     * @throws \LogicException Si l'erreur est vide (erreur de développement)
     */
    public static function singleError(string $validationError, array $context = []): self
    {
        if (trim($validationError) === '') {
            throw new \LogicException('Validation error message cannot be empty');
        }

        return new self([$validationError], $validationError, $context);
    }

    /**
     * Format d'email invalide.
     */
    public static function invalidEmailFormat(string $email): self
    {
        return self::singleError(
            sprintf('Email address "%s" is not valid', $email),
            ['invalid_email' => $email]
        );
    }

    /**
     * Mot de passe trop faible (ne respecte pas les exigences).
     */
    public static function weakPassword(array $requirements): self
    {
        return self::singleError(
            'Password does not meet security requirements',
            ['password_requirements' => $requirements]
        );
    }

    /**
     * Confirmation de mot de passe ne correspond pas.
     */
    public static function invalidPasswordConfirmation(): self
    {
        return self::singleError('Password confirmation does not match');
    }

    /**
     * Champs requis manquants dans le formulaire.
     */
    public static function requiredFields(array $missingFields): self
    {
        $errors = [];
        foreach ($missingFields as $field) {
            $errors[] = sprintf('Field "%s" is required', $field);
        }

        return new self(
            $errors,
            sprintf('%d required field%s missing', count($errors), count($errors) > 1 ? 's' : ''),
            ['missing_fields' => $missingFields]
        );
    }

    /**
     * Normalise les erreurs : trim et filtre les valeurs vides.
     * 
     * @throws \LogicException Si aucune erreur valide (erreur de développement)
     */
    private function normalizeValidationErrors(array $validationErrors): array
    {
        if (empty($validationErrors)) {
            throw new \LogicException('Validation errors array cannot be empty');
        }

        $normalized = array_filter(array_map('trim', $validationErrors));
        
        if (empty($normalized)) {
            throw new \LogicException('No valid validation errors provided after normalization');
        }

        return $normalized;
    }

    /**
     * Génère le message en fonction du nombre d'erreurs.
     * Une seule erreur : retourne l'erreur directement.
     * Plusieurs erreurs : retourne un message de comptage.
     */
    private function generateValidationMessage(): string
    {
        $errorCount = $this->getValidationErrorsCount();

        if ($errorCount === 1) {
            return $this->validationErrors[0];
        }

        return sprintf(
            'Authentication validation errors (%d error%s)',
            $errorCount,
            $errorCount > 1 ? 's' : ''
        );
    }
}
