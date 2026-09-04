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
    // v2: Wochenwerte kommen seit 09/2026 aus dem WorkTimeCalculationService (anderes Soll/Ist) —
    // neuer Präfix, damit alte Einträge (bis zu 7 Tage TTL) nicht weiter ausgeliefert werden.
    // v3: Wochen-Payload um planned_formatted/daily_target_formatted/difference_formatted ergänzt
    private const PREFIX = 'working_hours_v3:';

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
        $versionKey = $this->versionKey($type, $id);

        Cache::add($versionKey, 0, self::TTL);
        Cache::increment($versionKey);
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
        return self::PREFIX . "{$type}:{$id}:v" . $this->version($type, $id) . ":{$year}:{$week}";
    }

    private function version(string $type, int $id): int
    {
        return (int) Cache::get($this->versionKey($type, $id), 0);
    }

    private function versionKey(string $type, int $id): string
    {
        return self::PREFIX . "{$type}:{$id}:_version";
    }
}
