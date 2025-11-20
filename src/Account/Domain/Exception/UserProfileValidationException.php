<?php

namespace App\Account\Domain\Exception;

/**
 * Exception pour les erreurs de validation métier du profil.
 * Code HTTP 422 (Unprocessable Entity) pour toutes les validations.
 */
final class UserProfileValidationException extends UserProfileException
{
    /**
     * Crée une exception avec plusieurs erreurs de validation.
     */
    public static function fromErrors(array $errors, array $context = []): self
    {
        return new self(
            sprintf('Validation failed: %s', implode(', ', $errors)),
            self::ERROR_VALIDATION_FAILED,
            ['validation_errors' => $errors] + $context,
            422 
        );
    }

    /**
     * Format de téléphone invalide.
     */
    public static function invalidPhone(string $phone): self
    {
        return new self(
            sprintf('Invalid phone number format: %s', $phone),
            self::ERROR_INVALID_DATA,
            ['field' => 'phone_number'],
            422
        );
    }

    /**
     * URL de site web invalide.
     */
    public static function invalidWebsite(string $website): self
    {
        return new self(
            sprintf('Invalid website URL: %s', $website),
            self::ERROR_INVALID_DATA,
            ['field' => 'website'],
            422
        );
    }

    /**
     * Bio trop longue (dépasse la limite de caractères).
     */
    public static function bioTooLong(int $actualLength, int $maxLength): self
    {
        return new self(
            sprintf('Bio is too long (%d characters, maximum %d allowed).', $actualLength, $maxLength),
            self::ERROR_INVALID_DATA,
            ['field' => 'bio', 'max_length' => $maxLength, 'actual_length' => $actualLength],
            422
        );
    }

    /**
     * Nom de personne invalide (vide ou format incorrect).
     */
    public static function invalidPersonName(string $reason = 'Name cannot be empty or invalid'): self
    {
        return new self(
            $reason,
            self::ERROR_INVALID_DATA,
            ['field' => 'name'],
            422
        );
    }

    /**
     * Données incohérentes entre deux champs liés.
     */
    public static function inconsistentData(string $field1, string $field2): self
    {
        return new self(
            sprintf('Inconsistent data: %s and %s must be filled together.', $field1, $field2),
            self::ERROR_INCONSISTENT_DATA,
            ['fields' => [$field1, $field2]],
            422
        );
    }

    /**
     * Règle métier : newsletter nécessite les notifications email.
     */
    public static function newsletterRequiresEmail(): self
    {
        return new self(
            'Cannot subscribe to newsletter without email notifications enabled.',
            self::ERROR_INCONSISTENT_DATA,
            ['constraint' => 'newsletter_email_dependency'],
            422
        );
    }
}
