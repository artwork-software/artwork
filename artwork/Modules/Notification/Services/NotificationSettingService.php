<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationFrequencyEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Notification\Repositories\NotificationSettingRepository;
use Artwork\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class NotificationSettingService
{
    /** Konten je UPDATE beim Markieren des Rückstands (Länge der IN-Liste begrenzen) */
    private const BACKLOG_USER_CHUNK = 500;

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
            $this->markBacklogAsSummarised($type, fn ($query) => $query->where('notifiable_id', $user->getKey()));
            $this->create($this->defaultAttributes($type) + ['user_id' => $user->getKey()]);
            $created++;
        }

        return $created;
    }

    /**
     * Ungelesene Altmeldungen eines Typs, für den gerade erst eine Einstellung entsteht, gelten als
     * zusammengefasst – sonst schickt die erste Sammelmail den ganzen Rückstand (teils Monate alt).
     *
     * @param callable(\Illuminate\Database\Query\Builder): mixed $restrictRecipients
     */
    private function markBacklogAsSummarised(NotificationEnum $type, callable $restrictRecipients): void
    {
        $query = DB::table('notifications')
            ->where('notifiable_type', (new User())->getMorphClass())
            ->whereNull('read_at')
            ->where('sent_in_summary', false)
            ->whereRaw('JSON_VALID(data)')
            ->whereJsonContains('data->type', $type->value);
        $restrictRecipients($query);
        $query->update(['sent_in_summary' => true]);
    }

    /**
     * Dasselbe für alle Konten in einem INSERT … SELECT je Typ (für artwork:update). Der Rückstand
     * wird nur für Konten markiert, denen die Einstellung wirklich fehlt – vorher lief je Typ ein
     * UPDATE über die ganze notifications-Tabelle, auch wenn niemandem etwas fehlte.
     */
    public function ensureDefaultsForAllUsers(): int
    {
        $created = 0;
        $now = now();

        foreach (NotificationEnum::configurableCases() as $type) {
            $usersWithoutSetting = DB::table('users')
                ->whereNotExists(function ($query) use ($type): void {
                    $query->selectRaw('1')
                        ->from('notification_settings')
                        ->whereColumn('notification_settings.user_id', 'users.id')
                        ->where('notification_settings.type', $type->value);
                })
                ->pluck('users.id')
                ->all();
            if ($usersWithoutSetting === []) {
                continue;
            }

            $attributes = $this->defaultAttributes($type);
            foreach (array_chunk($usersWithoutSetting, self::BACKLOG_USER_CHUNK) as $userIds) {
                $this->markBacklogAsSummarised(
                    $type,
                    static fn ($query) => $query->whereIn('notifiable_id', $userIds)
                );
            }
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
     * Typen mit E-Mail an, davon die Sofort-Mail-Typen, und für Typen mit E-Mail aus der Zeitpunkt der
     * letzten Änderung – Stand vor einer Änderung für summariseBacklogAfterSettingsChange().
     *
     * @return array{email: array<int, string>, immediate: array<int, string>, emailOffSince: array<string, mixed>}
     */
    public function mailStateOf(User $user): array
    {
        $state = ['email' => [], 'immediate' => [], 'emailOffSince' => []];
        $settings = $user->notificationSettings()
            ->toBase()
            ->get(['type', 'frequency', 'enabled_email', 'email_disabled_at']);

        foreach ($settings as $setting) {
            $type = (string) $setting->type;
            if (!$setting->enabled_email) {
                $state['emailOffSince'][$type] = $setting->email_disabled_at;
                continue;
            }
            $state['email'][] = $type;
            if ($setting->frequency === NotificationFrequencyEnum::IMMEDIATELY->value) {
                $state['immediate'][] = $type;
            }
        }

        return $state;
    }

    /**
     * Nach einer Änderung (einzeln, Sammeländerung, „Standard wiederherstellen“) ungelesene Meldungen
     * als zusammengefasst markieren, die die nächste Sammelmail sonst (noch einmal) verschicken würde:
     * - Typen, die nicht mehr sofort gemailt werden – ihre Meldungen kamen schon per Mail,
     * - Typen, deren E-Mail gerade erst eingeschaltet wurde – aber nur Meldungen aus der Zeit ohne
     *   E-Mail (sonst käme der ganze Rückstand, teils Monate, in einer Mail). Was vor dem Ausschalten
     *   auf die Sammelmail wartete, bleibt drin; im Zweifel eher eine Meldung zu viel als eine zu wenig.
     *
     * @param array{
     *     email: array<int, string>,
     *     immediate: array<int, string>,
     *     emailOffSince: array<string, mixed>
     * } $stateBefore
     */
    public function summariseBacklogAfterSettingsChange(User $user, array $stateBefore): void
    {
        $this->syncEmailDisabledAt($user, $stateBefore['email']);
        $stateAfter = $this->mailStateOf($user);
        $markSince = [];
        foreach (array_diff($stateBefore['immediate'], $stateAfter['immediate']) as $type) {
            $markSince[$type] = null;
        }
        foreach (array_diff($stateAfter['email'], $stateBefore['email']) as $type) {
            if (!array_key_exists($type, $markSince)) {
                $markSince[$type] = $stateBefore['emailOffSince'][$type] ?? null;
            }
        }
        if ($markSince === []) {
            return;
        }

        $user->unreadNotifications()
            ->reorder()
            ->where('sent_in_summary', false)
            ->whereRaw('JSON_VALID(data)')
            ->where(function ($query) use ($markSince): void {
                foreach ($markSince as $type => $since) {
                    $query->orWhere(function ($typeQuery) use ($type, $since): void {
                        $typeQuery->whereJsonContains('data->type', $type)
                            ->when($since !== null, fn ($q) => $q->where('created_at', '>=', $since));
                    });
                }
            })
            ->update(['sent_in_summary' => true]);
    }

    /**
     * email_disabled_at nach jeder Änderung nachziehen – alle Schreibwege (einzeln, Sammeländerung,
     * Standard wiederherstellen) laufen über summariseBacklogAfterSettingsChange(). Bewusst ohne
     * updated_at, damit nichts anderes den Zeitpunkt verschiebt.
     *
     * @param array<int, string> $emailTypesBefore Typen, deren E-Mail vor der Änderung an war
     */
    private function syncEmailDisabledAt(User $user, array $emailTypesBefore): void
    {
        // nur echte Übergänge an → aus stempeln, nicht Zeilen, die schon vorher aus waren
        if ($emailTypesBefore !== []) {
            DB::table('notification_settings')
                ->where('user_id', $user->getKey())
                ->where('enabled_email', false)
                ->whereIn('type', $emailTypesBefore)
                ->update(['email_disabled_at' => now()]);
        }
        DB::table('notification_settings')
            ->where('user_id', $user->getKey())
            ->where('enabled_email', true)
            ->whereNotNull('email_disabled_at')
            ->update(['email_disabled_at' => null]);
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
