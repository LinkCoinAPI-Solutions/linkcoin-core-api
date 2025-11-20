<?php

namespace App\Auth\Domain\ValueObject;

use App\Auth\Domain\Exception\AuthValidationException;

/**
 * Value Object représentant un mot de passe sécurisé.
 * Valide la force du mot de passe avant le hash.
 * N'effectue PAS le hash (délégué au PasswordHasher de Symfony).
 */
readonly class SecurePassword
{
    private const MIN_LENGTH = 8;
    private const MAX_LENGTH = 255;
    private const MIN_SCORE = 4;

    private function __construct(
        public string $plainPassword
    ) {}

    /**
     * Crée un mot de passe sécurisé depuis une chaîne.
     * Vérifie la longueur, la confirmation et les infos personnelles.
     * 
     * @param string $password Mot de passe en clair
     * @param string|null $confirmation Confirmation du mot de passe
     * @param array $personalInfo Infos personnelles à exclure (email, nom, etc.)
     * @throws AuthValidationException Si la validation échoue
     */
    public static function fromPlain(
        string $password,
        ?string $confirmation = null,
        array $personalInfo = []
    ): self {
        self::validate($password, $confirmation, $personalInfo);
        return new self($password);
    }

    /**
     * Valide le mot de passe selon les règles de sécurité.
     * 
     * @throws AuthValidationException Si une règle est violée
     */
    private static function validate(
        string $password,
        ?string $confirmation,
        array $personalInfo
    ): void {
        $errors = [];

        // Vérifie la longueur minimale
        if (strlen($password) < self::MIN_LENGTH) {
            $errors[] = sprintf('Password must be at least %d characters', self::MIN_LENGTH);
        }

        // Vérifie la longueur maximale
        if (strlen($password) > self::MAX_LENGTH) {
            $errors[] = sprintf('Password cannot exceed %d characters', self::MAX_LENGTH);
        }

        // Vérifie la correspondance avec la confirmation
        if ($confirmation !== null && $password !== $confirmation) {
            throw AuthValidationException::invalidPasswordConfirmation();
        }

        // Vérifie que le mot de passe ne contient pas d'infos personnelles
        if (!empty($personalInfo)) {
            foreach ($personalInfo as $info) {
                if (!empty($info) && stripos($password, $info) !== false) {
                    $errors[] = 'Password cannot contain personal information';
                    break;
                }
            }
        }

        if (!empty($errors)) {
            throw AuthValidationException::fromErrors($errors);
        }
    }

    /**
     * Indique si le mot de passe doit être hashé.
     * Toujours true car les mots de passe ne doivent jamais être stockés en clair.
     */
    public function requiresHash(): bool
    {
        return true;
    }
}
