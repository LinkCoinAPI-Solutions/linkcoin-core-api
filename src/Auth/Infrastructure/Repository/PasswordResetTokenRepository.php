<?php

namespace App\Auth\Infrastructure\Repository;

use App\Auth\Domain\Entity\PasswordResetToken;
use App\Auth\Domain\Repository\PasswordResetTokenRepositoryInterface;
use App\Account\Domain\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository pour les tokens de réinitialisation de mot de passe.
 * Gère la persistence et les requêtes de recherche/invalidation des tokens.
 */
class PasswordResetTokenRepository extends ServiceEntityRepository implements PasswordResetTokenRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Trouve un token par sa valeur (sans vérification de validité).
     * Utilisé pour récupérer le token avant validation manuelle dans le service.
     */
    public function findOneByToken(string $token): ?PasswordResetToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    /**
     * Trouve un token valide par sa valeur.
     * Vérifie automatiquement que le token n'est pas expiré et n'est pas déjà utilisé.
     * 
     * Alternative : utiliser findOneByToken() + isValid() dans le service.
     */
    public function findValidToken(string $token): ?PasswordResetToken
    {
        return $this->createQueryBuilder('t')
            ->where('t.token = :token')
            ->andWhere('t.expiresAt > :now')
            ->andWhere('t.used = :used')
            ->setParameter('token', $token)
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('used', false)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les tokens actifs d'un utilisateur.
     * Actifs = non expirés ET non utilisés.
     * Triés du plus récent au plus ancien.
     * 
     * @return PasswordResetToken[]
     */
    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.used = :used')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('user', $user)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve tous les tokens d'un utilisateur (tous états).
     * Inclut les tokens expirés et utilisés.
     * Triés du plus récent au plus ancien.
     * 
     * Utilisé pour l'audit ou l'affichage historique.
     * 
     * @return PasswordResetToken[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Persiste un token en base.
     */
    public function save(PasswordResetToken $token, bool $flush = false): void
    {
        $this->getEntityManager()->persist($token);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Supprime un token de la base.
     */
    public function remove(PasswordResetToken $token, bool $flush = false): void
    {
        $this->getEntityManager()->remove($token);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Invalide tous les tokens actifs d'un utilisateur.
     * Marque les tokens comme utilisés (used = true).
     * Appelé avant la création d'un nouveau token.
     * 
     * @return int Nombre de tokens invalidés
     */
    public function invalidateUserTokens(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.used', ':used')
            ->where('t.user = :user')
            ->andWhere('t.used = :notUsed')
            ->andWhere('t.expiresAt > :now')
            ->setParameter('used', true)
            ->setParameter('notUsed', false)
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    /**
     * Supprime les tokens expirés avant une date donnée.
     * Nettoyage automatique pour éviter l'accumulation en base.
     * 
     * Exemple : removeExpiredTokens(new \DateTimeImmutable('-30 days'))
     * → Supprime tous les tokens expirés depuis plus de 30 jours.
     * 
     * @param \DateTimeInterface $before Date limite
     * @return int Nombre de tokens supprimés
     */
    public function removeExpiredTokens(\DateTimeInterface $before): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute();
    }

    /**
     * Force la synchronisation avec la DB.
     * Utilisé quand on fait plusieurs persist() sans flush.
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }
}
