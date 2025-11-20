<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\SharedKernelException;

/**
 * Value Object représentant une URL.
 * SHARED KERNEL : Utilisé par Account (UserProfile), Product.
 * 
 * Encapsule la validation.
 * Immutable : ne peut pas être modifié après création.
 */
readonly class Url
{
    private const MAX_LENGTH = 500;

    private function __construct(
        public string $value
    ) {}

    /**
     * Crée une URL depuis une chaîne.
     * Trim automatique.
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function fromString(string $url): self
    {
        $trimmed = trim($url);
        self::validate($trimmed);
        
        return new self($trimmed);
    }

    /**
     * Valide l'URL.
     * 
     * @throws SharedKernelException Si invalide
     */
    private static function validate(string $url): void
    {
        if (empty($url)) {
            throw SharedKernelException::emptyUrl();
        }

        if (strlen($url) > self::MAX_LENGTH) {
            throw SharedKernelException::urlTooLong(self::MAX_LENGTH, strlen($url));
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw SharedKernelException::invalidUrlFormat($url);
        }
    }

    /**
     * Retourne le domaine de l'URL.
     */
    public function getDomain(): string
    {
        return parse_url($this->value, PHP_URL_HOST) ?? '';
    }

    /**
     * Retourne le protocole (http, https).
     */
    public function getScheme(): string
    {
        return parse_url($this->value, PHP_URL_SCHEME) ?? '';
    }

    /**
     * Vérifie si l'URL est en HTTPS.
     */
    public function isSecure(): bool
    {
        return $this->getScheme() === 'https';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
