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
            self::IMMEDIATELY => "Sofort",
            self::DAILY => "Täglich",
            self::WEEKLY_TWICE => "2x Wöchentlich",
            self::WEEKLY_ONCE => "1x Wöchentlich",
        };
    }
}
