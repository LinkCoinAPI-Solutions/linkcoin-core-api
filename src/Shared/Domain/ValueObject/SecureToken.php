<?php

namespace App\Shared\Domain\ValueObject;

use App\Shared\Domain\Exception\SharedKernelException;

/**
 * Value Object représentant un token sécurisé.
 * SHARED KERNEL : Utilisé par Auth (password reset, email confirmation).
 * 
 * Encapsule la génération et la validation de tokens cryptographiques.
 * Immutable : ne peut pas être modifié après création.
 */
readonly class SecureToken
{
    private const TOKEN_LENGTH = 32; // 32 bytes = 64 chars hex

    private function __construct(
        public string $value
    ) {}

    /**
     * Génère un nouveau token aléatoire cryptographiquement sécurisé.
     * Utilise random_bytes() pour garantir l'unicité et la sécurité.
     * 
     * @throws SharedKernelException Si la génération échoue
     */
    public static function generate(): self
    {
        try {
            $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
            return new self($token);
        } catch (\Exception $e) {
            throw SharedKernelException::tokenGenerationFailed($e->getMessage());
        }
    }

    /**
     * Crée un token depuis une chaîne existante.
     * Valide le format (64 caractères hexadécimaux).
     * 
     * @throws SharedKernelException Si format invalide
     */
    public static function fromString(string $token): self
    {
        self::validate($token);
        return new self($token);
    }

    /**
     * Valide le format du token.
     * Doit être une chaîne hexadécimale de 64 caractères.
     * 
     * @throws SharedKernelException Si invalide
     */
    private static function validate(string $token): void
    {
        if (empty($token)) {
            throw SharedKernelException::emptyToken();
        }

        if (strlen($token) !== self::TOKEN_LENGTH * 2) {
            throw SharedKernelException::invalidTokenLength(self::TOKEN_LENGTH * 2, strlen($token));
        }

        if (!ctype_xdigit($token)) {
            throw SharedKernelException::invalidTokenFormat();
        }
    }

    /**
     * Compare avec un autre token (timing-safe).
     */
    public function equals(self $other): bool
    {
        return hash_equals($this->value, $other->value);
    }

    /**
     * Retourne une version masquée du token pour logging.
     * Affiche uniquement les 8 premiers caractères.
     */
    public function getMasked(): string
    {
        return substr($this->value, 0, 8) . '...' . substr($this->value, -4);
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
