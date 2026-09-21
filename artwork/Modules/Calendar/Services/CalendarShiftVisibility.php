<?php

namespace Artwork\Modules\Calendar\Services;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Artwork\Modules\User\Models\UserCalendarSettings;
use Artwork\Modules\User\Models\UserDailyViewCalendarSettings;

/**
 * Schicht-Karten im Kalender ("Schichten anzeigen", work_shifts) erscheinen nur mit Dienstplan-Sichtrecht
 * ("can view shift plan" oder "can plan shifts"); das gespeicherte Setting allein reicht nicht. Ohne Recht
 * liefert der Kalender keinen Schicht-Payload, der Schalter fehlt im Dialog und ein gesendeter Wert wird
 * beim Speichern auf false gezwungen. Spiegel im Frontend: BaseCalendar.vue und CalendarSettingsCatalog.js.
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
