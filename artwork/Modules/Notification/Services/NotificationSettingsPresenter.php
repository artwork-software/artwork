<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\Shift\Models\ShiftCommitWorkflowUser;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Policies\UserPolicy;
use Illuminate\Support\Facades\DB;

/**
 * Was eine Person in den Benachrichtigungseinstellungen sieht: nur Typen, die sie bekommen kann
 * (Modul aktiv, Planer-Typen nur im tatsächlichen Empfängerkreis, Externe nur bei aktivem Feature),
 * Texte aus dem Enum (nicht die beim Anlegen kopierten), feste Reihenfolge wie im Center.
 */
readonly class NotificationSettingsPresenter
{
    /** Bei „Termine immer direkt buchbar“ gibt es keine Anfragen/Prüfungen */
    private const REQUEST_TYPES = [
        NotificationEnum::NOTIFICATION_ROOM_REQUEST,
        NotificationEnum::NOTIFICATION_UPSERT_ROOM_REQUEST,
        NotificationEnum::NOTIFICATION_ROOM_ANSWER,
        NotificationEnum::NOTIFICATION_EVENT_VERIFICATION_REQUESTS,
    ];

    public function __construct(
        private ModuleSettings $moduleSettings,
        private ExternalAccessSettings $externalAccessSettings,
        private EventSettingsService $eventSettingsService,
    ) {
    }

    public function isVisibleFor(NotificationEnum $type, User $user): bool
    {
        return in_array($type->value, $this->visibleTypeValuesFor($user), true);
    }

    /**
     * @return array<int, string>
     */
    public function visibleTypeValuesFor(User $user): array
    {
        $available = array_values(array_filter(
            NotificationEnum::cases(),
            fn (NotificationEnum $type): bool => $this->isAvailable($type)
        ));
        $plannerTypes = array_filter(
            $available,
            static fn (NotificationEnum $type): bool => $type->isForShiftPlanners()
        );
        $receivablePlannerTypes = $plannerTypes === [] ? [] : $this->receivablePlannerTypeValues($user);

        $visible = array_filter(
            $available,
            static fn (NotificationEnum $type): bool => !$type->isForShiftPlanners()
                || in_array($type->value, $receivablePlannerTypes, true)
        );

        return array_values(array_map(static fn (NotificationEnum $type): string => $type->value, $visible));
    }

    /**
     * Typ fällt in dieser Instanz überhaupt an (konfigurierbar, Modul/Feature aktiv).
     */
    private function isAvailable(NotificationEnum $type): bool
    {
        if (!$type->isConfigurable()) {
            return false;
        }
        if (in_array($type, self::REQUEST_TYPES, true) && $this->eventSettingsService->alwaysDirectBooking()) {
            return false;
        }
        $module = $type->module();
        if ($module !== null && !($this->moduleSettings->{$module} ?? true)) {
            return false;
        }
        $isExternal = $type->groupType() === NotificationGroupEnum::EXTERNAL_ACCESS->value;

        return !$isExternal || $this->externalAccessSettings->enabled;
    }

    /**
     * Planer-Typen, die diese Person tatsächlich bekommen kann – sonst kämen Mails/Sammelmails, die sie
     * weder sieht noch abbestellen kann (und „Alle E-Mails aus“ würde sie überspringen):
     * - Dienstplan-Sichtrecht (UserPolicy::canViewForeignRoster): alle,
     * - Gewerke-Planer*in (craft_users): alle – Regelverstöße, Zu-/Absagen, offene Bedarfe,
     *   Festschreibe-Anfragen und Arbeitszeitanträge ihres Gewerks,
     * - Gewerkeleitung oder Freigabe-Person des Festschreibe-Workflows: Festschreibe-Anfragen,
     * - Projektleitung: offene Bedarfe (NotifyCraftIfShiftDeadlineReached),
     * - und jeder Typ, von dem schon Meldungen vorliegen (künftige Absender ohne Rollenpflege hier).
     *
     * @return array<int, string>
     */
    private function receivablePlannerTypeValues(User $user): array
    {
        $plannerTypes = array_values(array_filter(
            NotificationEnum::cases(),
            static fn (NotificationEnum $type): bool => $type->isForShiftPlanners()
        ));
        $plannerTypeValues = array_map(static fn (NotificationEnum $type): string => $type->value, $plannerTypes);

        if (UserPolicy::canViewForeignRoster($user) || $user->crafts()->exists()) {
            return $plannerTypeValues;
        }

        $receivable = $this->receivedTypeValues($user, $plannerTypes);
        if (
            ShiftCommitWorkflowUser::query()->where('user_id', $user->getKey())->exists()
            || DB::table('craft_managers')
                ->where('craft_manager_type', $user->getMorphClass())
                ->where('craft_manager_id', $user->getKey())
                ->exists()
        ) {
            $receivable[] = NotificationEnum::NOTIFICATION_NEW_SHIFT_COMMIT_WORKFLOW_REQUEST->value;
        }
        if ($user->projects()->wherePivot('is_manager', true)->exists()) {
            $receivable[] = NotificationEnum::NOTIFICATION_SHIFT_OPEN_DEMAND->value;
        }

        return array_values(array_intersect($plannerTypeValues, $receivable));
    }

    /**
     * Typen unter $types, von denen die Person (gelesene oder ungelesene) Meldungen hat. Läuft über den
     * Index Empfänger/Gruppe/gelesen und nur über die Gruppen dieser Typen.
     *
     * @param array<int, NotificationEnum> $types
     * @return array<int, string>
     */
    private function receivedTypeValues(User $user, array $types): array
    {
        $groups = array_values(array_unique(array_map(
            static fn (NotificationEnum $type): string => $type->groupType(),
            $types
        )));

        return $user->notifications()
            ->reorder()
            ->whereIn('groupType', $groups)
            ->whereRaw('JSON_VALID(data)')
            ->distinct()
            ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) as notification_type")
            ->pluck('notification_type')
            ->filter(static fn (mixed $type): bool => is_string($type))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     title: string,
     *     description: string,
     *     settings: array<int, array<string, mixed>>
     * }>
     */
    public function groupsFor(User $user): array
    {
        $settings = $user->notificationSettings()->get()->keyBy(
            static fn (NotificationSetting $setting): string => $setting->type->value
        );
        $visibleTypes = $this->visibleTypeValuesFor($user);
        $groups = [];

        foreach (NotificationGroupEnum::displayOrder() as $group) {
            $rows = [];
            foreach (NotificationEnum::cases() as $type) {
                $setting = $settings->get($type->value);
                if (
                    $setting === null
                    || $type->groupType() !== $group->value
                    || !in_array($type->value, $visibleTypes, true)
                ) {
                    continue;
                }
                $rows[] = [
                    'id' => $setting->id,
                    'type' => $type->value,
                    'title' => $type->title(),
                    'description' => $type->description(),
                    'enabled_email' => (bool) $setting->enabled_email,
                    'enabled_push' => (bool) $setting->enabled_push,
                    'frequency' => $setting->frequency->value,
                    'default_frequency' => $type->defaultFrequency()->value,
                ];
            }
            if ($rows !== []) {
                $groups[] = [
                    'key' => $group->value,
                    'title' => $group->title(),
                    'description' => $group->description(),
                    'settings' => $rows,
                ];
            }
        }

        return $groups;
    }
}
