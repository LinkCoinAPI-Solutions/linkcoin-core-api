<?php

namespace App\Shared\Tests\Support\Factory;

use App\Account\Domain\Entity\User;
use App\Auth\Domain\Entity\PasswordResetToken;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * Factory Foundry pour PasswordResetToken.
 * Utilise PasswordResetToken::create() au lieu du constructeur privé.
 *
 * @extends PersistentProxyObjectFactory<PasswordResetToken>
 */
final class PasswordResetTokenFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return PasswordResetToken::class;
    }

    /**
     * Valeurs par défaut.
     * Note : 'validityInHours' est utilisé par create(), pas 'expiresAt' directement.
     */
    protected function defaults(): array
    {
        return [
            'user' => UserFactory::new()->active(),
            'validityInHours' => 1, // Utilisé par PasswordResetToken::create()
        ];
    }

    /**
     * Utilise la factory method PasswordResetToken::create() au lieu du constructeur.
     */
    protected function initialize(): static
    {
        return $this->instantiateWith(function (array $attributes): PasswordResetToken {
            /** @var User $user */
            $user = $attributes['user'];
            $validityInHours = $attributes['validityInHours'] ?? 1;
            
            $token = PasswordResetToken::create($user, $validityInHours);
            
            // Manipulations post-création si nécessaire
            if (isset($attributes['_expired']) && $attributes['_expired'] === true) {
                $token->invalidate(); // Marque comme expiré
            }
            
            if (isset($attributes['_used']) && $attributes['_used'] === true) {
                // Simule l'utilisation (nécessite que le token soit valide)
                if (!$token->isExpired()) {
                    $token->markAsUsed();
                }
            }
            
            return $token;
        });
    }

    // ==================== États personnalisés ====================

    /**
     * Crée un token expiré.
     */
    public function expired(): static
    {
        return $this->with([
            'validityInHours' => -1, // Truc : validité négative = déjà expiré
            '_expired' => true,
        ]);
    }

    /**
     * Crée un token déjà utilisé.
     */
    public function used(): static
    {
        return $this->with([
            '_used' => true,
        ]);
    }

    /**
     * Crée un token valide (2h de validité).
     */
    public function valid(): static
    {
        return $this->with([
            'validityInHours' => 2,
        ]);
    }

    /**
     * Crée un token qui expire bientôt (5 minutes).
     */
    public function expiringSoon(): static
    {
        return $this->with([
            'validityInHours' => 0.083, // 5 minutes = 0.083 heures
        ]);
    }

    /**
     * Crée un token avec une durée de vie personnalisée.
     * 
     * @param int $hours Nombre d'heures de validité
     */
    public function withLifetime(int $hours): static
    {
        return $this->with([
            'validityInHours' => $hours,
        ]);
    }

    // ==================== Méthodes utilitaires ====================

    /**
     * Associe le token à un utilisateur spécifique.
     */
    public function forUser(User $user): static
    {
        return $this->with(['user' => $user]);
    }

    /**
     * Crée un token qui expire dans X minutes.
     */
    public function expiresInMinutes(int $minutes): static
    {
        return $this->with([
            'validityInHours' => $minutes / 60,
        ]);
    }

    /**
     * Crée un token avec une longue validité (24h).
     */
    public function longLived(): static
    {
        return $this->with([
            'validityInHours' => 24,
        ]);
    }
}
