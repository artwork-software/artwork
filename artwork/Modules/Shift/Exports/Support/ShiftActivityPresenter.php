<?php

namespace Artwork\Modules\Shift\Exports\Support;

use Artwork\Modules\Availability\Models\Availability;
use Artwork\Modules\Shift\Models\ShiftPlanComment;
use Artwork\Modules\Vacation\Models\Vacation;
use Carbon\Carbon;
use Spatie\Activitylog\Models\Activity;

/**
 * Serverseitige Aufbereitung eines Schichtverlauf-Eintrags (Spatie activity_log, log_name 'shift') für
 * den Excel-Export — Gegenstück zu ShiftHistoryModal.vue / useShiftPlanRequest.js: Nachricht aus
 * translation_key + Platzhaltern ({0}, {1} …), Aktions-Kategorie, Schichtdaten (Snapshot → Live-Schicht
 * → properties.old) und Vorher/Nachher-Zeilen. Übersetzungsschlüssel identisch zum UI.
 */
final class ShiftActivityPresenter
{
    private const CATEGORY_LABELS = [
        'staffing' => 'Staffing',
        'shift_data' => 'Shift details',
        'lifecycle' => 'Shift created/deleted',
        'commitment' => 'Commitment & request',
        'confirmation' => 'Confirmations',
        'availability' => 'Availability & absence',
        'note' => 'Notes',
        'other' => 'Other',
    ];

    /** Tageseinträge einer Person im Verlauf (LogsShiftPlanDayActivity): Subject → Art */
    private const DAY_ENTRY_KINDS = [
        Vacation::class => 'absence',
        Availability::class => 'availability',
        ShiftPlanComment::class => 'note',
    ];

    /** Meldung je Art und Vorgang; {0} = Beschreibung (Typ, Zeit, Kommentar bzw. Notiztext) */
    private const DAY_ENTRY_MESSAGES = [
        'absence' => [
            'created' => 'Absence entered: {0}',
            'updated' => 'Absence changed: {0}',
            'deleted' => 'Absence removed: {0}',
        ],
        'availability' => [
            'created' => 'Availability entered: {0}',
            'updated' => 'Availability changed: {0}',
            'deleted' => 'Availability removed: {0}',
        ],
        'note' => [
            'created' => 'Note added: {0}',
            'updated' => 'Note changed: {0}',
            'deleted' => 'Note removed: {0}',
        ],
    ];

    private const ABSENCE_TYPE_LABELS = [
        // Bezeichnungen wie im Tagesdialog des Dienstplans
        'OFF_WORK' => 'Work-free day',
        'NOT_AVAILABLE' => 'Not available',
        'FREE_WORK' => 'Day off',
        'AVAILABLE' => 'Available',
    ];

    private const FIELD_LABELS = [
        'start' => 'Start time',
        'end' => 'End time',
        'start_time' => 'Start time',
        'end_time' => 'End time',
        'start_date' => 'Start date',
        'end_date' => 'End date',
        'event_start_day' => 'Start date',
        'event_end_day' => 'End date',
        'break_minutes' => 'Break',
        'description' => 'Description',
        'shiftGroup.name' => 'Shift group',
        'craft.name' => 'Craft',
        'project.name' => 'Project',
        'room.name' => 'Room',
        'current_request_id' => 'Request',
        'project_id' => 'Project',
        'craft_id' => 'Craft',
        'room_id' => 'Room',
        'global_qualifications' => 'Global qualification',
        'qualifications' => 'Qualification',
        'in_workflow' => 'In approval workflow',
        'workflow_rejection_reason' => 'workflow_rejection_reason',
        'shift_qualification_id' => 'shift_qualification_id',
        'assignment' => 'Assignment',
        'comment' => 'Comment',
        'type' => 'Type',
        'date' => 'Date',
        'full_day' => 'Full day',
        'day_part' => 'Day part',
    ];

    public function __construct(private readonly ?string $locale = null)
    {
    }

    /** Übersetzte Nachricht wie messageForLog() im Modal. */
    public function message(Activity $log): string
    {
        $dayEntry = $this->dayEntry($log);
        if ($dayEntry !== null) {
            $key = self::DAY_ENTRY_MESSAGES[$dayEntry['kind']][$log->event]
                ?? self::DAY_ENTRY_MESSAGES[$dayEntry['kind']]['updated'];

            return str_replace('{0}', $this->dayEntryDescription($dayEntry), $this->tr($key));
        }

        $properties = $this->properties($log);
        $key = $properties['translation_key'] ?? null;

        if (is_string($key) && $key !== '') {
            $values = $properties['translation_key_placeholder_values'] ?? [];
            $text = $this->tr($key);
            foreach (array_values(is_array($values) ? $values : []) as $index => $value) {
                $text = str_replace('{' . $index . '}', is_scalar($value) ? (string) $value : '', $text);
            }

            return $text;
        }

        if ($log->event === 'deleted') {
            return $this->tr('Shift was deleted');
        }
        if ($log->event === 'restored' || $log->description === 'restored') {
            return $this->tr('Shift was restored');
        }
        if ($log->description) {
            return $this->tr($log->description);
        }
        if ($log->event) {
            return $this->tr($log->event);
        }

        return $this->tr('Change in shift');
    }

    /** Aktions-Kategorie (Label) wie detectCategory() im Modal. */
    public function categoryLabel(Activity $log): string
    {
        return $this->tr(self::CATEGORY_LABELS[$this->category($log)]);
    }

    public function category(Activity $log): string
    {
        $dayEntry = $this->dayEntry($log);
        if ($dayEntry !== null) {
            return $dayEntry['kind'] === 'note' ? 'note' : 'availability';
        }

        $properties = $this->properties($log);
        $desc = mb_strtolower((string) $log->description);
        $ev = (string) $log->event;
        $key = mb_strtolower((string) ($properties['translation_key'] ?? ''));
        $ctx = (string) ($properties['context'] ?? '');

        if (in_array($ev, ['confirmation_accepted', 'confirmation_declined'], true)) {
            return 'confirmation';
        }
        if (!empty($properties['commit_summary']) || $ctx === 'commit') {
            return 'commitment';
        }
        if (
            in_array($ev, [
            'committed',
            'uncommitted',
            'committed_bulk',
            'uncommitted_bulk',
            'shift_committed',
            ], true)
        ) {
            return 'commitment';
        }
        if (in_array($ev, ['shift_added_to_request', 'workflow_withdrawn'], true) || str_contains($key, 'request')) {
            return 'commitment';
        }
        if (
            in_array($ev, ['assigned', 'removed'], true)
            || str_contains($desc, 'assigned') || str_contains($desc, 'removed')
            || str_contains($key, 'assigned to shift') || str_contains($key, 'removed from shift')
        ) {
            return 'staffing';
        }
        if (
            in_array($ev, ['created', 'deleted', 'deleted_with_reason', 'restored'], true)
            || str_contains($desc, 'deleted') || str_contains($desc, 'restored')
            || str_contains($key, 'shift was deleted')
        ) {
            return 'lifecycle';
        }
        if (
            str_contains($ev, 'updated') || str_contains($desc, 'updated') || str_contains($desc, 'reverted')
            || str_contains($key, 'updated') || str_contains($key, 'reverted') || str_contains($key, 'changed')
        ) {
            return 'shift_data';
        }

        return 'other';
    }

    public function causerName(Activity $log): string
    {
        $causer = $log->causer;
        if (!$causer) {
            return $this->tr('System');
        }

        $name = trim(($causer->first_name ?? '') . ' ' . ($causer->last_name ?? ''));
        if ($name === '' && isset($causer->full_name)) {
            $name = (string) $causer->full_name;
        }

        return $name !== '' ? $name : $this->tr('Unknown user');
    }

    /**
     * Schichtdaten für die Spalte "Schicht": Snapshot (Stand zum Zeitpunkt) → Live-Schicht → properties.old.
     * Sammel-Einträge (Festschreibung) zeigen Zeitraum + Gewerke.
     *
     * @param array<int, object> $shiftsById Live-Schichten (auch soft-deleted) mit room/craft geladen
     * @return array{date: string, time: string, room: string, craft: string}
     */
    public function shiftDetails(Activity $log, array $shiftsById): array
    {
        // Tageseinträge haben keine Schicht: subject_id ist die Abwesenheit/Notiz, nicht eine Schicht-ID
        $dayEntry = $this->dayEntry($log);
        if ($dayEntry !== null) {
            return [
                'date' => self::dateDe($dayEntry['day']),
                'time' => $dayEntry['kind'] === 'note' ? '' : $this->dayEntryTime($dayEntry['data']),
                'room' => '',
                'craft' => '',
            ];
        }

        $properties = $this->properties($log);
        $summary = $properties['commit_summary'] ?? null;

        if (is_array($summary) && $summary !== []) {
            $start = $summary['start_date'] ?? '';
            $end = $summary['end_date'] ?? '';

            return [
                'date' => $start
                    ? ($end
                         && $end !== $start ? self::dateDe($start) . ' – ' . self::dateDe($end) : self::dateDe($start))
                    : '',
                'time' => '',
                'room' => '',
                'craft' => implode(', ', array_filter((array) ($summary['crafts'] ?? []))),
            ];
        }

        $snap = is_array($properties['shift_snapshot'] ?? null) ? $properties['shift_snapshot'] : [];
        $old = is_array($properties['old'] ?? null) ? $properties['old'] : [];
        $id = $properties['shift_id'] ?? $log->subject_id ?? ($snap['id'] ?? null);
        $live = $id !== null ? ($shiftsById[(int) $id] ?? null) : null;

        $startDate = $snap['start_date'] ?? $live?->start_date?->format('Y-m-d') ?? $old['start_date'] ?? '';
        $endDate = $snap['end_date'] ?? $live?->end_date?->format('Y-m-d') ?? $old['end_date'] ?? '';
        $start = $snap['start'] ?? $live?->start ?? $old['start'] ?? '';
        $end = $snap['end'] ?? $live?->end ?? $old['end'] ?? '';

        return [
            'date' => $startDate
                ? ($endDate && $endDate !== $startDate
                    ? self::dateDe($startDate) . ' – ' . self::dateDe($endDate)
                    : self::dateDe($startDate))
                : '',
            'time' => trim(implode(' – ', array_filter([$start, $end]))),
            'room' => (string) ($snap['room'] ?? $live?->room?->name ?? $old['room.name'] ?? ''),
            'craft' => (string) ($snap['craft']
                 ?? $live?->craft?->abbreviation
                 ?? $live?->craft?->name
                 ?? $old['craft.name']
                 ?? ''),
        ];
    }

    /**
     * Vorher/Nachher-Zeilen ("Feld: vorher → nachher") wie extractActivityChanges() im Modal.
     *
     * @return array<int, string>
     */
    public function changeLines(Activity $log): array
    {
        $properties = $this->properties($log);
        $lines = [];

        if (!empty($properties['attributes']) && !empty($properties['old']) && is_array($properties['attributes'])) {
            foreach ($properties['attributes'] as $field => $new) {
                $lines[] = $this->fieldLabel((string) $field) . ': '
                    . $this->formatValue((string) $field, $properties['old'][$field] ?? null)
                    . ' → ' . $this->formatValue((string) $field, $new);
            }

            return $lines;
        }

        $fieldChanges = $properties['field_changes'] ?? null;
        if (!is_array($fieldChanges)) {
            return [];
        }

        foreach ($fieldChanges as $field => $change) {
            if ($field === '_initial') {
                continue;
            }

            if (in_array($field, ['qualifications', 'global_qualifications'], true) && is_array($change)) {
                foreach ($change as $item) {
                    $name = $item['label'] ?? ($item['qualification_id'] ?? $item['global_qualification_id'] ?? '');
                    $lines[] = $this->fieldLabel($field) . ($name !== '' ? ': ' . $name : '') . ': '
                        . $this->formatValue($field, $item['old_label'] ?? $item['old'] ?? null)
                        . ' → ' . $this->formatValue($field, $item['new_label'] ?? $item['new'] ?? null);
                }
                continue;
            }

            if ($field === 'assignment' && is_array($change)) {
                $oldPayload = array_key_exists('old', $change) ? $change['old'] : $change;
                $newPayload = array_key_exists('new', $change) ? $change['new'] : $change;
                $lines[] = $this->fieldLabel($field) . ': '
                    . $this->formatValue($field, $this->assignmentLabel($oldPayload, 'before_label'))
                    . ' → ' . $this->formatValue($field, $this->assignmentLabel($newPayload, 'after_label'));
                continue;
            }

            if (is_array($change) && (array_key_exists('old', $change) || array_key_exists('new', $change))) {
                $lines[] = $this->fieldLabel((string) $field) . ': '
                    . $this->formatValue((string) $field, $change['old_label'] ?? $change['old'] ?? null)
                    . ' → ' . $this->formatValue((string) $field, $change['new_label'] ?? $change['new'] ?? null);
                continue;
            }

            $lines[] = $this->fieldLabel((string) $field) . ': ' . $this->formatValue((string) $field, $change);
        }

        return $lines;
    }

    /**
     * Tageseintrag (Abwesenheit, Verfügbarkeit, Notiz) eines Verlaufseintrags oder null. Inhalt aus
     * day_entry (LogsShiftPlanDayActivity), bei älteren Einträgen aus attributes bzw. old (gelöscht).
     *
     * @return array{kind: string, day: ?string, data: array<string, mixed>}|null
     */
    public function dayEntry(Activity $log): ?array
    {
        $kind = self::DAY_ENTRY_KINDS[$log->subject_type] ?? null;
        if ($kind === null) {
            return null;
        }

        $properties = $this->properties($log);
        $data = $properties['day_entry'] ?? null;
        if (! is_array($data)) {
            $data = (array) ($log->event === 'deleted'
                ? ($properties['old'] ?? [])
                : ($properties['attributes'] ?? []));
        }

        return [
            'kind' => $kind,
            'day' => $properties['worker_day']
                ?? $properties['attributes']['date']
                ?? $properties['old']['date']
                ?? null,
            'data' => $data,
        ];
    }

    /**
     * "Nicht verfügbar · Ganztägig · „Arzttermin“" bzw. bei Notizen „Text“.
     *
     * @param array{kind: string, data: array<string, mixed>} $dayEntry
     */
    public function dayEntryDescription(array $dayEntry): string
    {
        $data = $dayEntry['data'];
        $comment = trim((string) ($data['comment'] ?? ''));

        if ($dayEntry['kind'] === 'note') {
            return $comment !== '' ? '„' . $comment . '“' : '–';
        }

        $type = (string) ($data['type'] ?? '');
        $parts = [];
        if ($dayEntry['kind'] === 'absence' && $type !== '') {
            $parts[] = $this->tr(self::ABSENCE_TYPE_LABELS[$type] ?? $type);
        }
        $time = $this->dayEntryTime($data);
        if ($time !== '') {
            $parts[] = $time;
        }
        // Abwesenheiten speichern im Kommentar teils nur den Typ — nicht doppelt zeigen
        if ($comment !== '' && $comment !== $type) {
            $parts[] = '„' . $comment . '“';
        }

        return $parts !== [] ? implode(' · ', $parts) : '–';
    }

    /**
     * @param array<string, mixed> $data
     */
    private function dayEntryTime(array $data): string
    {
        $start = self::timeOfDay($data['start_time'] ?? null);
        $end = self::timeOfDay($data['end_time'] ?? null);
        if (empty($data['full_day']) && $start !== null && $end !== null) {
            return $start . ' – ' . $end;
        }

        return match ($data['day_part'] ?? null) {
            'morning' => $this->tr('Morning'),
            'afternoon' => $this->tr('Afternoon'),
            default => ! empty($data['full_day']) ? $this->tr('Full day') : '',
        };
    }

    private static function timeOfDay(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return preg_match('/(\d{2}:\d{2})(:\d{2})?$/', (string) $value, $match) ? $match[1] : null;
    }

    private function assignmentLabel(mixed $payload, string $fallbackKey): ?string
    {
        if (!is_array($payload)) {
            return null;
        }
        $base = $payload['label']
             ?? $payload[$fallbackKey]
             ?? $payload['before_label']
             ?? $payload['after_label']
             ?? null;
        if (!$base) {
            $parts = [];
            $date = $payload['start_date'] ?? $payload['event_start_day'] ?? null;
            $startTime = $payload['start_time'] ?? $payload['start'] ?? null;
            $endTime = $payload['end_time'] ?? $payload['end'] ?? null;
            if ($date) {
                $parts[] = $date;
            }
            if ($startTime || $endTime) {
                $parts[] = implode(' - ', array_filter([$startTime, $endTime]));
            }
            $base = $parts !== [] ? implode(' ', $parts) : null;
        }

        return $base !== null ? (string) $base : null;
    }

    public function fieldLabel(string $field): string
    {
        if (str_starts_with($field, 'qualifications:')) {
            $name = substr($field, strlen('qualifications:'));

            return $this->tr('Qualification') . ($name !== '' ? ': ' . $name : '');
        }
        if (str_starts_with($field, 'global_qualifications:')) {
            $name = substr($field, strlen('global_qualifications:'));

            return $this->tr('Global qualification') . ($name !== '' ? ': ' . $name : '');
        }

        return isset(self::FIELD_LABELS[$field]) ? $this->tr(self::FIELD_LABELS[$field]) : $field;
    }

    private function formatValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '–';
        }
        if ($field === 'break_minutes') {
            $minutes = (int) $value;
            if ($minutes === 0) {
                return $this->tr('No break');
            }
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;

            return $h === 0 ? $m . ' min' : $h . ' h ' . $m . ' min';
        }
        if (str_contains($field, 'date') || str_ends_with($field, '_day')) {
            return self::dateDe($value);
        }
        if (is_bool($value)) {
            return $value ? $this->tr('Yes') : $this->tr('No');
        }
        if (is_scalar($value)) {
            return $this->tr((string) $value);
        }
        if (is_array($value)) {
            $label = $value['label'] ?? $value['before_label'] ?? $value['after_label'] ?? null;
            if ($label) {
                return $this->tr((string) $label);
            }

            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    }

    /**
     * @return array<string, mixed>
     */
    private function properties(Activity $log): array
    {
        $properties = $log->properties;
        if ($properties instanceof \Illuminate\Support\Collection) {
            return $properties->all();
        }

        return is_array($properties) ? $properties : [];
    }

    private static function dateDe(mixed $value): string
    {
        if (!$value) {
            return '';
        }
        $string = (string) $value;
        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})/', $string)) {
            return $string;
        }
        try {
            return Carbon::parse($string)->format('d.m.Y');
        } catch (\Throwable) {
            return $string;
        }
    }

    private function tr(string $key): string
    {
        return __($key, [], $this->locale);
    }
}
