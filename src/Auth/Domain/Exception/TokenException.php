<?php

namespace App\Auth\Domain\Exception;

use ApiPlatform\Metadata\Exception\ProblemExceptionInterface;
use Exception;

/**
 * Exception pour les erreurs liées aux tokens d'authentification.
 * Gère la génération, validation, expiration et usage des tokens.
 * Implémente RFC 7807 (Problem Details) pour API Platform.
 */
class TokenException extends Exception implements ProblemExceptionInterface
{
    public const ERROR_GENERATION_FAILED = 'token_generation_failed';
    public const ERROR_INVALID_TOKEN = 'invalid_token';
    public const ERROR_EXPIRED_TOKEN = 'expired_token';
    public const ERROR_TOKEN_NOT_FOUND = 'token_not_found';
    public const ERROR_TOKEN_ALREADY_USED = 'token_already_used';
    public const ERROR_STRATEGY_NOT_FOUND = 'strategy_not_found';

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
        return '/errors/token/' . $this->errorCode;
    }

    /**
     * Titre court selon le code d'erreur.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_GENERATION_FAILED => 'Token Generation Failed',
            self::ERROR_INVALID_TOKEN => 'Invalid Token',
            self::ERROR_EXPIRED_TOKEN => 'Expired Token',
            self::ERROR_TOKEN_NOT_FOUND => 'Token Not Found',
            self::ERROR_TOKEN_ALREADY_USED => 'Token Already Used',
            self::ERROR_STRATEGY_NOT_FOUND => 'Token Strategy Not Found',
            default => 'Token Error'
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
     * Échec de génération de token (500 Internal Server Error).
     */
    public static function generationFailed(string $reason = '', string $tokenType = 'unknown'): self
    {
        $message = 'Token generation failed';
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(
            $message,
            self::ERROR_GENERATION_FAILED,
            [
                'token_type' => $tokenType,
                'failure_reason' => $reason
            ],
            500
        );
    }

    /**
     * Token invalide ou mal formé (422 Unprocessable Entity).
     * Masque le token pour la sécurité (10 premiers caractères).
     */
    public static function invalidToken(string $tokenType = 'unknown', string $tokenValue = ''): self
    {
        return new self(
            'Token is invalid or malformed',
            self::ERROR_INVALID_TOKEN,
            [
                'token_type' => $tokenType,
                'token_value' => $tokenValue ? substr($tokenValue, 0, 10) . '...' : 'not_provided'
            ],
            422
        );
    }

    /**
     * Token expiré (410 Gone).
     * Inclut la date d'expiration si disponible.
     */
    public static function expiredToken(string $tokenType = 'unknown', ?\DateTimeInterface $expiredAt = null): self
    {
        $context = ['token_type' => $tokenType];
        if ($expiredAt) {
            $context['expired_at'] = $expiredAt->format('Y-m-d H:i:s');
        }

        return new self(
            'Token has expired',
            self::ERROR_EXPIRED_TOKEN,
            $context,
            410
        );
    }

    /**
     * Token introuvable en base (404 Not Found).
     */
    public static function tokenNotFound(string $tokenType = 'unknown'): self
    {
        return new self(
            'Token not found',
            self::ERROR_TOKEN_NOT_FOUND,
            ['token_type' => $tokenType],
            404
        );
    }

    /**
     * Token déjà utilisé (usage unique violé) (409 Conflict).
     * Inclut la date d'utilisation si disponible.
     */
    public static function tokenAlreadyUsed(string $tokenType = 'unknown', ?\DateTimeInterface $usedAt = null): self
    {
        $context = ['token_type' => $tokenType];
        if ($usedAt) {
            $context['used_at'] = $usedAt->format('Y-m-d H:i:s');
        }

        return new self(
            'Token has already been used',
            self::ERROR_TOKEN_ALREADY_USED,
            $context,
            409
        );
    }

    /**
     * Stratégie de token non trouvée (422 Unprocessable Entity).
     * Indique un problème de configuration des services.
     */
    public static function strategyNotSupported(string $tokenType): self
    {
        return new self(
            sprintf('No strategy found for token type "%s"', $tokenType),
            self::ERROR_STRATEGY_NOT_FOUND,
            [
                'token_type' => $tokenType,
                'available_strategies' => 'Check your service configuration'
            ],
            422 
        );
    }
}
