<?php

namespace Artwork\Modules\Shift\Services;

use Carbon\Carbon;

/**
 * Gesetzliche Mindestpause nach § 4 ArbZG.
 *
 * Bis einschließlich 6:00 h Arbeitszeit keine Pause, über 6 h 30 min, über 9 h 45 min.
 * Der Vergleich ist strikt: 6:00 → 0, 6:01 → 30, 9:00 → 30, 9:01 → 45.
 *
 * Spiegel der Frontend-Logik in resources/js/Composeables/useLegalBreak.ts —
 * beide Seiten müssen dieselben Grenzen verwenden.
 */
final class LegalBreakCalculator
{
    public const SIX_HOURS_IN_MINUTES = 360;
    public const NINE_HOURS_IN_MINUTES = 540;
    public const BREAK_OVER_SIX_HOURS = 30;
    public const BREAK_OVER_NINE_HOURS = 45;

    public static function minimumBreakMinutes(int $workMinutes): int
    {
        if ($workMinutes > self::NINE_HOURS_IN_MINUTES) {
            return self::BREAK_OVER_NINE_HOURS;
        }

        if ($workMinutes > self::SIX_HOURS_IN_MINUTES) {
            return self::BREAK_OVER_SIX_HOURS;
        }

        return 0;
    }

    /**
     * Brutto-Arbeitsminuten zwischen zwei Uhrzeiten ("H:i" oder "H:i:s").
     * Liegt das Ende vor oder auf dem Start, wird eine Über-Mitternacht-Schicht
     * angenommen (Ende + 1 Tag). Ungültige Eingaben ergeben 0.
     */
    public static function workMinutesBetween(?string $start, ?string $end): int
    {
        $startMinutes = self::parseTimeToMinutes($start);
        $endMinutes = self::parseTimeToMinutes($end);

        if ($startMinutes === null || $endMinutes === null) {
            return 0;
        }

        $diff = $endMinutes - $startMinutes;
        if ($diff <= 0) {
            $diff += 24 * 60;
        }

        return max(0, $diff);
    }

    /**
     * Mindestpause direkt aus Start-/Endzeit.
     */
    public static function minimumBreakMinutesBetween(?string $start, ?string $end): int
    {
        return self::minimumBreakMinutes(self::workMinutesBetween($start, $end));
    }

    /**
     * Liefert den übergebenen Wert, wenn er gesetzt ist, sonst die gesetzliche Mindestpause.
     * Ein explizit übergebener Wert (auch 0) wird nie überschrieben.
     */
    public static function resolveBreakMinutes(int|string|null $breakMinutes, ?string $start, ?string $end): int
    {
        if ($breakMinutes !== null && $breakMinutes !== '') {
            return (int) $breakMinutes;
        }

        return self::minimumBreakMinutesBetween($start, $end);
    }

    private static function parseTimeToMinutes(?string $time): ?int
    {
        if ($time === null || trim($time) === '') {
            return null;
        }

        $time = trim($time);

        // Volle Datums-/Zeitangaben (z.B. "2026-05-06 22:00:00") auf die Uhrzeit reduzieren.
        if (!preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $time)) {
            try {
                $time = Carbon::parse($time)->format('H:i');
            } catch (\Throwable) {
                return null;
            }
        }

        [$hours, $minutes] = array_map('intval', array_slice(explode(':', $time), 0, 2));

        if ($hours < 0 || $hours > 47 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return $hours * 60 + $minutes;
    }
}
