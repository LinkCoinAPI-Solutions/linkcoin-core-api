<?php

namespace App\Account\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception de base pour le domaine UserProfile.
 * Implémente RFC 7807 (Problem Details) pour API Platform.
 * Toutes les exceptions de profil héritent de cette classe.
 */
class UserProfileException extends Exception implements ProblemExceptionInterface
{
    // Codes d'erreur généraux (utilisés)
    public const ERROR_INVALID_DATA = 'invalid_profile_data';
    public const ERROR_VALIDATION_FAILED = 'profile_validation_failed';
    public const ERROR_PERSISTENCE_FAILED = 'profile_persistence_failed';
    public const ERROR_MAPPING_FAILED = 'profile_mapping_failed';
    public const ERROR_INCONSISTENT_DATA = 'inconsistent_profile_data';
    public const ERROR_INACTIVE_USER = 'inactive_user_profile_access';

    // Codes d'erreur spécifiques (utilisés)
    public const ERROR_INVALID_STATE = 'invalid_profile_state';
    public const ERROR_INVALID_TOKEN = 'invalid_security_token';
    public const ERROR_MISSING_PROFILE_ENTITY = 'missing_profile_entity';

    protected string $errorCode;
    protected array $errorContext = [];
    private int $httpStatus;

    public function __construct(
        string $message,
        string $errorCode,
        array $errorContext = [],
        int $httpStatus = 500,
        ?\Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->errorContext = $errorContext;
        $this->httpStatus = $httpStatus;

        parent::__construct($message, 0, $previous);
    }

    /**
     * URI du type d'erreur selon RFC 7807.
     */
    public function getType(): string
    {
        return '/errors/user-profile/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_INVALID_DATA => 'Invalid Profile Data',
            self::ERROR_VALIDATION_FAILED => 'Profile Validation Error',
            self::ERROR_PERSISTENCE_FAILED => 'Profile Persistence Error',
            self::ERROR_MAPPING_FAILED => 'Data Mapping Error',
            self::ERROR_INCONSISTENT_DATA => 'Inconsistent Profile Data',
            self::ERROR_INACTIVE_USER => 'Inactive User Access',
            self::ERROR_INVALID_STATE => 'Invalid Profile State',
            self::ERROR_INVALID_TOKEN => 'Invalid Security Token',
            self::ERROR_MISSING_PROFILE_ENTITY => 'Missing Profile Entity',
            default => 'User Profile System Error'
        };
    }

    /**
     * Code HTTP de la réponse.
     */
    public function getStatus(): ?int
    {
        return $this->httpStatus;
    }

    /**
     * Message détaillé de l'erreur.
     */
    public function getDetail(): ?string
    {
        return $this->getMessage();
    }

    /**
     * URI de l'instance concernée (optionnel).
     */
    public function getInstance(): ?string
    {
        return null;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorContext(): array
    {
        return $this->errorContext;
    }

    /**
     * État de profil invalide (500 Internal Server Error).
     */
    public static function invalidState(int $userId, string $reason): self
    {
        return new self(
            sprintf('Invalid profile state for user #%d: %s', $userId, $reason),
            self::ERROR_INVALID_STATE,
            ['user_id' => $userId, 'reason' => $reason],
            500
        );
    }
}
