<?php

namespace Artwork\Modules\IndividualTimes\Support;

use Artwork\Modules\Permission\Enums\PermissionEnum;
use Artwork\Modules\User\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Regel für die EIGENEN individuellen Zeiten einer Person (Einzelzeiten und Zeitreihen):
 * - "can manage availability" oder "can plan shifts": eigene Zeiten an jedem Tag
 * - "can enter own individual times": eigene Zeiten ab heute (vergangene Tage bleiben den Verwaltungsrechten
 *   vorbehalten, weil sie bereits ins Stundenkonto gebucht sein können)
 * - sonst: keine eigenen Zeiten
 *
 * Fremde Zeiten regeln IndividualTimePolicy / IndividualTimeSeriesPolicy weiterhin über die Verwaltungsrechte.
 */
final class OwnIndividualTimeAccess
{
    public static function managesOwnTimesOnAnyDay(User $user): bool
    {
        return $user->canAny([
            PermissionEnum::AVAILABILITY_MANAGEMENT->value,
            PermissionEnum::SHIFT_PLANNER->value,
        ]);
    }

    public static function mayEnterOwnTimes(User $user): bool
    {
        return self::managesOwnTimesOnAnyDay($user)
            || $user->can(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value);
    }

    /**
     * Alle übergebenen Tage müssen für die Person bearbeitbar sein; null-Werte werden ignoriert.
     *
     * @param array<int, string|CarbonInterface|null> $dates
     */
    public static function mayEnterOwnTimesOn(User $user, array $dates): bool
    {
        if (self::managesOwnTimesOnAnyDay($user)) {
            return true;
        }

        if (!$user->can(PermissionEnum::CAN_ENTER_OWN_INDIVIDUAL_TIMES->value)) {
            return false;
        }

        $today = Carbon::today();
        foreach ($dates as $date) {
            if ($date === null || $date === '') {
                continue;
            }

            if (Carbon::parse($date)->startOfDay()->lt($today)) {
                return false;
            }
        }

        return true;
    }
}
