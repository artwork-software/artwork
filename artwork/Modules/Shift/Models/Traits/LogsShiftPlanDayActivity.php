<?php

namespace Artwork\Modules\Shift\Models\Traits;

use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Tageseinträge einer Person im Dienstplan (Abwesenheit, Verfügbarkeit, Notiz) erscheinen im
 * Schichtverlauf als eigene Vorgänge. Ergänzt jeden Activity-Eintrag (Spatie LogsActivity) um:
 *  - employable_type / employable_id: die betroffene Person (gleiche Schlüssel wie bei Schicht-
 *    Zuweisungen, damit der Personenfilter des Verlaufs greift)
 *  - worker_day: der Tag (Y-m-d)
 *  - day_entry: Art und Inhalt zum Zeitpunkt des Eintrags (auch nach dem Löschen lesbar)
 *
 * Wer und wann kommt wie bei Schichten aus causer und created_at des Activity-Eintrags.
 */
trait LogsShiftPlanDayActivity
{
    /** Art des Tageseintrags: absence | availability | note */
    abstract protected function shiftHistoryDayEntryKind(): string;

    /** Morph-Spalten der Person, z. B. ['vacationer_type', 'vacationer_id'] */
    abstract protected function shiftHistoryPersonColumns(): array;

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur von Spatie (tapActivity)
    public function tapActivity(Activity $activity, string $eventName): void
    {
        [$typeColumn, $idColumn] = $this->shiftHistoryPersonColumns();
        // Rohwerte (aktueller Stand; Uhrzeiten ohne datetime-Cast mit Tagesdatum)
        $raw = $this->getAttributes();
        $date = $raw['date'] ?? null;

        $activity->properties = $activity->properties->merge([
            'employable_type' => $this->getAttribute($typeColumn),
            'employable_id' => $this->getAttribute($idColumn) !== null ? (int) $this->getAttribute($idColumn) : null,
            'worker_day' => $date ? Carbon::parse($date)->toDateString() : null,
            'day_entry' => [
                'kind' => $this->shiftHistoryDayEntryKind(),
                'type' => $this->getAttribute('type'),
                'full_day' => $this->getAttribute('full_day') !== null ? (bool) $this->getAttribute('full_day') : null,
                'day_part' => $this->getAttribute('day_part'),
                'start_time' => self::shiftHistoryTime($raw['start_time'] ?? null),
                'end_time' => self::shiftHistoryTime($raw['end_time'] ?? null),
                'comment' => $this->getAttribute('comment'),
            ],
        ]);
    }

    private static function shiftHistoryTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }
        // "08:00:00" bzw. "2026-11-25 08:00:00"
        if (preg_match('/(\d{2}:\d{2})(:\d{2})?$/', (string) $value, $match)) {
            return $match[1];
        }

        return null;
    }
}
