<?php

namespace App\Cart\Infrastructure\Cache;

use App\Cart\Domain\Entity\Cart;
use App\Account\Domain\Entity\User;
use App\Shared\Infrastructure\Service\Cache\CacheService;
use App\Shared\Infrastructure\Service\Cache\CacheKeyTrait;
use App\Cart\Domain\Service\Contract\CartCacheManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Gestionnaire de cache pour les paniers.
 * Cache les paniers en mémoire (Redis) pour optimiser les performances.
 */
final readonly class CartCacheManager implements CartCacheManagerInterface
{
    use CacheKeyTrait;

    private const CACHE_TTL = 1800; // 30 minutes
    private const CACHE_PREFIX_CART = 'cart_user';

    public function __construct(
        private CacheService $cache,
        private LoggerInterface $logger
    ) {}

    /**
     * Récupère un panier depuis le cache.
     * Retourne null si absent ou invalide.
     */
    public function getCartFromCache(User $user): ?Cart
    {
        $cacheKey = $this->getCartCacheKey($user->getId());
        
        $cachedData = $this->cache->remember($cacheKey, fn() => null, self::CACHE_TTL);
        
        if ($cachedData instanceof Cart) {
            $this->logger->debug('Cart retrieved from cache', [
                'user_id' => $user->getId(),
                'cart_id' => $cachedData->getId(),
            ]);
        }
        
        return $cachedData instanceof Cart ? $cachedData : null;
    }

    /**
     * Met un panier en cache.
     * TTL de 30 minutes.
     */
    public function cacheCart(Cart $cart): void
    {
        if (!$cart->getUser()) {
            $this->logger->warning('Attempted to cache cart without user', [
                'cart_id' => $cart->getId(),
            ]);
            return;
        }

        $cacheKey = $this->getCartCacheKey($cart->getUser()->getId());
        
        $this->cache->remember(
            $cacheKey,
            fn() => $cart,
            self::CACHE_TTL
        );
        
        $this->logger->info('Cart cached successfully', [
            'user_id' => $cart->getUser()->getId(),
            'cart_id' => $cart->getId(),
            'ttl' => self::CACHE_TTL,
        ]);
    }

    /**
     * Invalide le cache d'un utilisateur spécifique.
     */
    public function invalidateCartCache(User $user): void
    {
        $cacheKey = $this->getCartCacheKey($user->getId());
        
        $this->cache->forget($cacheKey);
        
        $this->logger->info('Cart cache invalidated for user', [
            'user_id' => $user->getId(),
        ]);
    }

    /**
     * Rafraîchit le cache d'un panier.
     * Invalide puis remet en cache avec les données à jour.
     */
    public function refreshCartCache(Cart $cart): void
    {
        if (!$cart->getUser()) {
            $this->logger->warning('Attempted to refresh cart cache without user', [
                'cart_id' => $cart->getId(),
            ]);
            return;
        }

        $userId = $cart->getUser()->getId();
        
        // Invalider puis recréer
        $this->invalidateCartCache($cart->getUser());
        $this->cacheCart($cart);
        
        $this->logger->info('Cart cache refreshed', [
            'user_id' => $userId,
            'cart_id' => $cart->getId(),
        ]);
    }

    /**
     * Invalide tout le cache des paniers.
     * Vide complètement le pool de cache.
     */
    public function invalidateAll(): void
    {
        $this->cache->clear();
        
        $this->logger->info('All cart caches invalidated (full cache cleared)');
    }

    /**
     * Génère la clé de cache pour un panier utilisateur.
     */
    private function getCartCacheKey(int $userId): string
    {
        return $this->buildCacheKey(self::CACHE_PREFIX_CART, $userId);
    }
}
