<?php

namespace Artwork\Modules\Notification\Services;

use Artwork\Modules\Event\Services\EventSettingsService;
use Artwork\Modules\ExternalAccess\Settings\ExternalAccessSettings;
use Artwork\Modules\ModuleSettings\Models\ModuleSettings;
use Artwork\Modules\Notification\Enums\NotificationEnum;
use Artwork\Modules\Notification\Enums\NotificationGroupEnum;
use Artwork\Modules\Notification\Models\NotificationSetting;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Policies\UserPolicy;

/**
 * Was eine Person in den Benachrichtigungseinstellungen sieht: nur Typen, die sie bekommen kann
 * (Modul aktiv, Planer-Typen nur mit Dienstplan-Sichtrecht, Externe nur bei aktivem Feature),
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
        if ($isExternal && !$this->externalAccessSettings->enabled) {
            return false;
        }

        return !$type->isForShiftPlanners() || UserPolicy::canViewForeignRoster($user);
    }

    /**
     * @return array<int, string>
     */
    public function visibleTypeValuesFor(User $user): array
    {
        $visible = array_filter(
            NotificationEnum::cases(),
            fn (NotificationEnum $type): bool => $this->isVisibleFor($type, $user)
        );

        return array_values(array_map(static fn (NotificationEnum $type): string => $type->value, $visible));
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
        $groups = [];

        foreach (NotificationGroupEnum::displayOrder() as $group) {
            $rows = [];
            foreach (NotificationEnum::cases() as $type) {
                $setting = $settings->get($type->value);
                if ($setting === null || $type->groupType() !== $group->value || !$this->isVisibleFor($type, $user)) {
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
