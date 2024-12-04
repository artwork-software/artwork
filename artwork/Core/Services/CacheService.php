<?php

namespace Artwork\Core\Services;

use Artwork\Core\Str\StrService;
use Illuminate\Cache\CacheManager;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

final class CacheService
{
    private const TEN_SECONDS = 999999999;
    //@todo: restore: private const TEN_SECONDS = 10;

    public function __construct(
        private readonly StrService $strService,
        private readonly CacheManager $cacheManager,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function setValueAndGetCacheTokenValidForTenSeconds(mixed $value): mixed
    {
        $this->set($token = $this->strService->createToken(), $value, self::TEN_SECONDS);

        return $token;
    }

    /**
     * @throws Throwable
     */
    public function getValueByToken(string $token): mixed
    {
        return $this->get($token);
    }

    private function get(string $key, bool $delete = true): mixed
    {
        $value = $this->cacheManager->get($key);

        if ($delete) {
            //@todo: restore: $this->cacheManager->delete($key);
        }

        return $value;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function set(string $key, mixed $value, int $ttl = 0): void
    {
        $this->cacheManager->set($key, $value, $ttl);
    }
}
