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
}
