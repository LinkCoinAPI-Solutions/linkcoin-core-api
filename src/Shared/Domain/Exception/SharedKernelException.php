<?php

namespace App\Shared\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception de base pour les erreurs du Shared Kernel.
 * Implémente RFC 7807 (Problem Details) pour API Platform.
 * Fournit des factory methods pour chaque type d'erreur de validation.
 */
class SharedKernelException extends Exception implements ProblemExceptionInterface
{
    // Codes d'erreur constants
    public const ERROR_INVALID_EMAIL = 'invalid_email';
    public const ERROR_INVALID_MONEY = 'invalid_money';
    public const ERROR_INVALID_NAME = 'invalid_name';
    public const ERROR_INVALID_PHONE = 'invalid_phone';
    public const ERROR_INVALID_TOKEN = 'invalid_token';
    public const ERROR_INVALID_URL = 'invalid_url';
    public const ERROR_EMPTY_VALUE = 'empty_value';
    public const ERROR_VALUE_TOO_LONG = 'value_too_long';
    public const ERROR_VALUE_TOO_SHORT = 'value_too_short';
    public const ERROR_INVALID_FORMAT = 'invalid_format';
    public const ERROR_INVALID_CHARACTERS = 'invalid_characters';
    public const ERROR_NEGATIVE_AMOUNT = 'negative_amount';
    public const ERROR_AMOUNT_TOO_HIGH = 'amount_too_high';
    public const ERROR_TOKEN_GENERATION_FAILED = 'token_generation_failed';

    protected string $errorCode;
    protected array $errorContext = [];
    private int $httpStatus;

    public function __construct(
        string $message,
        string $errorCode,
        array $errorContext = [],
        int $httpStatus = 422,
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
        return '/errors/shared/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_INVALID_EMAIL => 'Invalid Email',
            self::ERROR_INVALID_MONEY => 'Invalid Amount',
            self::ERROR_INVALID_NAME => 'Invalid Name',
            self::ERROR_INVALID_PHONE => 'Invalid Phone Number',
            self::ERROR_INVALID_TOKEN => 'Invalid Token',
            self::ERROR_INVALID_URL => 'Invalid URL',
            self::ERROR_EMPTY_VALUE => 'Empty Value',
            self::ERROR_VALUE_TOO_LONG => 'Value Too Long',
            self::ERROR_VALUE_TOO_SHORT => 'Value Too Short',
            self::ERROR_INVALID_FORMAT => 'Invalid Format',
            self::ERROR_INVALID_CHARACTERS => 'Invalid Characters',
            self::ERROR_NEGATIVE_AMOUNT => 'Negative Amount',
            self::ERROR_AMOUNT_TOO_HIGH => 'Amount Too High',
            self::ERROR_TOKEN_GENERATION_FAILED => 'Token Generation Failed',
            default => 'Validation Error'
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

    // ========== Email Exceptions ==========

    /**
     * Email vide (422 Unprocessable Entity).
     */
    public static function emptyEmail(): self
    {
        return new self(
            'Email cannot be empty',
            self::ERROR_EMPTY_VALUE,
            ['field' => 'email'],
            422
        );
    }

    /**
     * Email trop long (422 Unprocessable Entity).
     */
    public static function emailTooLong(int $maxLength, int $actualLength): self
    {
        return new self(
            sprintf('Email cannot exceed %d characters', $maxLength),
            self::ERROR_VALUE_TOO_LONG,
            [
                'field' => 'email',
                'max_length' => $maxLength,
                'actual_length' => $actualLength
            ],
            422
        );
    }

    /**
     * Format d'email invalide (422 Unprocessable Entity).
     */
    public static function invalidEmailFormat(string $email = ''): self
    {
        return new self(
            'Invalid email format',
            self::ERROR_INVALID_EMAIL,
            ['provided_value' => $email],
            422
        );
    }

    // ========== Money Exceptions ==========

    /**
     * Montant négatif (422 Unprocessable Entity).
     */
    public static function negativeAmount(float $amount): self
    {
        return new self(
            'Amount cannot be negative',
            self::ERROR_NEGATIVE_AMOUNT,
            [
                'provided_amount' => $amount,
                'min_amount' => 0
            ],
            422
        );
    }

    /**
     * Montant non fini (NaN, infini) (422 Unprocessable Entity).
     */
    public static function invalidAmount(float $amount): self
    {
        return new self(
            'Amount must be a valid finite number',
            self::ERROR_INVALID_MONEY,
            ['provided_amount' => $amount],
            422
        );
    }

    /**
     * Montant trop élevé (422 Unprocessable Entity).
     */
    public static function amountTooHigh(float $amount, float $maxAmount): self
    {
        return new self(
            sprintf('Amount cannot exceed %.2f', $maxAmount),
            self::ERROR_AMOUNT_TOO_HIGH,
            [
                'provided_amount' => $amount,
                'max_amount' => $maxAmount
            ],
            422
        );
    }

    // ========== PersonName Exceptions ==========

    /**
     * Nom vide (422 Unprocessable Entity).
     */
    public static function emptyName(): self
    {
        return new self(
            'Name cannot be empty',
            self::ERROR_EMPTY_VALUE,
            ['field' => 'name'],
            422
        );
    }

    /**
     * Nom trop court ou trop long (422 Unprocessable Entity).
     */
    public static function invalidNameLength(int $minLength, int $maxLength, int $actualLength): self
    {
        return new self(
            sprintf('Name must be between %d and %d characters', $minLength, $maxLength),
            $actualLength < $minLength ? self::ERROR_VALUE_TOO_SHORT : self::ERROR_VALUE_TOO_LONG,
            [
                'field' => 'name',
                'min_length' => $minLength,
                'max_length' => $maxLength,
                'actual_length' => $actualLength
            ],
            422
        );
    }

    /**
     * Caractères invalides dans le nom (422 Unprocessable Entity).
     */
    public static function invalidNameCharacters(): self
    {
        return new self(
            'Name can only contain letters, spaces, hyphens and apostrophes',
            self::ERROR_INVALID_CHARACTERS,
            [
                'field' => 'name',
                'allowed_characters' => 'letters, spaces, hyphens, apostrophes'
            ],
            422
        );
    }

    // ========== PhoneNumber Exceptions ==========

    /**
     * Numéro de téléphone vide (422 Unprocessable Entity).
     */
    public static function emptyPhoneNumber(): self
    {
        return new self(
            'Phone number cannot be empty',
            self::ERROR_EMPTY_VALUE,
            ['field' => 'phone_number'],
            422
        );
    }

    /**
     * Numéro de téléphone trop long (422 Unprocessable Entity).
     */
    public static function phoneNumberTooLong(int $maxLength, int $actualLength): self
    {
        return new self(
            sprintf('Phone number cannot exceed %d characters', $maxLength),
            self::ERROR_VALUE_TOO_LONG,
            [
                'field' => 'phone_number',
                'max_length' => $maxLength,
                'actual_length' => $actualLength
            ],
            422
        );
    }

    /**
     * Format de numéro de téléphone invalide (422 Unprocessable Entity).
     */
    public static function invalidPhoneNumberFormat(): self
    {
        return new self(
            'Phone number can only contain digits, spaces, +, -, (), .',
            self::ERROR_INVALID_PHONE,
            [
                'field' => 'phone_number',
                'allowed_characters' => 'digits, spaces, +, -, (), .'
            ],
            422
        );
    }

    // ========== SecureToken Exceptions ==========

    /**
     * Token vide (422 Unprocessable Entity).
     */
    public static function emptyToken(): self
    {
        return new self(
            'Token cannot be empty',
            self::ERROR_EMPTY_VALUE,
            ['field' => 'token'],
            422
        );
    }

    /**
     * Longueur de token invalide (422 Unprocessable Entity).
     */
    public static function invalidTokenLength(int $expectedLength, int $actualLength): self
    {
        return new self(
            sprintf('Token must be exactly %d characters', $expectedLength),
            self::ERROR_INVALID_FORMAT,
            [
                'field' => 'token',
                'expected_length' => $expectedLength,
                'actual_length' => $actualLength
            ],
            422
        );
    }

    /**
     * Token non hexadécimal (422 Unprocessable Entity).
     */
    public static function invalidTokenFormat(): self
    {
        return new self(
            'Token must be a valid hexadecimal string',
            self::ERROR_INVALID_TOKEN,
            [
                'field' => 'token',
                'expected_format' => 'hexadecimal'
            ],
            422
        );
    }

    /**
     * Échec de génération de token (500 Internal Server Error).
     */
    public static function tokenGenerationFailed(string $reason = ''): self
    {
        return new self(
            'Failed to generate secure token',
            self::ERROR_TOKEN_GENERATION_FAILED,
            ['reason' => $reason],
            500
        );
    }

    // ========== URL Exceptions ==========

    /**
     * URL vide (422 Unprocessable Entity).
     */
    public static function emptyUrl(): self
    {
        return new self(
            'URL cannot be empty',
            self::ERROR_EMPTY_VALUE,
            ['field' => 'url'],
            422
        );
    }

    /**
     * URL trop longue (422 Unprocessable Entity).
     */
    public static function urlTooLong(int $maxLength, int $actualLength): self
    {
        return new self(
            sprintf('URL cannot exceed %d characters', $maxLength),
            self::ERROR_VALUE_TOO_LONG,
            [
                'field' => 'url',
                'max_length' => $maxLength,
                'actual_length' => $actualLength
            ],
            422
        );
    }

    /**
     * Format d'URL invalide (422 Unprocessable Entity).
     */
    public static function invalidUrlFormat(string $url = ''): self
    {
        return new self(
            'Invalid URL format',
            self::ERROR_INVALID_URL,
            ['provided_value' => $url],
            422
        );
    }
}
