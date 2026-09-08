<?php

namespace Artwork\Modules\Shift\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Zeitraum-Deckel für Dienstplan-Exporte und -Listen (Schichtverlauf, Regelverstöße,
 * Änderungsübersicht): höchstens ein Jahr (366 Tage), sonst 422. Schützt vor unbegrenzten
 * Excel-Läufen über den gesamten Datenbestand.
 */
final class ExportPeriodLimit
{
    public const MAX_DAYS = 366;

    /**
     * Wirft eine ValidationException (422) auf $field, wenn der Zeitraum länger als MAX_DAYS ist.
     * Fehlende Grenzen (null/leer) werden nicht geprüft — Defaults setzen die Aufrufer.
     */
    public static function assertWithinLimit(
        CarbonInterface|string|null $from,
        CarbonInterface|string|null $to,
        string $field = 'date_to'
    ): void {
        if ($from === null || $from === '' || $to === null || $to === '') {
            return;
        }

        $start = $from instanceof CarbonInterface ? $from->copy()->startOfDay() : Carbon::parse($from)->startOfDay();
        $end = $to instanceof CarbonInterface ? $to->copy()->startOfDay() : Carbon::parse($to)->startOfDay();

        if ($start->diffInDays($end, false) > self::MAX_DAYS) {
            throw ValidationException::withMessages([
                $field => __('The period may cover at most one year.'),
            ]);
        }
    }
}
