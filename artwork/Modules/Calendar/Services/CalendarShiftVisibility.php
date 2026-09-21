<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;

/**
 * Schicht-Karten im Kalender/Planungskalender ("Schichten anzeigen", Spalte work_shifts auf
 * user_calendar_settings bzw. user_daily_view_calendar_settings).
 *
 * Produktentscheidung (Sicherheits-Audit 21.09.2026): Schichten sind Dienstplan-Daten und
 * werden im Kalender nur eingeblendet, wenn die Person den Dienstplan sehen darf
 * ("can view shift plan" oder "can plan shifts"; Admins via Gate::before). Das gespeicherte
 * Setting allein reicht nicht — ohne Recht liefert der Kalender keinen Schicht-Payload,
 * der Schalter erscheint nicht im Anzeigeeinstellungs-Dialog und ein gesendeter Wert wird
 * beim Speichern auf false gezwungen (UserController::updateCalendarSettings).
 * Spiegel im Frontend: BaseCalendar.vue (Broadcast-Kanäle) und CalendarSettingsCatalog.js.
 */
final class CalendarShiftVisibility
{
    public static function userMayViewShifts(?User $user): bool
    {
        return $user !== null && $user->canAny([
            PermissionEnum::VIEW_SHIFT_PLAN->value,
            PermissionEnum::SHIFT_PLANNER->value,
        ]);
    }

    public static function isEnabled(
        ?User $user,
        null|UserCalendarSettings|UserDailyViewCalendarSettings $settings
    ): bool {
        return (bool) $settings?->work_shifts && self::userMayViewShifts($user);
    }
}
