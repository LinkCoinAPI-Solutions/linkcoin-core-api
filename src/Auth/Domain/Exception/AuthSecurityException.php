<?php

namespace App\Auth\Domain\Exception;

/**
 * Exception pour les erreurs de sécurité d'authentification.
 * Gère le rate limiting, le blocage de comptes et les activités suspectes.
 */
final class AuthSecurityException extends AuthException
{
    public const ERROR_TOO_MANY_ATTEMPTS = 'too_many_attempts';
    public const ERROR_SUSPICIOUS_ACTIVITY = 'suspicious_activity';
    public const ERROR_ACCOUNT_LOCKED = 'account_locked';
    public const ERROR_IP_BLOCKED = 'ip_blocked';

    /**
     * Titre selon le type d'erreur de sécurité.
     */
    public function getTitle(): ?string
    {
        return match ($this->errorCode) {
            self::ERROR_TOO_MANY_ATTEMPTS => 'Too Many Attempts',
            self::ERROR_SUSPICIOUS_ACTIVITY => 'Suspicious Activity Detected',
            self::ERROR_ACCOUNT_LOCKED => 'Account Locked',
            self::ERROR_IP_BLOCKED => 'IP Address Blocked',
            default => parent::getTitle()
        };
    }

    /**
     * Trop de tentatives d'authentification (429 Too Many Requests).
     */
    public static function tooManyAttempts(string $identifier = '', int $remainingTime = 0): self
    {
        $message = 'Too many authentication attempts. Please try again later.';
        if ($remainingTime > 0) {
            $message .= " (Retry in {$remainingTime} seconds)";
        }

        return new self(
            $message,
            self::ERROR_TOO_MANY_ATTEMPTS,
            [
                'identifier' => $identifier,
                'retry_after' => $remainingTime
            ],
            429
        );
    }

    /**
     * Activité suspecte détectée (429 Too Many Requests).
     */
    public static function suspiciousActivity(string $reason = '', string $ipAddress = ''): self
    {
        return new self(
            'Suspicious activity detected. Account temporarily restricted.',
            self::ERROR_SUSPICIOUS_ACTIVITY,
            [
                'reason' => $reason,
                'ip_address' => $ipAddress
            ],
            429
        );
    }

    /**
     * Compte verrouillé pour raisons de sécurité (423 Locked).
     */
    public static function accountLocked(string $reason = '', ?\DateTimeInterface $lockedUntil = null): self
    {
        $context = ['lock_reason' => $reason];
        if ($lockedUntil) {
            $context['locked_until'] = $lockedUntil->format('Y-m-d H:i:s');
        }

        return new self(
            'Account is locked due to security reasons',
            self::ERROR_ACCOUNT_LOCKED,
            $context,
            423
        );
    }

    /**
     * Adresse IP bloquée (403 Forbidden).
     */
    public static function ipBlocked(string $ipAddress, string $reason = ''): self
    {
        return new self(
            'Your IP address has been blocked',
            self::ERROR_IP_BLOCKED,
            [
                'ip_address' => $ipAddress,
                'block_reason' => $reason
            ],
            403
        );
    }
}
