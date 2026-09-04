<?php

namespace Artwork\Modules\Vacation\Enums;

enum Vacation: string
{
    /** Urlaub (soll-wirksam) */
    case OFF_WORK = 'OFF_WORK';
    /** Nicht verfügbar (soll-neutral) */
    case NOT_AVAILABLE = 'NOT_AVAILABLE';
    /** Freier Tag laut Planung (ganz oder halb, siehe vacations.day_part) */
    case FREE_WORK = 'FREE_WORK';
    case AVAILABLE = 'AVAILABLE';

    /**
     * Abwesenheitsarten, die eine Person selbst im Verfügbarkeitskalender erfassen darf.
     * FREE_WORK ist Planungssache (Verfügbarkeitsstatus im Schichtplan).
     *
     * @return list<string>
     */
    public static function selfServiceAbsenceValues(): array
    {
        return [self::OFF_WORK->value, self::NOT_AVAILABLE->value];
    }
}
