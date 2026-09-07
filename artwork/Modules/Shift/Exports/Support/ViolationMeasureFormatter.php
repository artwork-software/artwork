<?php

namespace Artwork\Modules\Shift\Exports\Support;

use Artwork\Modules\Shift\Models\ShiftRuleViolation;
use Carbon\Carbon;

/**
 * Serverseitiges Gegenstück zu resources/js/Pages/ShiftWarnings/ruleTypes.js (formatViolationMeasure):
 * Messwert eines Verstoßes aus violation_data ("9,5 h von max. 8 h") plus Regeltyp-Label — identische
 * Übersetzungsschlüssel wie im UI, damit Excel-Export und Oberfläche dieselben Texte zeigen.
 */
final class ViolationMeasureFormatter
{
    /** Regeltyp -> Übersetzungsschlüssel (siehe RULE_TYPES in ruleTypes.js) */
    private const RULE_TYPE_LABELS = [
        'maxWorkingHoursOnDay' => 'Daily maximum of hours',
        'maxConsecWorkingDays' => 'Maximum consecutive working days',
        'weeklyMaxHours' => 'Weekly maximum of hours',
        'maxWorkingHoursOnWeek' => 'Weekly maximum of hours',
        'restTimeBeforeWorkday' => 'Rest time before a working day',
        'restTimeBeforeHoliday' => 'Rest time before a Sunday or special day',
        'restTimeBetweenShiftGroups' => 'Rest time between shift groups',
        'halfDayOffConflict' => 'Conflict: half day off / shift',
        'halfDayOffOnSpecialDay' => 'No half day off on special days',
        'minDaysBeforeCommit' => 'Minimum days before binding commitment',
        'workOnSunday' => 'Work on Sunday',
        'workOnHoliday' => 'Work on special day',
        'overtimeDeadline' => 'Overtime reduction deadline',
        'minFreeSundaysPerSeasonHalf' => 'Minimum free Sundays with Saturday/Monday per season half',
        'minFreeSundaysPerYear' => 'Minimum free Sundays per calendar year',
        'averageWeeklyHours' => 'Average weekly hours over a compensation period',
        'nightWorkMaxHours' => 'Daily maximum on days with night work',
        'minFreeDaysPerWeek' => 'Minimum whole free days per week',
    ];

    public function __construct(private readonly ?string $locale = null)
    {
    }

    public function ruleTypeLabel(?string $triggerType): string
    {
        if ($triggerType === null || $triggerType === '') {
            return '';
        }

        return $this->tr(self::RULE_TYPE_LABELS[$triggerType] ?? $triggerType);
    }

    public function format(ShiftRuleViolation $violation): string
    {
        $data = $violation->violation_data;
        if (!is_array($data) || $data === []) {
            return '';
        }

        $type = $violation->shiftRule?->trigger_type;
        $ofMax = fn (string $actual, string $max): string => $this->tr('{actual} of max. {max}', ['actual' => $actual, 'max' => $max]);
        $ofMin = fn (string $actual, string $min): string => $this->tr('{actual} of min. {min}', ['actual' => $actual, 'min' => $min]);

        if (($data['type'] ?? null) === 'compensation_deadline_expired') {
            return $this->tr('Compensation deadline expired');
        }

        if (($data['type'] ?? null) === 'overtime_deadline' || array_key_exists('remaining_minutes', $data)) {
            $parts = [$this->tr('{hours} open, deadline {date}', [
                'hours' => self::minutesAsHours($data['remaining_minutes'] ?? 0),
                'date' => self::dateDe($data['deadline'] ?? null),
            ])];
            if (array_key_exists('days_left', $data)) {
                $parts[] = (int) $data['days_left'] < 0
                    ? $this->tr('Deadline expired')
                    : $this->tr('{days} days left', ['days' => $data['days_left']]);
            }

            return implode(', ', $parts);
        }

        if (($data['type'] ?? null) === 'min_free_sundays_per_season_half' || array_key_exists('half', $data)) {
            $half = (int) ($data['half'] ?? 1) === 2 ? $this->tr('2nd half') : $this->tr('1st half');
            $base = $half . ': ' . $ofMin((string) ($data['have'] ?? 0), ($data['target'] ?? '') . ' ' . $this->tr('free Sundays'));

            return !empty($data['completed'])
                ? $base
                : $base . ', ' . $this->tr('{count} still possible', ['count' => $data['possible'] ?? 0]);
        }

        if (($data['type'] ?? null) === 'min_free_sundays_per_year') {
            $base = ($data['year'] ?? '') . ': ' . $ofMin((string) ($data['have'] ?? 0), ($data['target'] ?? '') . ' ' . $this->tr('free Sundays'));

            return !empty($data['completed'])
                ? $base
                : $base . ', ' . $this->tr('{count} still possible', ['count' => $data['possible'] ?? 0]);
        }

        if (($data['type'] ?? null) === 'average_weekly_hours' || array_key_exists('average_hours', $data)) {
            return $this->tr('Ø {hours} h over {weeks} weeks, max. {max} h', [
                'hours' => self::number($data['average_hours'] ?? null),
                'weeks' => $data['period_weeks'] ?? '',
                'max' => self::number($data['max_allowed'] ?? null),
            ]);
        }

        if (($data['type'] ?? null) === 'night_work_max_hours' || array_key_exists('night_hours', $data)) {
            return $ofMax(
                $this->tr('{hours} h ({night} h of it at night)', [
                    'hours' => self::number($data['planned_hours'] ?? null),
                    'night' => self::number($data['night_hours'] ?? null),
                ]),
                self::number($data['max_allowed'] ?? null) . ' h'
            );
        }

        if (($data['type'] ?? null) === 'min_free_days_per_week' || array_key_exists('free_days', $data)) {
            $week = !empty($data['week']) ? $this->tr('CW') . ' ' . $data['week'] . ': ' : '';

            return $week . $ofMin((string) ($data['free_days'] ?? 0), ($data['target'] ?? '') . ' ' . $this->tr('free days'));
        }

        if (isset($data['planned_hours'], $data['max_allowed'])) {
            return $ofMax(self::number($data['planned_hours']) . ' h', self::number($data['max_allowed']) . ' h');
        }
        if (isset($data['weekly_hours'], $data['max_allowed'])) {
            return $ofMax(self::number($data['weekly_hours']) . ' h', self::number($data['max_allowed']) . ' h');
        }
        if (isset($data['consecutive_days'], $data['max_allowed'])) {
            $days = $this->tr('Days');

            return $ofMax(self::number($data['consecutive_days']) . ' ' . $days, self::number($data['max_allowed']) . ' ' . $days);
        }
        if (isset($data['rest_hours'], $data['min_required'])) {
            return $ofMin(self::number($data['rest_hours']) . ' h', self::number($data['min_required']) . ' h');
        }
        if (isset($data['days_until_shift'], $data['min_required'])) {
            $days = $this->tr('Days');

            return $ofMin(self::number($data['days_until_shift']) . ' ' . $days, self::number($data['min_required']) . ' ' . $days);
        }

        if ($type === 'halfDayOffConflict' || array_key_exists('threshold_hour', $data)) {
            $parts = [];
            if (!empty($data['half_day_period'])) {
                $parts[] = $this->tr(match ($data['half_day_period']) {
                    'morning' => 'Morning off',
                    'afternoon' => 'Afternoon off',
                    default => 'Whole day off',
                });
            }
            if (array_key_exists('threshold_hour', $data)) {
                $parts[] = $this->tr('Threshold') . ': ' . self::decimalHourToTime($data['threshold_hour']);
            }

            return implode(', ', $parts);
        }

        if ($type === 'halfDayOffOnSpecialDay' || ($data['reason'] ?? null) === 'half_day_off_on_special_day') {
            return $this->tr('Half day off on a special day');
        }

        if ($type === 'workOnHoliday' || !empty($data['holiday_name'])) {
            return !empty($data['holiday_name'])
                ? $this->tr('Shift on special day {name}', ['name' => $data['holiday_name']])
                : $this->tr('Shift on a special day');
        }

        if ($type === 'workOnSunday' || ($data['weekday'] ?? null) === 'sunday') {
            return $this->tr('Shift on a Sunday');
        }

        return '';
    }

    /**
     * Übersetzung mit {name}-Platzhaltern (vue-i18n-Stil), damit die vorhandenen de.json-Texte
     * unverändert genutzt werden können.
     *
     * @param array<string, mixed> $params
     */
    public function tr(string $key, array $params = []): string
    {
        $text = __($key, [], $this->locale);
        foreach ($params as $name => $value) {
            $text = str_replace('{' . $name . '}', (string) $value, $text);
        }

        return $text;
    }

    /** Zahl mit Komma als Dezimaltrenner, max. 1 Nachkommastelle */
    public static function number(mixed $value): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return (string) ($value ?? '');
        }
        $rounded = round((float) $value, 1);

        return number_format($rounded, $rounded === floor($rounded) ? 0 : 1, ',', '.');
    }

    /** Minuten -> "3:30 h" */
    public static function minutesAsHours(mixed $minutes): string
    {
        $total = max(0, (int) round((float) ($minutes ?: 0)));

        return sprintf('%d:%02d h', intdiv($total, 60), $total % 60);
    }

    /** Dezimalstunde -> "HH:MM" */
    public static function decimalHourToTime(mixed $value): string
    {
        if (!is_numeric($value) || (float) $value < 0) {
            return '';
        }
        $hours = (int) floor((float) $value);
        $minutes = (int) round(((float) $value - $hours) * 60);

        return sprintf('%02d:%02d', min($hours, 23), min($minutes, 59));
    }

    /** "YYYY-MM-DD" -> "DD.MM.YYYY" */
    public static function dateDe(mixed $value): string
    {
        if (!$value) {
            return '';
        }
        try {
            return Carbon::parse((string) $value)->format('d.m.Y');
        } catch (\Throwable) {
            return (string) $value;
        }
    }
}
