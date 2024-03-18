<?php

namespace App\Enums;

enum NotificationFrequency: string
{
    case IMMEDIATELY = 'immediately';
    case DAILY       = 'daily';
    case WEEKLY_TWICE    = 'weekly_twice';
    case WEEKLY_ONCE     = 'weekly_once';

    public function title(): string
    {
        return match ($this) {
            self::IMMEDIATELY => "Immediately",
            self::DAILY => "Daily",
            self::WEEKLY_TWICE => "Twice a week",
            self::WEEKLY_ONCE => "Once a week",
        };
    }
}
