<?php

namespace Artwork\Modules\Notification\Enums;

use Carbon\CarbonInterface;

enum NotificationFrequencyEnum: string
{
    case IMMEDIATELY = 'Immediately';

    case DAILY = 'daily';

    case WEEKLY_TWICE = 'weekly_twice';

    case WEEKLY_ONCE = 'weekly_once';

    /**
     * Fällt die Zusammenfassung an diesem Tag an? Feste Wochentage statt „x Tage nach der letzten
     * Mail je Typ“ – so kommt pro Person höchstens eine Sammelmail am Tag, an festen Tagen.
     */
    public function isDueOn(CarbonInterface $day): bool
    {
        return match ($this) {
            self::IMMEDIATELY => false,
            self::DAILY => true,
            self::WEEKLY_TWICE => in_array(
                $day->dayOfWeekIso,
                [CarbonInterface::MONDAY, CarbonInterface::THURSDAY],
                true
            ),
            self::WEEKLY_ONCE => $day->dayOfWeekIso === CarbonInterface::MONDAY,
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::IMMEDIATELY => 'Immediately',
            self::DAILY => "Daily",
            self::WEEKLY_TWICE => "Twice a week",
            self::WEEKLY_ONCE => "Once a week",
        };
    }
}
