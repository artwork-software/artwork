<?php

namespace Artwork\Modules\WorkTime\Support;

use App\Settings\ShiftSettings;

/**
 * Globaler Schalter "Arbeitszeitberechnung" der Schichteinstellungen.
 *
 * Aus = artwork bucht keine Arbeitszeit (keine nächtliche Buchung, keine Überstunden,
 * keine manuellen Buchungen oder Auszahlungen) und zeigt weder Soll noch Stundenkonto.
 * Geplante Stunden aus Schichten bleiben sichtbar. Gespeicherte Daten bleiben erhalten;
 * Tage, an denen der Schalter aus war, werden beim Wiedereinschalten nicht nachgebucht.
 */
final class WorkTimeAccounting
{
    public static function isEnabled(): bool
    {
        return (bool) app(ShiftSettings::class)->work_time_accounting_enabled;
    }
}
