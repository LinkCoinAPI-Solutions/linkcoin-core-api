<?php

namespace App\Account\Domain\Repository;

use App\Account\Domain\Entity\User;

/**
 * Interface de repository pour l'entité User.
 * Définit les opérations de persistence et de recherche des utilisateurs.
 */
interface UserRepositoryInterface
{
    /**
     * Trouve un utilisateur par ID.
     * 
     * @return User|null Utilisateur trouvé ou null
     */
    public function findById(int $id): ?User;

    /**
     * Trouve un utilisateur par son email.
     * Utilisé pour la connexion et la vérification d'unicité.
     * 
     * @return User|null Utilisateur trouvé ou null
     */
    public function findOneByEmail(string $email): ?User;

    /**
     * Trouve un utilisateur par son username.
     * Utilisé pour la connexion alternative.
     * 
     * @return User|null Utilisateur trouvé ou null
     */
    public function findOneByUsername(string $username): ?User;

    /**
     * Trouve un utilisateur par son token de confirmation d'email.
     * Utilisé lors de la confirmation du compte.
     * Retourne null si le token n'existe pas ou si l'email est déjà vérifié.
     * 
     * @param string $token Token de confirmation (64 chars hex)
     * @return User|null Utilisateur trouvé ou null
     */
    public function findOneByEmailConfirmationToken(string $token): ?User;

    /**
     * Persiste un utilisateur en base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function save(User $user, bool $flush = false): void;

    /**
     * Supprime un utilisateur de la base.
     * 
     * @param bool $flush Si true, flush immédiatement
     */
    public function remove(User $user, bool $flush = false): void;

    /**
     * Force la synchronisation avec la base de données.
     */
    public function flush(): void;
}
