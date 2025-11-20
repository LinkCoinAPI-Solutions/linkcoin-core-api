<?php

namespace App\Account\Application\Service;

use App\Account\Application\Dto\V1\Input\UserProfileUpdateInput;
use App\Account\Application\Dto\V1\Output\UserProfileOutput;
use App\Account\Application\Mapper\Contract\UserProfileMapperInterface;
use App\Account\Application\Service\Contract\Updater\UserProfileUpdaterInterface;
use App\Account\Application\Service\Factory\UserProfileFactory;
use App\Account\Domain\Entity\User;
use App\Account\Domain\Exception\UserProfileSecurityException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service applicatif orchestrant les opérations sur les profils utilisateur.
 * Gère la récupération (avec cache), la mise à jour via stratégies (updaters),
 * et la création automatique des entités manquantes (Profile, NotificationPreferences).
 */
readonly class UserProfileService
{
    private const CACHE_TTL = 1800; // 30 minutes
    private const CACHE_KEY_PROFILE = 'user_profile';
    private const CACHE_KEY_BASIC = 'user_basic_profile';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserProfileMapperInterface $mapper,
        private CacheInterface $cache,
        private LoggerInterface $logger,
        private UserProfileFactory $factory,
        private iterable $updaters
    ) {}

    /**
     * Récupère le profil complet d'un utilisateur (avec cache).
     * Crée automatiquement les entités manquantes (Profile, NotificationPreferences).
     */
    public function getUserProfile(User $user): UserProfileOutput
    {
        $this->guardUserAccess($user);
        $this->ensureEntitiesExist($user);
        
        return $this->fetchCachedProfile($user);
    }

    /**
     * Met à jour le profil utilisateur via les updaters configurés.
     * Exécute les updaters par ordre de priorité décroissante.
     * Invalide le cache après mise à jour.
     */
    public function updateProfile(User $user, UserProfileUpdateInput $input): User
    {
        $this->guardUserAccess($user);
        
        return $this->executeProfileUpdate($user, $input);
    }

    /**
     * Vérifie que l'utilisateur peut accéder à son profil.
     * Interdit l'accès aux comptes inactifs.
     * 
     * @throws UserProfileSecurityException Si le compte est inactif
     */
    private function guardUserAccess(User $user): void
    {
        if (!$user->isActive()) {
            throw UserProfileSecurityException::inactiveUser($user->getId());
        }
    }

    /**
     * Garantit l'existence de toutes les entités nécessaires.
     * Crée automatiquement Profile et NotificationPreferences si absents.
     * Utilise la factory pour garantir une initialisation correcte.
     */
    private function ensureEntitiesExist(User $user): void
    {
        $needsFlush = false;
        
        if ($user->getProfile() === null) {
            $profile = $this->factory->createProfile($user);
            $user->setProfile($profile);
            $this->entityManager->persist($profile);
            $needsFlush = true;
            
            $this->logger->info('UserProfile created', ['user_id' => $user->getId()]);
        }
        
        if ($user->getNotificationPreferences() === null) {
            $preferences = $this->factory->createNotificationPreferences($user);
            $user->setNotificationPreferences($preferences);
            $this->entityManager->persist($preferences);
            $needsFlush = true;
            
            $this->logger->info('NotificationPreferences created', ['user_id' => $user->getId()]);
        }
        
        if ($needsFlush) {
            $this->entityManager->flush();
        }
    }

    /**
     * Récupère le profil depuis le cache ou le génère.
     * Cache pendant 30 minutes pour réduire les requêtes DB.
     */
    private function fetchCachedProfile(User $user): UserProfileOutput
    {
        $cacheKey = $this->buildCacheKey(self::CACHE_KEY_PROFILE, $user->getId());
        
        return $this->cache->get(
            $cacheKey,
            function (ItemInterface $item) use ($user): UserProfileOutput {
                $item->expiresAfter(self::CACHE_TTL);
                return $this->mapper->toApiOutput($user);
            }
        );
    }

    /**
     * Exécute la mise à jour du profil dans une transaction.
     * Garantit l'atomicité : soit tout réussit, soit rien n'est modifié.
     */
    private function executeProfileUpdate(User $user, UserProfileUpdateInput $input): User
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($user, $input): User {
                // Crée les entités manquantes AVANT les updaters
                $this->ensureEntitiesExist($user);
                
                // Applique les updaters par ordre de priorité
                $this->dispatchUpdaters($user, $input);
                
                $this->entityManager->persist($user);
                $this->invalidateCache($user->getId());
                
                $this->logger->info('User profile updated', [
                    'user_id' => $user->getId()
                ]);
                
                return $user;
            }
        );
    }

    /**
     * Applique tous les updaters supportant l'input donné.
     * Les updaters sont exécutés par ordre de priorité décroissante.
     * 
     * Ordre typique :
     * 1. BasicInfoUpdater (priorité 100)
     * 2. ProfileInfoUpdater (priorité 50)
     * 3. NotificationUpdater (priorité 10)
     */
    private function dispatchUpdaters(User $user, UserProfileUpdateInput $input): void
    {
        $sortedUpdaters = $this->getSortedUpdaters();
        
        foreach ($sortedUpdaters as $updater) {
            if ($updater->supports($input)) {
                $updater->update($user, $input);
            }
        }
    }

    /**
     * Trie les updaters par priorité décroissante.
     * Utilise l'iterable injecté par Symfony (tag UserProfileUpdaterInterface).
     */
    private function getSortedUpdaters(): array
    {
        $updaters = [];
        
        foreach ($this->updaters as $updater) {
            if ($updater instanceof UserProfileUpdaterInterface) {
                $updaters[] = $updater;
            }
        }
        
        usort($updaters, fn($a, $b) => $b->getPriority() <=> $a->getPriority());
        
        return $updaters;
    }

    /**
     * Invalide tous les caches liés à l'utilisateur.
     * Appelé après chaque mise à jour pour garantir la fraîcheur des données.
     */
    private function invalidateCache(int $userId): void
    {
        $keys = [
            $this->buildCacheKey(self::CACHE_KEY_PROFILE, $userId),
            $this->buildCacheKey(self::CACHE_KEY_BASIC, $userId),
        ];

        foreach ($keys as $key) {
            $this->cache->delete($key);
        }
        
        $this->logger->debug('User profile cache invalidated', ['user_id' => $userId]);
    }

    /**
     * Construit une clé de cache standardisée.
     */
    private function buildCacheKey(string $prefix, int $userId): string
    {
        return sprintf('%s_%d', $prefix, $userId);
    }
}
