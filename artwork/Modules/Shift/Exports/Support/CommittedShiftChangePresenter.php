<?php

namespace Artwork\Modules\Shift\Exports\Support;

use Artwork\Modules\Shift\Models\CommittedShiftChange;
use Carbon\Carbon;

/**
 * Aufbereitung einer Änderung nach Festschreibung (committed_shift_changes) für Änderungsübersicht
 * (ShiftPlanRequestController::changes) UND Excel-Export — eine Quelle für Vorher/Nachher-Labels und
 * die Beschreibung der Änderungsart (Gegenstück zu describeChange() in Changes.vue).
 */
final class CommittedShiftChangePresenter
{
    /**
     * Frontend-Shape einer Änderung (Zeile der Änderungsübersicht).
     *
     * @return array<string, mixed>
     */
    public static function present(CommittedShiftChange $change): array
    {
        $fieldChanges = $change->field_changes ?? [];

        $assignment = $fieldChanges['assignment'] ?? null;

        $affectedName       = null;
        $profilePictureUrl  = null;
        $beforeLabel        = null;
        $afterLabel         = null;

        // Fall 1: User-Zuweisung / Entfernen -> Daten aus assignment
        if ($assignment) {
            $affectedName      = $assignment['user_name']           ?? null;
            $profilePictureUrl = $assignment['profile_picture_url'] ?? null;
            $beforeLabel       = $assignment['before_label']        ?? null;
            $afterLabel        = $assignment['after_label']         ?? null;
        } else {
            // Fall 2: reine Schicht-Änderung (start/end/break …)
            $shift = $change->shift;

            if ($shift) {
                $date = optional($shift->start_date)?->format('d.m.Y')
                    ?? optional($shift->end_date)?->format('d.m.Y');

                // Zeiten zuerst aus field_changes lesen, sonst auf aktuelle Shift-Werte zurückfallen;
                // alles einheitlich auf "HH:MM" normalisieren (bzw. null).
                $beforeStart = self::toTime($fieldChanges['start']['old'] ?? null) ?? self::toTime($shift->start);
                $beforeEnd   = self::toTime($fieldChanges['end']['old']   ?? null) ?? self::toTime($shift->end);
                $afterStart  = self::toTime($fieldChanges['start']['new'] ?? null) ?? self::toTime($shift->start);
                $afterEnd    = self::toTime($fieldChanges['end']['new']   ?? null) ?? self::toTime($shift->end);

                $beforeLabel = self::timeLabel($date, $beforeStart, $beforeEnd);
                $afterLabel  = self::timeLabel($date, $afterStart, $afterEnd);

                // „Betroffene Entität“ für reine Schicht-Änderung sinnvoll benennen
                $craftAbbr = optional($shift->craft)->abbreviation;
                $affectedName = $craftAbbr
                    ? sprintf('%s – %s', $craftAbbr, $date)
                    : ($date ?: null);
            }
        }

        return [
            'id'                     => $change->id,
            'change_type'            => $change->change_type,

            'affected_name'          => $affectedName,
            'profile_picture_url'    => $profilePictureUrl,

            'before_label'           => $beforeLabel,
            'after_label'            => $afterLabel,

            'changed_by_name'        => optional($change->changedBy)->full_name,
            'changed_at'             => optional($change->changed_at)?->toIso8601String(),
            'changed_at_formatted'   => optional($change->changed_at)?->format('d.m.Y H:i'),

            'acknowledged_at'        => optional($change->acknowledged_at)?->toIso8601String(),
            'acknowledged'           => ! is_null($change->acknowledged_at),

            'field_changes'          => $fieldChanges,
        ];
    }

    /**
     * Art der Änderung als Text (wie describeChange() in Changes.vue), z. B.
     * "Nutzer*in Anna Muster aus Schicht entfernt" oder "Schicht geändert (Startzeit, Endzeit)".
     *
     * @param array<string, mixed> $presented Ergebnis von present()
     */
    public static function describe(array $presented, ?string $locale = null): string
    {
        $fieldChanges = is_array($presented['field_changes'] ?? null) ? $presented['field_changes'] : [];
        $labels = [];
        foreach (array_keys($fieldChanges) as $key) {
            if ($key === '_initial' || $key === 'assignment') {
                continue;
            }
            $labels[] = self::fieldLabel((string) $key, $locale);
        }
        $fieldList = implode(', ', $labels);
        $affected = $presented['affected_name'] ?? null;

        return match ($presented['change_type'] ?? null) {
            'user_removed_from_shift' => $affected
                ? self::tr('User {0} removed from shift', [$affected], $locale)
                : self::tr('User removed from shift', [], $locale),
            'user_added_to_shift' => $affected
                ? self::tr('User {0} added to shift', [$affected], $locale)
                : self::tr('User added to shift', [], $locale),
            'updated' => $fieldList !== ''
                ? self::tr('Shift updated ({0})', [$fieldList], $locale)
                : self::tr('Shift updated', [], $locale),
            'revert' => $fieldList !== ''
                ? self::tr('Change reverted ({0})', [$fieldList], $locale)
                : self::tr('Change reverted', [], $locale),
            default => self::tr((string) ($presented['change_type'] ?? ''), [], $locale),
        };
    }

    private static function fieldLabel(string $key, ?string $locale): string
    {
        return match ($key) {
            'start' => __('Start time', [], $locale),
            'end' => __('End time', [], $locale),
            'break_minutes' => __('Break', [], $locale),
            'qualifications' => __('Qualifications', [], $locale),
            'global_qualifications' => __('Global qualifications', [], $locale),
            'assignment' => __('Assignment', [], $locale),
            'individual_time' => __('Individual working time', [], $locale),
            'worker_short_description' => __('Short description', [], $locale),
            default => $key,
        };
    }

    /**
     * @param array<int, string> $values Platzhalter {0}, {1} … (vue-i18n-Listenform)
     */
    private static function tr(string $key, array $values, ?string $locale): string
    {
        $text = __($key, [], $locale);
        foreach ($values as $index => $value) {
            $text = str_replace('{' . $index . '}', (string) $value, $text);
        }

        return $text;
    }

    private static function timeLabel(?string $date, ?string $start, ?string $end): ?string
    {
        if ($date && $start && $end) {
            return sprintf('%s %s - %s', $date, $start, $end);
        }
        if ($start && $end) {
            return sprintf('%s - %s', $start, $end);
        }

        return $end ?: null;
    }

    /**
     * Werte aus field_changes können als volle Datetime ("2026-06-20 14:30:00"), als "HH:MM:SS", als
     * "HH:MM" oder als Platzhalter ("null"/leer) vorliegen — einheitlich auf "HH:MM" (bzw. null).
     */
    private static function toTime(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }
        $str = (string) $value;
        if (preg_match('/^(\d{1,2}):(\d{2})/', $str, $m)) {
            return sprintf('%02d:%s', (int) $m[1], $m[2]);
        }
        try {
            return Carbon::parse($str)->format('H:i');
        } catch (\Throwable) {
            return null;
        }
    }
}
