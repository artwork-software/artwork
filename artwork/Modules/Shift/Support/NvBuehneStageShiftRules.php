<?php

namespace Artwork\Modules\Shift\Support;

/**
 * Regelsatz "NV Bühne (Bühnentechnik)": die Arbeitszeitregeln des Normalvertrags Bühne für
 * Bühnentechniker*innen (§ 6 und §§ 64 bis 66 in der Fassung des Vierzehnten Änderungstarifvertrags
 * vom 30. April 2025) als ganz normale, danach frei anpassbare Regeln.
 *
 * Gleiche Form wie LegalDefaultShiftRules::all(), ergänzt um "config" (Zusatzparameter je Regeltyp,
 * siehe ShiftRuleConfig). Angelegt wird der Satz über ShiftRuleSets. Name, Beschreibung und
 * Begründung sind Stammdaten der angelegten Regel (deutsch), keine Übersetzungsschlüssel.
 */
final class NvBuehneStageShiftRules
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            self::definition(
                'nightRestWorkday',
                'NV Bühne: Nachtruhezeit vor Werktag',
                'restTimeBeforeWorkday',
                11.0,
                'NV Bühne § 65 Abs. 1',
                'Nach Ende der täglichen Arbeitszeit ist eine elfstündige Ruhezeit zu gewähren. Bei einer '
                    . 'Verkürzung entsteht Anspruch auf einen zusätzlichen halben freien Tag binnen zwei Wochen.',
                'Zwischen dem Dienstende des Vortags und dem Dienstbeginn liegen weniger als 11 Stunden '
                    . 'Nachtruhezeit (NV Bühne § 65 Abs. 1).',
                ['skip_same_day' => true],
                0.5,
                14
            ),
            self::definition(
                'nightRestHoliday',
                'NV Bühne: Nachtruhezeit vor Sonn- und Sondertag',
                'restTimeBeforeHoliday',
                11.0,
                'NV Bühne § 65 Abs. 1',
                'Die elfstündige Nachtruhezeit gilt auch vor Sonntagen und Sondertagen.',
                'Zwischen dem Dienstende des Vortags und dem Dienstbeginn am Sonn- oder Sondertag liegen '
                    . 'weniger als 11 Stunden Nachtruhezeit (NV Bühne § 65 Abs. 1).',
                ['skip_same_day' => true],
                0.5,
                14
            ),
            self::definition(
                'splitDuty',
                'NV Bühne: Geteilter Dienst',
                'restTimeBetweenShiftsOnDay',
                4.0,
                'NV Bühne § 65 Abs. 2',
                'Wird die Arbeitszeit auf zwei Zeitabschnitte verteilt, liegen dazwischen vier Stunden '
                    . 'Ruhezeit. Endproben lassen sich über ausgenommene Schichtgruppen ausnehmen.',
                'Zwischen zwei Arbeitsabschnitten am selben Tag liegen weniger als 4 Stunden '
                    . '(NV Bühne § 65 Abs. 2).',
                ['excluded_shift_group_ids' => []]
            ),
            self::definition(
                'freeWorkday',
                'NV Bühne: Freier Werktag je Woche',
                'minFreeDaysPerWeek',
                1.0,
                'NV Bühne § 66 Abs. 1 und 5',
                'Anspruch auf einen freien Werktag pro Woche. Der 1. Mai und der 24. Dezember zählen nicht '
                    . 'als freier Werktag.',
                'Die Person hat in dieser Kalenderwoche keinen freien Werktag (NV Bühne § 66 Abs. 1).',
                ['workdays_only' => true, 'excluded_dates' => ['05-01', '12-24']]
            ),
            self::definition(
                'maxDaysBetweenFreeDays',
                'NV Bühne: Höchstens 12 Tage zwischen freien Tagen',
                'maxConsecWorkingDays',
                12.0,
                'NV Bühne § 66 Abs. 2',
                'Zwischen zwei freien Werktagen sollen höchstens 12 Tage liegen. Ein ausgefallener freier '
                    . 'Tag ist binnen sechs Wochen auszugleichen.',
                'Die Person ist mehr als 12 Tage in Folge eingeplant (NV Bühne § 66 Abs. 2).',
                null,
                1.0,
                42
            ),
            self::definition(
                'workOnHoliday',
                'NV Bühne: Arbeit am Wochenfeiertag',
                'workOnHoliday',
                0.0,
                'NV Bühne § 66 Abs. 3',
                'Für Arbeit an einem Wochenfeiertag ist binnen acht Wochen ein freier Ausgleichstag zu '
                    . 'gewähren.',
                'Die Person arbeitet an einem Sondertag und hat Anspruch auf einen freien Ausgleichstag '
                    . 'binnen acht Wochen (NV Bühne § 66 Abs. 3).',
                null,
                1.0,
                56
            ),
            self::definition(
                'halfDayOffBoundary',
                'NV Bühne: Halber freier Tag, Grenze 14 Uhr',
                'halfDayOffConflict',
                14.0,
                'NV Bühne § 66 Abs. 4',
                'Die Grenze zwischen den Tageshälften eines halben freien Tages liegt bei 14 Uhr.',
                'Ein Dienst reicht in die freie Tageshälfte hinein (NV Bühne § 66 Abs. 4).'
            ),
            self::definition(
                'halfDayOffMaxHours',
                'NV Bühne: Arbeitszeit am halben freien Tag',
                'halfDayOffMaxHours',
                4.0,
                'NV Bühne § 66 Abs. 4',
                'In der anderen Tageshälfte sind höchstens 4 Stunden Arbeit zulässig, bei Endproben und '
                    . 'Aufführungen höchstens 5 Stunden. Welche Schichtgruppen als Endprobe oder Aufführung '
                    . 'gelten, wird an der Regel eingetragen.',
                'Am halben freien Tag überschreitet die Arbeitszeit in der anderen Tageshälfte die '
                    . 'zulässige Dauer (NV Bühne § 66 Abs. 4).',
                ['boundary' => '14:00', 'extended_hours' => 5.0, 'extended_shift_group_ids' => []]
            ),
            self::definition(
                'noHalfDayOffOnSundayMorning',
                'NV Bühne: Kein halber freier Tag am Sonntagvormittag',
                'halfDayOffOnSpecialDay',
                0.0,
                'NV Bühne § 66 Abs. 4',
                'Der halbe freie Tag darf nicht auf den Vormittag eines Sonntags oder Wochenfeiertags '
                    . 'gelegt werden.',
                'Ein halber freier Tag liegt am Vormittag eines Sonntags oder Sondertags '
                    . '(NV Bühne § 66 Abs. 4).',
                ['include_sundays' => true, 'morning_only' => true, 'include_planned' => true]
            ),
            self::definition(
                'christmasEveMayDay',
                'NV Bühne: Dienstfrei am 1. Mai nach Arbeit am 24. Dezember',
                'freeDayAfterWorkOnDate',
                0.0,
                'NV Bühne § 66 Abs. 5',
                'Wer am 24. Dezember arbeitet, hat am folgenden 1. Mai dienstfrei.',
                'Die Person hat am 24. Dezember gearbeitet und ist am 1. Mai eingeplant '
                    . '(NV Bühne § 66 Abs. 5).',
                ['worked' => '12-24', 'free' => '05-01']
            ),
            self::definition(
                'freeSundaysPerSeasonHalf',
                'NV Bühne: Freie Sonntage mit Samstag oder Montag',
                'minFreeSundaysPerSeasonHalf',
                4.0,
                'NV Bühne § 66 Abs. 6',
                'In acht Kalenderwochen der Spielzeit ist der Tag vor oder nach einem freien Sonntag '
                    . 'arbeitsfrei, je vier pro Spielzeithälfte.',
                'Die Person erreicht in der Spielzeithälfte die vier freien Sonntage mit freiem Samstag '
                    . 'oder Montag nicht mehr (NV Bühne § 66 Abs. 6).'
            ),
            self::definition(
                'freeDayAnnouncement',
                'NV Bühne: Ankündigung freier Tage',
                'freeDayAnnouncementLeadTime',
                14.0,
                'NV Bühne § 66 Abs. 7',
                'Freie Tage werden der Bühnentechnik spätestens zwei Wochen vorher verbindlich angekündigt.',
                'Ein freier Tag wurde weniger als 14 Tage vorher eingetragen (NV Bühne § 66 Abs. 7).'
            ),
            self::definition(
                'weeklyPlan',
                'NV Bühne: Wochenplan bis Donnerstag 14 Uhr',
                'weeklyPlanCommitDeadline',
                0.0,
                'NV Bühne § 6 Abs. 1',
                'Der Wochenplan wird in der Vorwoche bis Donnerstag 14 Uhr veröffentlicht. Ist der '
                    . 'Donnerstag ein Feiertag, gilt der Werktag davor.',
                'Dienste der Woche sind nach Ablauf der Wochenplan-Frist noch nicht verbindlich '
                    . '(NV Bühne § 6 Abs. 1).',
                ['weekday' => 4, 'time' => '14:00', 'weeks_ahead' => 1]
            ),
            self::definition(
                'overtimeDeadline',
                'NV Bühne: Ausgleich von Mehrstunden',
                'overtimeDeadline',
                14.0,
                'NV Bühne § 64 Abs. 5',
                'Mehrstunden sind bis zum Ende der sechsten folgenden Kalenderwoche auszugleichen. Die '
                    . 'Frist selbst steht im Vertrag (6 Kalenderwochen), die Regel warnt 14 Tage vor Ablauf.',
                'Offene Mehrstunden nähern sich dem Ende der Ausgleichsfrist oder haben es überschritten '
                    . '(NV Bühne § 64 Abs. 5).'
            ),
        ];
    }

    /**
     * @param array<string, mixed>|null $config
     * @return array<string, mixed>
     */
    private static function definition(
        string $key,
        string $name,
        string $triggerType,
        float $value,
        string $legalBasis,
        string $reason,
        string $description,
        ?array $config = null,
        ?float $compensationDays = null,
        ?int $compensationDeadlineDays = null
    ): array {
        return [
            'key' => $key,
            'name' => $name,
            'description' => $description,
            'trigger_type' => $triggerType,
            'individual_number_value' => $value,
            'period_weeks' => null,
            'config' => $config,
            'default_compensation_days' => $compensationDays,
            'default_compensation_deadline_days' => $compensationDeadlineDays,
            'legal_basis' => $legalBasis,
            'reason' => $reason,
        ];
    }
}
