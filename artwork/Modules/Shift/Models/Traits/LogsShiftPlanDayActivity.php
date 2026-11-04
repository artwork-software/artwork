<?php

namespace Artwork\Modules\Shift\Models\Traits;

use Artwork\Modules\Shift\Support\ShiftHistoryDayEntryPerson;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Tageseinträge einer Person im Dienstplan (Abwesenheit, Verfügbarkeit, Notiz, individuelle Zeit,
 * Ausgleichstag) erscheinen im Schichtverlauf als eigene Vorgänge. Ergänzt jeden Activity-Eintrag
 * (Spatie LogsActivity) um:
 *  - employable_type / employable_id: die betroffene Person (gleiche Schlüssel wie bei Schicht-
 *    Zuweisungen, damit der Personenfilter des Verlaufs greift)
 *  - person_name: Name der Person zum Zeitpunkt des Eintrags (Verlauf ohne Personenfilter)
 *  - worker_day / worker_day_end: erster und letzter betroffener Tag (Y-m-d)
 *  - day_entry: Art und Inhalt zum Zeitpunkt des Eintrags (auch nach dem Löschen lesbar)
 *
 * Wer und wann kommt wie bei Schichten aus causer und created_at des Activity-Eintrags.
 */
trait LogsShiftPlanDayActivity
{
    /** Art des Tageseintrags: absence | availability | note | individual_time | compensation */
    abstract protected function shiftHistoryDayEntryKind(): string;

    /**
     * Morph-Spalten der Person, z. B. ['vacationer_type', 'vacationer_id'].
     *
     * @return array{0: string, 1: string}
     */
    protected function shiftHistoryPersonColumns(): array
    {
        return ['employable_type', 'employable_id'];
    }

    /**
     * Betroffene Person als [Klasse, ID].
     *
     * @return array{0: ?string, 1: ?int}
     */
    protected function shiftHistoryPerson(): array
    {
        [$typeColumn, $idColumn] = $this->shiftHistoryPersonColumns();
        $id = $this->getAttribute($idColumn);

        return [$this->getAttribute($typeColumn), $id !== null ? (int) $id : null];
    }

    /**
     * Erster und letzter betroffener Tag (Y-m-d).
     *
     * @return array{0: ?string, 1: ?string}
     */
    protected function shiftHistoryDayRange(): array
    {
        $date = $this->getAttributes()['date'] ?? null;
        $day = $date ? Carbon::parse($date)->toDateString() : null;

        return [$day, $day];
    }

    /**
     * Inhalt des Tageseintrags (Stand zum Zeitpunkt des Vorgangs).
     *
     * @return array<string, mixed>
     */
    protected function shiftHistoryDayEntryData(): array
    {
        // Rohwerte (aktueller Stand; Uhrzeiten ohne datetime-Cast mit Tagesdatum)
        $raw = $this->getAttributes();

        return [
            'type' => $this->getAttribute('type'),
            'full_day' => $this->getAttribute('full_day') !== null ? (bool) $this->getAttribute('full_day') : null,
            'day_part' => $this->getAttribute('day_part'),
            'start_time' => self::shiftHistoryTime($raw['start_time'] ?? null),
            'end_time' => self::shiftHistoryTime($raw['end_time'] ?? null),
            'comment' => $this->getAttribute('comment'),
        ];
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter -- Signatur von Spatie (tapActivity)
    public function tapActivity(Activity $activity, string $eventName): void
    {
        [$personType, $personId] = $this->shiftHistoryPerson();
        [$firstDay, $lastDay] = $this->shiftHistoryDayRange();

        $activity->properties = $activity->properties->merge([
            'employable_type' => $personType,
            'employable_id' => $personId,
            'person_name' => ShiftHistoryDayEntryPerson::nameOf($personType, $personId),
            'worker_day' => $firstDay,
            'worker_day_end' => $lastDay !== $firstDay ? $lastDay : null,
            'day_entry' => ['kind' => $this->shiftHistoryDayEntryKind()] + $this->shiftHistoryDayEntryData(),
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
