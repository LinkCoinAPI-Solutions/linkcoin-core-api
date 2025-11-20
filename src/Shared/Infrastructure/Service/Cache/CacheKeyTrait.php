<?php
// src/Service/Cache/CacheKeyTrait.php

namespace App\Shared\Infrastructure\Service\Cache;

trait CacheKeyTrait
{
    private function buildCacheKey(string $prefix, array|string|int $params = []): string
    {
        if (!is_array($params)) {
            $params = [$params];
        }

        return sprintf(
            '%s_%s',
            $prefix,
            md5(json_encode($params))
        );
    }
}
