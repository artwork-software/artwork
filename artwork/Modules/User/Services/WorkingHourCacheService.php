<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\Shift\Models\Shift;
use Artwork\Modules\User\Models\User;
use Illuminate\Support\Facades\Cache;

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
        $this->addToIndex($type, $id, $year, $week);
    }

    public function forgetForEntity(string $type, int $id): void
    {
        $index = Cache::get($this->indexKey($type, $id), []);

        foreach ($index as $yearWeek) {
            Cache::forget($this->key($type, $id, $yearWeek['y'], $yearWeek['w']));
        }

        Cache::forget($this->indexKey($type, $id));
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
        // forgetAll is only used for full resets — flushing is acceptable here
        Cache::flush();
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

    private function indexKey(string $type, int $id): string
    {
        return self::PREFIX . "{$type}:{$id}:_index";
    }

    private function addToIndex(string $type, int $id, int $year, int $week): void
    {
        $indexKey = $this->indexKey($type, $id);
        $index = Cache::get($indexKey, []);

        // Check if already tracked
        foreach ($index as $entry) {
            if ($entry['y'] === $year && $entry['w'] === $week) {
                return;
            }
        }

        $index[] = ['y' => $year, 'w' => $week];
        Cache::put($indexKey, $index, self::TTL);
    }
}
