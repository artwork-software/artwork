<?php

namespace Artwork\Modules\Shift\Support;

use Artwork\Modules\Shift\Models\ShiftRule;

/**
 * Gesetzliche Standardregeln (ArbZG), die über "Gesetzliche Standardregeln anlegen" als ganz normale
 * Regeln angelegt werden — ohne Sonderkennzeichnung, danach frei anpassbar/umbenennbar.
 *
 * Einzige Definitionsquelle: der Controller liefert die Liste als Inertia-Prop `legalDefaultRules` an
 * ShiftWarnings/Index.vue (keine Kopie im Frontend) und legt beim Speichern nur Schlüssel aus dieser
 * Liste an. Name, Beschreibung und Begründung sind Stammdaten der angelegten Regel (deutsch), keine
 * Übersetzungsschlüssel.
 */
final class LegalDefaultShiftRules
{
    public const DEFAULT_WARNING_COLOR = '#ff6b6b';

    /**
     * @return list<array{key: string, name: string, description: string, trigger_type: string,
     *               individual_number_value: float, period_weeks: int|null,
     *               default_compensation_days: float|null, default_compensation_deadline_days: int|null,
     *               legal_basis: string, reason: string}>
     */
    public static function all(): array
    {
        return [
            self::definition(
                'dailyMax',
                'Gesetzliches Tagesmaximum',
                'maxWorkingHoursOnDay',
                10.0,
                'ArbZG § 3',
                'Die werktägliche Arbeitszeit darf auf bis zu 10 Stunden verlängert werden.',
                'Die geplante Arbeitszeit an einem Tag überschreitet das gesetzliche Tagesmaximum von 10 Stunden (ArbZG § 3).'
            ),
            self::definition(
                'weeklyMax',
                'Gesetzliches Wochenmaximum',
                'weeklyMaxHours',
                48.0,
                'ArbZG § 3',
                '8 Stunden werktäglich an sechs Werktagen ergeben höchstens 48 Stunden pro Woche.',
                'Die geplante Arbeitszeit in einer Woche überschreitet das gesetzliche Wochenmaximum von 48 Stunden (ArbZG § 3).'
            ),
            self::definition(
                'restTime',
                'Gesetzliche Ruhezeit',
                'restTimeBeforeWorkday',
                11.0,
                'ArbZG § 5 Abs. 1',
                'Nach Ende der täglichen Arbeitszeit ist eine ununterbrochene Ruhezeit von mindestens 11 Stunden einzuhalten.',
                'Zwischen zwei Arbeitseinsätzen liegen weniger als 11 Stunden Ruhezeit (ArbZG § 5 Abs. 1).'
            ),
            self::definition(
                'consecutiveDays',
                'Gesetzliche Höchstzahl Arbeitstage in Folge',
                'maxConsecWorkingDays',
                6.0,
                'ArbZG § 3, § 9',
                'Sechs Werktage pro Woche, Sonntag ist grundsätzlich arbeitsfrei.',
                'Die Person ist mehr als 6 Tage in Folge eingeplant (ArbZG § 3, § 9).'
            ),
            self::definition(
                'workOnSunday',
                'Sonntagsarbeit (Ersatzruhetag)',
                'workOnSunday',
                0.0,
                'ArbZG § 11 Abs. 3',
                'Für Arbeit an einem Sonntag ist innerhalb von zwei Wochen ein Ersatzruhetag zu gewähren.',
                'Die Person arbeitet an einem Sonntag — Anspruch auf einen Ersatzruhetag innerhalb von zwei Wochen (ArbZG § 11 Abs. 3).',
                1.0,
                14
            ),
            self::definition(
                'workOnHoliday',
                'Arbeit am Sondertag (Ersatzruhetag)',
                'workOnHoliday',
                0.0,
                'ArbZG § 11 Abs. 3',
                'Für Arbeit an einem Feiertag ist innerhalb von acht Wochen ein Ersatzruhetag zu gewähren.',
                'Die Person arbeitet an einem Sondertag — Anspruch auf einen Ersatzruhetag innerhalb von acht Wochen (ArbZG § 11 Abs. 3).',
                1.0,
                56
            ),
            self::definition(
                'freeSundaysPerYear',
                'Gesetzliche freie Sonntage pro Jahr',
                'minFreeSundaysPerYear',
                15.0,
                'ArbZG § 11 Abs. 1',
                'Mindestens 15 Sonntage im Jahr müssen beschäftigungsfrei bleiben.',
                'Die Person kann im Kalenderjahr die 15 gesetzlich freien Sonntage nicht mehr erreichen (ArbZG § 11 Abs. 1).'
            ),
            self::definition(
                'averageWeeklyHours',
                'Gesetzlicher Wochendurchschnitt',
                'averageWeeklyHours',
                48.0,
                'ArbZG § 3, TVöD § 6 Abs. 2',
                'Im Durchschnitt des Ausgleichszeitraums von 24 Wochen dürfen 48 Stunden pro Woche nicht überschritten werden.',
                'Der Durchschnitt der geplanten Wochenstunden über 24 Wochen überschreitet 48 Stunden (ArbZG § 3, TVöD § 6 Abs. 2).',
                null,
                null,
                24
            ),
            self::definition(
                'nightWorkMax',
                'Gesetzliches Nachtarbeitsmaximum',
                'nightWorkMaxHours',
                8.0,
                'ArbZG § 6 Abs. 2',
                'An Tagen mit mindestens 2 Stunden Nachtarbeit darf die Arbeitszeit 8 Stunden nicht überschreiten.',
                'An einem Tag mit Nachtarbeit überschreitet die geplante Arbeitszeit 8 Stunden (ArbZG § 6 Abs. 2).'
            ),
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_map(static fn (array $definition): string => $definition['key'], self::all());
    }

    /**
     * @return array{key: string, name: string, description: string, trigger_type: string,
     *               individual_number_value: float, period_weeks: int|null,
     *               default_compensation_days: float|null, default_compensation_deadline_days: int|null,
     *               legal_basis: string, reason: string}|null
     */
    public static function find(string $key): ?array
    {
        foreach (self::all() as $definition) {
            if ($definition['key'] === $key) {
                return $definition;
            }
        }

        return null;
    }

    /**
     * Attribute für ShiftRule::create() aus einer Definition.
     *
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    public static function attributesFor(array $definition): array
    {
        return [
            'name' => $definition['name'],
            'description' => $definition['description'],
            'trigger_type' => $definition['trigger_type'],
            'individual_number_value' => $definition['individual_number_value'],
            'period_weeks' => $definition['period_weeks'],
            'warning_color' => self::DEFAULT_WARNING_COLOR,
            'default_compensation_days' => $definition['default_compensation_days'],
            'default_compensation_deadline_days' => $definition['default_compensation_deadline_days'],
            'is_active' => true,
        ];
    }

    /**
     * Idempotenz: Eine bestehende AKTIVE Regel gilt als "dieselbe", wenn Typ und Wert übereinstimmen
     * (beim Wochendurchschnitt zusätzlich der Zeitraum in Wochen). Name/Farbe spielen keine Rolle —
     * umbenannte Standardregeln werden nicht erneut angelegt.
     *
     * Inaktive Regeln zählen NICHT als vorhanden ($requireActive = true, Standard): der Controller sucht
     * sie mit $requireActive = false gesondert und reaktiviert sie, statt eine Dublette anzulegen.
     *
     * @param array<string, mixed> $definition
     */
    public static function matches(ShiftRule $rule, array $definition, bool $requireActive = true): bool
    {
        if ($requireActive && !$rule->is_active) {
            return false;
        }
        if ($rule->trigger_type !== $definition['trigger_type']) {
            return false;
        }
        if (abs((float) $rule->individual_number_value - (float) $definition['individual_number_value']) > 0.001) {
            return false;
        }
        if ($definition['period_weeks'] !== null && (int) $rule->period_weeks !== (int) $definition['period_weeks']) {
            return false;
        }

        return true;
    }

    /**
     * @return array{key: string, name: string, description: string, trigger_type: string,
     *               individual_number_value: float, period_weeks: int|null,
     *               default_compensation_days: float|null, default_compensation_deadline_days: int|null,
     *               legal_basis: string, reason: string}
     */
    private static function definition(
        string $key,
        string $name,
        string $triggerType,
        float $value,
        string $legalBasis,
        string $reason,
        string $description,
        ?float $compensationDays = null,
        ?int $compensationDeadlineDays = null,
        ?int $periodWeeks = null
    ): array {
        return [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'trigger_type' => $triggerType,
            'individual_number_value' => $value,
            'period_weeks' => $periodWeeks,
            'default_compensation_days' => $compensationDays,
            'default_compensation_deadline_days' => $compensationDeadlineDays,
            'legal_basis' => $legalBasis,
            'reason' => $reason,
        ];
    }
}
