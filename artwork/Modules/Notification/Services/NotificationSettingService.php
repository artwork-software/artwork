<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Repositories\NotificationSettingRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class NotificationSettingService
{
    public function __construct(private readonly NotificationSettingRepository $notificationSettingRepository)
    {
    }

    /**
     * @throws Throwable
     */
    public function create(array $attributes): NotificationSetting
    {
        $this->notificationSettingRepository->saveOrFail(
            ($notificationSetting = $this->notificationSettingRepository->getNewModelInstance())->fill($attributes)
        );
        return $notificationSetting;
    }

    /**
     * @return array<string, NotificationEnum>
     */
    public function getNotificationEnumCases(): array
    {
        return NotificationEnum::cases();
    }

    /**
     * Legt fehlende Einstellungen mit den Enum-Standards an; vorhandene bleiben unverändert
     * (früher setzte artwork:update bei jedem Lauf E-Mail/Push für 9 Typen wieder auf an).
     */
    public function ensureDefaultsForUser(User $user): int
    {
        $existingTypes = $user->notificationSettings()->pluck('type')
            ->map(fn (mixed $type): string => $type instanceof NotificationEnum ? $type->value : (string) $type)
            ->all();
        $created = 0;

        foreach (NotificationEnum::configurableCases() as $type) {
            if (in_array($type->value, $existingTypes, true)) {
                continue;
            }
            $this->create($this->defaultAttributes($type) + ['user_id' => $user->getKey()]);
            $created++;
        }

        return $created;
    }

    /**
     * Dasselbe für alle Konten in einem INSERT … SELECT je Typ (für artwork:update).
     */
    public function ensureDefaultsForAllUsers(): int
    {
        $created = 0;
        $now = now();

        foreach (NotificationEnum::configurableCases() as $type) {
            $attributes = $this->defaultAttributes($type);
            $created += DB::table('notification_settings')->insertUsing(
                [
                    'user_id', 'group_type', 'type', 'title', 'description', 'frequency',
                    'enabled_email', 'enabled_push', 'created_at', 'updated_at',
                ],
                DB::table('users')
                    ->selectRaw('users.id, ?, ?, ?, ?, ?, 1, 1, ?, ?', [
                        $attributes['group_type'],
                        $attributes['type'],
                        $attributes['title'],
                        $attributes['description'],
                        $attributes['frequency'],
                        $now,
                        $now,
                    ])
                    ->whereNotExists(function ($query) use ($type): void {
                        $query->selectRaw('1')
                            ->from('notification_settings')
                            ->whereColumn('notification_settings.user_id', 'users.id')
                            ->where('notification_settings.type', $type->value);
                    })
            );
        }

        return $created;
    }

    /**
     * Gruppe, Titel und Beschreibung je Typ aus dem Enum in alle Zeilen übernehmen.
     */
    public function syncTypeMetadata(): void
    {
        foreach (NotificationEnum::cases() as $type) {
            DB::table('notification_settings')->where('type', $type->value)->update([
                'group_type' => $type->groupType(),
                'title' => $type->title(),
                'description' => $type->description(),
            ]);
        }
    }

    /**
     * @return array{group_type: string, type: string, title: string, description: string, frequency: string}
     */
    private function defaultAttributes(NotificationEnum $type): array
    {
        return [
            'group_type' => $type->groupType(),
            'type' => $type->value,
            'title' => $type->title(),
            'description' => $type->description(),
            'frequency' => $type->defaultFrequency()->value,
        ];
    }

    public function getEnabledOfUser(int $userId): Collection
    {
        return $this->notificationSettingRepository->getEnabledOfUser($userId);
    }
}
