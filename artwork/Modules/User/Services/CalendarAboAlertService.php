<?php

namespace Artwork\Modules\User\Services;

use Artwork\Modules\User\Models\UserCalendarAbo;
use Artwork\Modules\User\Models\UserShiftCalendarAbo;
use Carbon\Carbon;
use Spatie\IcalendarGenerator\Components\Event;

/**
 * Erinnerung vor Beginn für Termin- und Schicht-Abo: Zeitpunkt aus Vorlauf und Einheit, Text in der Sprache
 * der Abo-Inhaber*in mit übersetzter Einheit in Singular bzw. Plural.
 */
readonly class CalendarAboAlertService
{
    /**
     * @var array<string, string> Einheit => Übersetzungsschlüssel (trans_choice)
     */
    private const TEXT_KEYS = [
        'minutes' => '{0} :title starts now.|{1} :title starts in one minute.|[2,*] :title starts in :count minutes.',
        'hours' => '{0} :title starts now.|{1} :title starts in one hour.|[2,*] :title starts in :count hours.',
        'days' => '{0} :title starts now.|{1} :title starts in one day.|[2,*] :title starts in :count days.',
    ];

    /**
     * Hängt die Erinnerung an, wenn das Abo sie einschaltet. Bei unbekannter Einheit gibt es keine Erinnerung
     * (vorher lag sie auf dem Beginn).
     */
    public function addStartAlert(
        Event $calendarEvent,
        UserCalendarAbo|UserShiftCalendarAbo $calendarAbo,
        Carbon $eventStart,
        string $title
    ): void {
        if (!$calendarAbo->enable_notification) {
            return;
        }

        $unit = (string) $calendarAbo->notification_time_unit;
        if (!array_key_exists($unit, self::TEXT_KEYS)) {
            return;
        }

        $notificationTime = max(0, (int) $calendarAbo->notification_time);
        $alertTime = match ($unit) {
            'minutes' => $eventStart->copy()->subMinutes($notificationTime),
            'hours' => $eventStart->copy()->subHours($notificationTime),
            'days' => $eventStart->copy()->subDays($notificationTime),
        };

        $calendarEvent->alertAt(
            $alertTime,
            trans_choice(
                self::TEXT_KEYS[$unit],
                $notificationTime,
                ['title' => $title],
                $calendarAbo->user?->preferredLocale() ?? app()->getLocale()
            )
        );
    }
}
