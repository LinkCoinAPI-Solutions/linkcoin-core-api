<?php

namespace App\Account\Infrastructure\Repository;

use App\Account\Domain\Entity\User;
use App\Account\Domain\Repository\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository Doctrine pour l'entité User.
 * Implémente l'interface domain UserRepositoryInterface.
 * Fournit les opérations CRUD et les recherches spécifiques.
 */
class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Trouve un utilisateur par ID.
     * Utilise find() de Doctrine (cache de niveau 1 automatique).
     * 
     * @return User|null Utilisateur trouvé ou null
     */
    public function findById(int $id): ?User
    {
        return $this->find($id);
    }

    /**
     * Trouve un utilisateur par son email.
     * Normalise l'email (trim + minuscules) avant la recherche.
     * Utilisé pour la connexion et la vérification d'unicité.
     * 
     * @return User|null Utilisateur trouvé ou null
     */
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', strtolower(trim($email)))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un utilisateur par son username.
     * Normalise le username (trim) avant la recherche.
     * Utilisé pour la connexion alternative.
     * 
     * @return User|null Utilisateur trouvé ou null
     */
    public function findOneByUsername(string $username): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.username = :username')
            ->setParameter('username', trim($username))
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un utilisateur par son token de confirmation d'email.
     * Vérifie automatiquement que l'email n'est pas déjà confirmé.
     * Utilisé lors du processus de confirmation de compte.
     * 
     * Sécurité :
     * - Filtre isEmailVerified = false pour éviter double confirmation
     * - Retourne null si token introuvable ou email déjà vérifié
     * 
     * @param string $token Token de confirmation (64 chars hex)
     * @return User|null Utilisateur trouvé ou null
     */
    public function findOneByEmailConfirmationToken(string $token): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.emailConfirmationToken = :token')
            ->andWhere('u.isEmailVerified = :verified')
            ->setParameter('token', $token)
            ->setParameter('verified', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Persiste un utilisateur en base.
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function save(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->persist($user);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un utilisateur de la base.
     * Supprime aussi les relations cascade (profile, notifications, etc.).
     * 
     * @param bool $flush Si true, synchronise immédiatement avec la DB
     */
    public function remove(User $user, bool $flush = false): void
    {
        $this->getEntityManager()->remove($user);
        
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Force la synchronisation avec la base de données.
     * Applique toutes les modifications en attente.
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
