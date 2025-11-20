<?php

namespace App\Shared\Infrastructure\Service\Cache;

use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Psr\Cache\CacheItemPoolInterface;

readonly class CacheService
{
    public function __construct(
        private CacheInterface $cache,
        private CacheItemPoolInterface $cachePool
    ) {}

    public function get(string $key): mixed
    {
        return $this->cache->get($key, fn() => null);
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->cache->get($key, function (ItemInterface $item) use ($value, $ttl) {
            $item->expiresAfter($ttl);
            return $value;
        });
    }

    public function delete(string $key): void
    {
        $this->cache->delete($key);
    }

    public function remember(string $key, callable $callback, int $ttl = 3600): mixed
    {
        return $this->cache->get($key, function (ItemInterface $item) use ($callback, $ttl) {
            $item->expiresAfter($ttl);
            return $callback();
        });
    }

    public function forget(string $key): void
    {
        $this->cache->delete($key);
    }

    /**
     * Vide complètement le cache
     */
    public function clear(): void
    {
        $this->cachePool->clear();
    }
}
