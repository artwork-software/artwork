<?php

namespace Artwork\Modules\Shift\Support;

use Artwork\Modules\Shift\Models\ShiftRule;

/**
 * Zusätzliche Parameter je Regeltyp (shift_rules.config). Einzige Definitionsquelle für Schlüssel,
 * Typen und Standardwerte: Die Requests validieren damit, der Controller normalisiert damit, die
 * Regelprüfungen lesen damit. Eine Regel ohne config (Altbestand) liest überall den Standardwert und
 * verhält sich damit wie vor Einführung der Spalte.
 *
 * Feldtypen:
 *  - bool
 *  - int (mit min/max)
 *  - float (mit min/max, nullable = leer erlaubt)
 *  - time ("HH:MM")
 *  - month_day ("MM-DD", jährlich wiederkehrender Kalendertag)
 *  - month_day_list (Liste von "MM-DD")
 *  - id_list (Liste von IDs, z. B. Schichtgruppen)
 */
final class ShiftRuleConfig
{
    private const MONTH_DAY_PATTERN = '/^(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])$/';

    private const TIME_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    /**
     * @return array<string, array<string, array{type: string, default: mixed, min?: int|float,
     *               max?: int|float, required?: bool, nullable?: bool, exists?: string}>>
     */
    private static function schemas(): array
    {
        $skipSameDay = ['skip_same_day' => ['type' => 'bool', 'default' => false]];

        return [
            'weeklyPlanCommitDeadline' => [
                'weekday' => ['type' => 'int', 'default' => 4, 'min' => 1, 'max' => 7],
                'time' => ['type' => 'time', 'default' => '14:00'],
                'weeks_ahead' => ['type' => 'int', 'default' => 1, 'min' => 1, 'max' => 4],
            ],
            'halfDayOffMaxHours' => [
                'boundary' => ['type' => 'time', 'default' => '14:00'],
                'extended_hours' => [
                    'type' => 'float',
                    'default' => null,
                    'min' => 0.5,
                    'max' => 24,
                    'nullable' => true,
                ],
                'extended_shift_group_ids' => ['type' => 'id_list', 'default' => [], 'exists' => 'shift_groups'],
            ],
            'halfDayOffOnSpecialDay' => [
                'include_sundays' => ['type' => 'bool', 'default' => false],
                'morning_only' => ['type' => 'bool', 'default' => false],
                'include_planned' => ['type' => 'bool', 'default' => false],
            ],
            'restTimeBetweenShiftsOnDay' => [
                'excluded_shift_group_ids' => ['type' => 'id_list', 'default' => [], 'exists' => 'shift_groups'],
            ],
            'restTimeBeforeWorkday' => $skipSameDay,
            'restTimeBeforeHoliday' => $skipSameDay,
            'minFreeDaysPerWeek' => [
                'workdays_only' => ['type' => 'bool', 'default' => false],
                'excluded_dates' => ['type' => 'month_day_list', 'default' => []],
            ],
            'freeDayAfterWorkOnDate' => [
                'worked' => ['type' => 'month_day', 'default' => null, 'required' => true],
                'free' => [
                    'type' => 'month_day',
                    'default' => null,
                    'required' => true,
                    'different' => 'worked',
                ],
            ],
        ];
    }

    public static function hasSchema(?string $triggerType): bool
    {
        return $triggerType !== null && isset(self::schemas()[$triggerType]);
    }

    /**
     * Standardwerte eines Regeltyps (leer, wenn der Typ keine Zusatzparameter kennt).
     *
     * @return array<string, mixed>
     */
    public static function defaults(?string $triggerType): array
    {
        if (!self::hasSchema($triggerType)) {
            return [];
        }

        return array_map(static fn (array $field): mixed => $field['default'], self::schemas()[$triggerType]);
    }

    /**
     * Validierungsregeln für das Feld "config" eines Requests (Schlüssel "config.<feld>").
     *
     * @return array<string, mixed>
     */
    public static function validationRules(?string $triggerType): array
    {
        $rules = ['config' => ['nullable', 'array']];
        if (!self::hasSchema($triggerType)) {
            return $rules;
        }

        foreach (self::schemas()[$triggerType] as $key => $field) {
            $presence = !empty($field['required']) ? 'required' : 'nullable';
            $path = 'config.' . $key;

            switch ($field['type']) {
                case 'bool':
                    $rules[$path] = [$presence, 'boolean'];
                    break;
                case 'int':
                    $rules[$path] = [$presence, 'integer', 'min:' . $field['min'], 'max:' . $field['max']];
                    break;
                case 'float':
                    $rules[$path] = [$presence, 'numeric', 'min:' . $field['min'], 'max:' . $field['max']];
                    break;
                case 'time':
                    $rules[$path] = [$presence, 'string', 'regex:' . self::TIME_PATTERN];
                    break;
                case 'month_day':
                    $rules[$path] = [
                        $presence,
                        'string',
                        'regex:' . self::MONTH_DAY_PATTERN,
                        self::existingCalendarDay(),
                    ];
                    if (isset($field['different'])) {
                        $rules[$path][] = 'different:config.' . $field['different'];
                    }
                    break;
                case 'month_day_list':
                    $rules[$path] = [$presence, 'array', 'max:20'];
                    $rules[$path . '.*'] = [
                        'string',
                        'distinct',
                        'regex:' . self::MONTH_DAY_PATTERN,
                        self::existingCalendarDay(),
                    ];
                    break;
                case 'id_list':
                    // Bewusst ohne exists-Prüfung: Eine inzwischen gelöschte Schichtgruppe darf das
                    // Speichern der Regel nicht blockieren, in den Prüfungen ist eine verwaiste ID wirkungslos.
                    $rules[$path] = [$presence, 'array', 'max:100'];
                    $rules[$path . '.*'] = ['integer', 'distinct', 'min:1'];
                    break;
            }
        }

        return $rules;
    }

    /**
     * Bringt eine Eingabe in die gespeicherte Form: nur bekannte Schlüssel, typisiert, fehlende Werte
     * mit dem Standard. null für Regeltypen ohne Zusatzparameter.
     *
     * @param array<string, mixed>|null $input
     * @return array<string, mixed>|null
     */
    public static function normalize(?string $triggerType, ?array $input): ?array
    {
        if (!self::hasSchema($triggerType)) {
            return null;
        }

        $input ??= [];
        $config = [];
        foreach (self::schemas()[$triggerType] as $key => $field) {
            $value = array_key_exists($key, $input) ? $input[$key] : $field['default'];
            $config[$key] = self::cast($field, $value);
        }

        return $config;
    }

    /**
     * Wert eines Zusatzparameters der Regel, sonst der Standardwert des Regeltyps.
     */
    public static function get(ShiftRule $rule, string $key): mixed
    {
        $config = self::normalize($rule->trigger_type, is_array($rule->config) ? $rule->config : null);

        return $config[$key] ?? null;
    }

    /**
     * Vergleicht zwei Konfigurationen eines Regeltyps nach Normalisierung (null und Standardwerte sind
     * gleich). Grundlage für "diese Regel gibt es schon" beim Anlegen von Regelsätzen.
     *
     * @param array<string, mixed>|null $left
     * @param array<string, mixed>|null $right
     */
    public static function equals(?string $triggerType, ?array $left, ?array $right): bool
    {
        return self::normalize($triggerType, $left) == self::normalize($triggerType, $right);
    }

    /**
     * Wie equals(), ohne die Listen von IDs (z. B. Schichtgruppen). Diese Listen trägt ein Haus nach dem
     * Anlegen eines Regelsatzes selbst ein; eine so angepasste Regel bleibt dieselbe Regel des Satzes.
     *
     * @param array<string, mixed>|null $left
     * @param array<string, mixed>|null $right
     */
    public static function equalsIgnoringIdLists(?string $triggerType, ?array $left, ?array $right): bool
    {
        $leftConfig = self::normalize($triggerType, $left);
        $rightConfig = self::normalize($triggerType, $right);
        if ($leftConfig === null || $rightConfig === null) {
            return $leftConfig == $rightConfig;
        }

        foreach (self::schemas()[$triggerType] as $key => $field) {
            if ($field['type'] === 'id_list') {
                unset($leftConfig[$key], $rightConfig[$key]);
            }
        }

        return $leftConfig == $rightConfig;
    }

    /**
     * Validierungsregel: "MM-DD" muss ein Kalendertag sein, den es gibt (der 29.02. zählt, der 30.02. nicht).
     */
    private static function existingCalendarDay(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if (!is_string($value) || preg_match(self::MONTH_DAY_PATTERN, $value) !== 1) {
                return;
            }
            [$month, $day] = array_map('intval', explode('-', $value));
            // 2024 ist ein Schaltjahr, damit gilt der 29.02. als gültig
            if (!checkdate($month, $day, 2024)) {
                $fail(__('validation.date', ['attribute' => $attribute]));
            }
        };
    }

    /**
     * @param array<string, mixed> $field
     */
    private static function cast(array $field, mixed $value): mixed
    {
        switch ($field['type']) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'int':
                $number = is_numeric($value) ? (int) $value : (int) $field['default'];

                return max((int) $field['min'], min((int) $field['max'], $number));
            case 'float':
                if ($value === null || $value === '' || !is_numeric($value) || (float) $value <= 0) {
                    return $field['default'];
                }

                return (float) $value;
            case 'time':
                return is_string($value) && preg_match(self::TIME_PATTERN, $value) === 1
                    ? $value
                    : $field['default'];
            case 'month_day':
                return is_string($value) && preg_match(self::MONTH_DAY_PATTERN, $value) === 1
                    ? $value
                    : $field['default'];
            case 'month_day_list':
                $dates = array_filter(
                    is_array($value) ? $value : [],
                    static fn (mixed $date): bool => is_string($date)
                        && preg_match(self::MONTH_DAY_PATTERN, $date) === 1
                );
                $dates = array_values(array_unique($dates));
                sort($dates);

                return $dates;
            case 'id_list':
                $ids = array_map('intval', array_filter(is_array($value) ? $value : [], 'is_numeric'));
                $ids = array_values(array_unique($ids));
                sort($ids);

                return $ids;
        }

        return $value;
    }
}
