<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class WorkingHourCacheService
{
    private const TTL = 604800; // 7 Tage
    private const PREFIX = 'working_hours:';

    public function getWeeklyData(string $type, int $id, int $year, int $week): ?array
    {
        return Cache::get($this->key($type, $id, $year, $week));
    }

    public function setWeeklyData(string $type, int $id, int $year, int $week, array $data): void
    {
        Cache::put($this->key($type, $id, $year, $week), $data, self::TTL);
    }

    public function forgetForEntity(string $type, int $id): void
    {
        $pattern = self::PREFIX . "{$type}:{$id}:*";
        $this->deleteByPattern($pattern);
    }

    public function forgetForShift(Shift $shift): void
    {
        $shift->loadMissing(['users', 'freelancer', 'serviceProvider']);

        foreach ($shift->users as $user) {
            $this->forgetForEntity('user', $user->id);
        }

        foreach ($shift->freelancer as $freelancer) {
            $this->forgetForEntity('freelancer', $freelancer->id);
        }

        foreach ($shift->serviceProvider as $serviceProvider) {
            $this->forgetForEntity('service_provider', $serviceProvider->id);
        }
    }

    public function forgetAll(): void
    {
        $this->deleteByPattern(self::PREFIX . '*');
    }

    public static function entityType(User|Freelancer|ServiceProvider $entity): string
    {
        return match (true) {
            $entity instanceof User => 'user',
            $entity instanceof Freelancer => 'freelancer',
            $entity instanceof ServiceProvider => 'service_provider',
        };
    }

    private function key(string $type, int $id, int $year, int $week): string
    {
        return self::PREFIX . "{$type}:{$id}:{$year}:{$week}";
    }

    private function deleteByPattern(string $pattern): void
    {
        $prefix = config('database.redis.options.prefix', '');
        $keys = Redis::keys($prefix . $pattern);

        if (!empty($keys)) {
            // Strip the prefix Redis added so we can delete them
            $keysWithoutPrefix = array_map(
                fn (string $key) => $prefix ? str_replace($prefix, '', $key) : $key,
                $keys
            );

            Redis::del(...$keysWithoutPrefix);
        }
    }
}
