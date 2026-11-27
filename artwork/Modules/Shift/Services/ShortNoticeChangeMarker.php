<?php

namespace Artwork\Modules\Shift\Services;

use App\Settings\ShiftSettings;
use Artwork\Modules\IndividualTimes\Models\IndividualTime;
use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Artwork\Modules\Shift\Models\Shift;
use Carbon\Carbon;

/**
 * Markiert Änderungen an verbindlichen Diensten als kurzfristig (NV Bühne § 6 Abs. 2 und 3: Änderungen
 * sind bis 14 Uhr des Vortags mitzuteilen).
 *
 * Kurzfristig ist eine Änderung, wenn sie nach dem Stichzeitpunkt liegt (Vortag des Dienstbeginns zur
 * eingestellten Uhrzeit) und spätestens am Tag des Dienstes selbst erfolgt. Spätere Korrekturen an
 * bereits vergangenen Diensten (zum Beispiel Ist-Zeiten nachtragen) sind keine kurzfristigen
 * Planänderungen.
 *
 * Maßgeblich ist der Dienstbeginn VOR der Änderung. Wird ein Dienst vorverlegt, zählt der frühere der
 * beiden Zeitpunkte: Die Person erfährt dann kurzfristig von einem früheren Beginn.
 *
 * Die Markierung ist abschaltbar (ShiftSettings::short_notice_marking_enabled, Standard aus). Ohne
 * aktivierte Einstellung wird nichts markiert.
 */
class ShortNoticeChangeMarker
{
    public const DEFAULT_CUTOFF_TIME = '14:00';

    public function __construct(private readonly ShiftSettings $shiftSettings)
    {
    }

    public function isEnabled(): bool
    {
        return (bool) $this->shiftSettings->short_notice_marking_enabled;
    }

    public function cutoffTime(): string
    {
        $time = (string) $this->shiftSettings->short_notice_cutoff_time;

        return preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time) === 1 ? $time : self::DEFAULT_CUTOFF_TIME;
    }

    /**
     * Ob die Änderung zum Zeitpunkt $changedAt (Standard: changed_at des Eintrags, sonst jetzt)
     * kurzfristig ist.
     */
    public function isShortNotice(CommittedShiftChange $change, ?Carbon $changedAt = null): bool
    {
        if (! $this->isEnabled()) {
            return false;
        }

        $start = $this->referenceStart($change);
        if ($start === null) {
            return false;
        }

        $changedAt ??= $change->changed_at ? Carbon::parse($change->changed_at) : Carbon::now();
        $cutoff = $start->copy()->subDay()->setTimeFromTimeString($this->cutoffTime());

        return $changedAt->greaterThan($cutoff) && $changedAt->lessThanOrEqualTo($start->copy()->endOfDay());
    }

    /**
     * Dienstbeginn, an dem die Frist hängt: der Beginn vor der Änderung, bei einer Vorverlegung der
     * frühere neue Beginn. Null, wenn sich kein Beginn ermitteln lässt.
     */
    public function referenceStart(CommittedShiftChange $change): ?Carbon
    {
        $fieldChanges = is_array($change->field_changes) ? $change->field_changes : [];

        $candidates = $change->subject_type === IndividualTime::class
            ? $this->individualTimeStarts($fieldChanges)
            : $this->shiftStarts($change, $fieldChanges);

        $candidates = array_values(array_filter($candidates));
        if ($candidates === []) {
            return null;
        }

        usort($candidates, static fn (Carbon $a, Carbon $b): int => $a->getTimestamp() <=> $b->getTimestamp());

        return $candidates[0];
    }

    /**
     * @param array<string, mixed> $fieldChanges
     * @return list<Carbon|null>
     */
    private function individualTimeStarts(array $fieldChanges): array
    {
        $state = is_array($fieldChanges['_individual_time'] ?? null) ? $fieldChanges['_individual_time'] : [];
        $deleted = is_array($fieldChanges['_deleted'] ?? null) ? $fieldChanges['_deleted'] : [];

        $currentDate = $state['start_date'] ?? $deleted['start_date'] ?? null;
        $currentTime = ! empty($state['full_day'] ?? $deleted['full_day'] ?? false)
            ? null
            : ($state['start_time'] ?? $deleted['start_time'] ?? null);

        $oldDate = $this->oldValue($fieldChanges, 'start_date') ?? $currentDate;
        $oldTime = $this->oldValue($fieldChanges, 'start_time') ?? $currentTime;

        return [
            $this->combine($oldDate, $oldTime),
            $this->combine($currentDate, $currentTime),
        ];
    }

    /**
     * @param array<string, mixed> $fieldChanges
     * @return list<Carbon|null>
     */
    private function shiftStarts(CommittedShiftChange $change, array $fieldChanges): array
    {
        $deleted = is_array($fieldChanges['_deleted'] ?? null) ? $fieldChanges['_deleted'] : [];

        $shift = $change->shift_id
            ? Shift::query()
                ->withoutGlobalScopes()
                ->without(['craft', 'users', 'freelancer', 'serviceProvider', 'committedBy'])
                ->select('id', 'start_date', 'start')
                ->find($change->shift_id)
            : null;

        $shiftDate = $shift ? substr((string) $shift->getRawOriginal('start_date'), 0, 10) : null;
        $shiftTime = $shift ? (string) $shift->getRawOriginal('start') : null;

        // Stand nach der Änderung: Zeiten der Person (Pivot) vor den Schichtzeiten
        $currentDate = $this->newValue($fieldChanges, 'start_date') ?? $deleted['start_date'] ?? $shiftDate;
        $currentTime = $this->newValue($fieldChanges, 'start')
            ?? $this->newValue($fieldChanges, 'start_time')
            ?? $deleted['start_time']
            ?? $deleted['start']
            ?? $shiftTime;

        $oldDate = $this->oldValue($fieldChanges, 'start_date') ?? $currentDate;
        $oldTime = $this->oldValue($fieldChanges, 'start')
            ?? $this->oldValue($fieldChanges, 'start_time')
            ?? $currentTime;

        return [
            $this->combine($oldDate, $oldTime),
            $this->combine($currentDate, $currentTime),
        ];
    }

    /**
     * @param array<string, mixed> $fieldChanges
     */
    private function oldValue(array $fieldChanges, string $field): mixed
    {
        return $this->changeValue($fieldChanges, $field, 'old');
    }

    /**
     * @param array<string, mixed> $fieldChanges
     */
    private function newValue(array $fieldChanges, string $field): mixed
    {
        return $this->changeValue($fieldChanges, $field, 'new');
    }

    /**
     * @param array<string, mixed> $fieldChanges
     */
    private function changeValue(array $fieldChanges, string $field, string $side): mixed
    {
        $value = $fieldChanges[$field] ?? null;
        if (! is_array($value) || ! array_key_exists($side, $value)) {
            return null;
        }

        return $value[$side] === '' ? null : $value[$side];
    }

    /**
     * Datum und Uhrzeit zu einem Zeitpunkt in der App-Zeitzone zusammensetzen. Die Uhrzeit darf ein
     * vollständiger Zeitstempel sein (ShiftService schreibt bei Zeitänderungen "Y-m-d H:i:s"), dann
     * gilt dessen Datum. Ohne Uhrzeit gilt der Tagesbeginn.
     */
    private function combine(mixed $date, mixed $time): ?Carbon
    {
        $timezone = config('app.timezone', 'Europe/Berlin');

        try {
            if ($time instanceof \DateTimeInterface) {
                $time = Carbon::instance($time)->format('Y-m-d H:i:s');
            }

            if (is_string($time) && preg_match('/^\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}/', $time) === 1) {
                return Carbon::parse(substr(str_replace('T', ' ', $time), 0, 16), $timezone);
            }

            $dateKey = $this->dateKey($date);
            if ($dateKey === null) {
                return null;
            }

            $timeKey = is_string($time) && preg_match('/^\d{2}:\d{2}/', $time) === 1
                ? substr($time, 0, 5)
                : '00:00';

            return Carbon::parse($dateKey . ' ' . $timeKey, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    private function dateKey(mixed $date): ?string
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->setTimezone(config('app.timezone', 'Europe/Berlin'))->toDateString();
        }

        if (! is_string($date) || $date === '') {
            return null;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1) {
            return $date;
        }

        // Rohwerte mit Zeitanteil (getOriginal einer gelöschten Schicht) sind UTC-Zeitstempel eines
        // lokalen Tagesbeginns, wie in ShiftChangeRecorder::normalizeDateValue
        if (preg_match('/[TZ]/', $date) === 1) {
            return Carbon::parse($date, 'UTC')->setTimezone(config('app.timezone', 'Europe/Berlin'))->toDateString();
        }

        return substr($date, 0, 10);
    }
}
