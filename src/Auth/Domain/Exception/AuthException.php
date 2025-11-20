<?php

namespace App\Auth\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception de base pour les erreurs d'authentification.
 * Implémente RFC 7807 (Problem Details) pour API Platform.
 * Fournit des factory methods pour chaque type d'erreur.
 */
class AuthException extends Exception implements ProblemExceptionInterface
{
    // Codes d'erreur constants
    public const ERROR_EMAIL_ALREADY_CONFIRMED = 'email_already_confirmed';
    public const ERROR_EMAIL_ALREADY_USED = 'email_already_used';
    public const ERROR_EXPIRED_TOKEN = 'expired_token';
    public const ERROR_INVALID_CONFIRMATION_TOKEN = 'invalid_confirmation_token';
    public const ERROR_INVALID_TOKEN = 'invalid_token';
    public const ERROR_PASSWORD_MISMATCH = 'password_mismatch';
    public const ERROR_TOKEN_ALREADY_USED = 'token_already_used';
    public const ERROR_USER_NOT_ACTIVE = 'user_not_active';
    public const ERROR_AUTHENTICATION_FAILED = 'authentication_failed';
    public const ERROR_ACCESS_DENIED = 'access_denied';
    public const ERROR_USER_NOT_FOUND = 'user_not_found';
    public const ERROR_INVALID_CREDENTIALS = 'invalid_credentials';
    public const ERROR_USER_NOT_AUTHENTICATED = 'user_not_authenticated';

    protected string $errorCode;
    protected array $errorContext = [];
    private int $httpStatus;

    public function __construct(
        string $message,
        string $errorCode,
        array $errorContext = [],
        int $httpStatus = 400,
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
        return '/errors/auth/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_EMAIL_ALREADY_CONFIRMED => 'Email Already Confirmed',
            self::ERROR_EMAIL_ALREADY_USED => 'Email Already Used',
            self::ERROR_EXPIRED_TOKEN => 'Expired Token',
            self::ERROR_INVALID_CONFIRMATION_TOKEN => 'Invalid Confirmation Token',
            self::ERROR_INVALID_TOKEN => 'Invalid Token',
            self::ERROR_PASSWORD_MISMATCH => 'Password Mismatch',
            self::ERROR_TOKEN_ALREADY_USED => 'Token Already Used',
            self::ERROR_USER_NOT_ACTIVE => 'User Not Active',
            self::ERROR_AUTHENTICATION_FAILED => 'Authentication Failed',
            self::ERROR_ACCESS_DENIED => 'Access Denied',
            self::ERROR_USER_NOT_FOUND => 'User Not Found',
            self::ERROR_INVALID_CREDENTIALS => 'Invalid Credentials',
            self::ERROR_USER_NOT_AUTHENTICATED => 'Authentication Required',
            default => 'Authentication Error'
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
     * Email déjà confirmé (409 Conflict).
     */
    public static function emailAlreadyConfirmed(string $email): self
    {
        return new self(
            'This email has already been confirmed',
            self::ERROR_EMAIL_ALREADY_CONFIRMED,
            ['email' => $email],
            409
        );
    }

    /**
     * Email déjà utilisé lors de l'inscription (409 Conflict).
     */
    public static function emailAlreadyUsed(string $email): self
    {
        return new self(
            'This email address is already in use',
            self::ERROR_EMAIL_ALREADY_USED,
            ['email' => $email],
            409
        );
    }

    /**
     * Token expiré (410 Gone).
     */
    public static function expiredToken(string $tokenType = 'authentication'): self
    {
        return new self(
            'Token has expired',
            self::ERROR_EXPIRED_TOKEN,
            ['token_type' => $tokenType],
            410
        );
    }

    /**
     * Token de confirmation invalide (422 Unprocessable Entity).
     */
    public static function invalidConfirmationToken(string $reason = ''): self
    {
        $message = 'The confirmation token is invalid, expired, or already used';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            $message,
            self::ERROR_INVALID_CONFIRMATION_TOKEN,
            ['reason' => $reason],
            422
        );
    }

    /**
     * Token invalide ou introuvable (404 Not Found).
     */
    public static function invalidToken(string $tokenType = 'authentication'): self
    {
        return new self(
            'Token is invalid or not found',
            self::ERROR_INVALID_TOKEN,
            ['token_type' => $tokenType],
            404
        );
    }

    /**
     * Mots de passe non identiques (422 Unprocessable Entity).
     */
    public static function passwordMismatch(): self
    {
        return new self(
            'Password and confirmation do not match',
            self::ERROR_PASSWORD_MISMATCH,
            [],
            422
        );
    }

    /**
     * Token déjà utilisé (409 Conflict).
     */
    public static function tokenAlreadyUsed(string $tokenType = 'authentication'): self
    {
        return new self(
            'This token has already been used',
            self::ERROR_TOKEN_ALREADY_USED,
            ['token_type' => $tokenType],
            409
        );
    }

    /**
     * Compte utilisateur inactif (403 Forbidden).
     */
    public static function userNotActive(string $email = '', string $reason = 'email_not_confirmed'): self
    {
        return new self(
            'User account is not active. Please confirm your email address first.',
            self::ERROR_USER_NOT_ACTIVE,
            [
                'email' => $email,
                'activation_required' => $reason
            ],
            403
        );
    }

    /**
     * Échec d'authentification générique (401 Unauthorized).
     */
    public static function authenticationFailed(string $reason = ''): self
    {
        $message = 'Authentication failed';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            $message,
            self::ERROR_AUTHENTICATION_FAILED,
            ['failure_reason' => $reason],
            401
        );
    }

    /**
     * Accès refusé à une ressource (403 Forbidden).
     */
    public static function accessDenied(string $resource = '', string $action = ''): self
    {
        $message = 'Access denied';
        if ($resource) {
            $message .= " to {$resource}";
        }
        if ($action) {
            $message .= " for action: {$action}";
        }

        return new self(
            $message,
            self::ERROR_ACCESS_DENIED,
            [
                'resource' => $resource,
                'action' => $action
            ],
            403
        );
    }

    /**
     * Utilisateur introuvable (404 Not Found).
     */
    public static function userNotFound(string $identifier = ''): self
    {
        return new self(
            'User not found',
            self::ERROR_USER_NOT_FOUND,
            ['identifier' => $identifier],
            404
        );
    }

    /**
     * Utilisateur non authentifié (401 Unauthorized).
     */
    public static function userNotAuthenticated(): self
    {
        return new self(
            'Authentication required',
            self::ERROR_USER_NOT_AUTHENTICATED,
            [],
            401
        );
    }
}
