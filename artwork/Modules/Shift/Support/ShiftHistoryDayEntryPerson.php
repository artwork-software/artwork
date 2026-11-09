<?php

namespace Artwork\Modules\Shift\Support;

use Artwork\Modules\Freelancer\Models\Freelancer;
use Artwork\Modules\ServiceProvider\Models\ServiceProvider;
use Artwork\Modules\User\Models\User;

/**
 * Name der Person eines Tageseintrags im Schichtverlauf. Neue Einträge tragen ihn in
 * properties.person_name (LogsShiftPlanDayActivity); ältere werden über employable_* bzw. die
 * geloggten Morph-Spalten aufgelöst. Gecacht pro Request, weil Verlauf und Export viele Einträge
 * derselben Personen zeigen.
 */
class ShiftHistoryDayEntryPerson
{
    /** Morph-Spalten älterer Einträge in properties.attributes/old */
    private const LEGACY_PERSON_PREFIXES = ['vacationer', 'available', 'commentable', 'timeable'];

    /** @var array<string, ?string> */
    private static array $names = [];

    public static function nameOf(?string $type, ?int $id): ?string
    {
        if ($type === null || $id === null || $id <= 0) {
            return null;
        }
        if (! in_array($type, [User::class, Freelancer::class, ServiceProvider::class], true)) {
            return null;
        }

        $key = $type . '-' . $id;
        if (! array_key_exists($key, self::$names)) {
            $query = $type::query();
            if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($type), true)) {
                // entspricht withTrashed(): auch gelöschte Personen behalten ihren Namen im Verlauf
                $query->withoutGlobalScope(\Illuminate\Database\Eloquent\SoftDeletingScope::class);
            }
            $model = $query->find($id);
            self::$names[$key] = $model === null ? null : self::displayName($model);
        }

        return self::$names[$key];
    }

    /**
     * @param array<string, mixed> $properties
     */
    public static function nameFromProperties(array $properties): ?string
    {
        $stored = $properties['person_name'] ?? null;
        if (is_string($stored) && $stored !== '') {
            return $stored;
        }

        $type = $properties['employable_type'] ?? null;
        $id = isset($properties['employable_id']) ? (int) $properties['employable_id'] : null;
        if (is_string($type) && $id) {
            return self::nameOf($type, $id);
        }

        foreach (['attributes', 'old'] as $section) {
            $data = (array) ($properties[$section] ?? []);
            foreach (self::LEGACY_PERSON_PREFIXES as $prefix) {
                $legacyType = $data[$prefix . '_type'] ?? null;
                $legacyId = isset($data[$prefix . '_id']) ? (int) $data[$prefix . '_id'] : null;
                if (is_string($legacyType) && $legacyId) {
                    return self::nameOf($legacyType, $legacyId);
                }
            }
            if (isset($data['user_id']) && (int) $data['user_id'] > 0) {
                return self::nameOf(User::class, (int) $data['user_id']);
            }
        }

        return null;
    }

    /** Cache leeren (Tests) */
    public static function flush(): void
    {
        self::$names = [];
    }

    private static function displayName(User|Freelancer|ServiceProvider $model): ?string
    {
        if ($model instanceof ServiceProvider) {
            return $model->provider_name ?? null;
        }

        $name = trim((string) ($model->first_name ?? '') . ' ' . (string) ($model->last_name ?? ''));

        return $name !== '' ? $name : null;
    }
}
