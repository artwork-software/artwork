/**
 * Gemeinsame Quelle für Regeltypen des Regel-Engines (Settings "Regeln & Verstöße", Vertragszuordnung,
 * Marker im Schichtplan und in der Prüfansicht): Label, Einheit/Eingabeart des Werts, Erklärung und die
 * Formatierung des Messwerts aus violation_data ("9,5 h von max. 8 h").
 *
 * Labels/Hinweise sind Übersetzungsschlüssel (englisch) — im Template über $t() auflösen.
 * valueKind: 'hours' | 'days' | 'count' | 'time' | 'none'
 *   - hours: Zahl mit Suffix "h"
 *   - days:  Zahl mit Suffix "Tage"
 *   - count: Anzahl (z. B. Sonntage); mit optional: true darf der Wert leer bleiben (Ziel aus dem Vertrag)
 *   - time:  Uhrzeit-Picker (HH:MM), intern Dezimalstunde (14.5 = 14:30)
 *   - none:  kein Zahlenwert (Feld ausblenden)
 *
 * configFields: Zusatzparameter des Regeltyps (shift_rules.config). Schlüssel, Typen und Standardwerte
 * müssen zu ShiftRuleConfig (PHP) passen. kind:
 *   - bool:          Checkbox
 *   - weekday:       Wochentag (ISO 1 = Montag … 7 = Sonntag)
 *   - time:          Uhrzeit "HH:MM"
 *   - int / hours:   Zahl (hours: halbe Stunden erlaubt, leer = nicht gesetzt)
 *   - monthDay:      jährlicher Kalendertag, Eingabe "TT.MM.", gespeichert "MM-DD"
 *   - monthDayList:  mehrere Kalendertage, durch Komma getrennt
 *   - shiftGroups:   Mehrfachauswahl von Schichtgruppen (IDs)
 */

const SKIP_SAME_DAY_FIELD = {
    key: 'skip_same_day',
    kind: 'bool',
    default: false,
    label: 'Only check the rest time to the previous day',
    hint: 'Gaps between two duties on the same day are not checked by this rule. Use the rule "Rest time between two duties on the same day" for split duties.',
}

export const RULE_TYPES = {
    maxWorkingHoursOnDay: {
        label: 'Daily maximum of hours',
        valueKind: 'hours',
        placeholder: '8',
        hint: 'Checks the total planned working hours of a person per day. Value = maximum number of hours per day.',
    },
    maxConsecWorkingDays: {
        label: 'Maximum consecutive working days',
        valueKind: 'days',
        placeholder: '6',
        hint: 'Checks how many days in a row a person is scheduled without a day off. Value = maximum number of consecutive working days.',
    },
    weeklyMaxHours: {
        label: 'Weekly maximum of hours',
        valueKind: 'hours',
        placeholder: '48',
        hint: 'Checks the total planned working hours of a person per week. Value = maximum number of hours per week.',
    },
    // Alias aus Altbestand (DB/UI) — identisch zu weeklyMaxHours
    maxWorkingHoursOnWeek: {
        label: 'Weekly maximum of hours',
        valueKind: 'hours',
        placeholder: '48',
        hint: 'Checks the total planned working hours of a person per week. Value = maximum number of hours per week.',
        hidden: true,
    },
    restTimeBeforeWorkday: {
        label: 'Rest time before a working day',
        valueKind: 'hours',
        placeholder: '11',
        hint: 'Checks the rest time between the end of a shift and the start of the next shift before a regular working day. Value = minimum rest time in hours.',
        configFields: [SKIP_SAME_DAY_FIELD],
    },
    restTimeBeforeHoliday: {
        label: 'Rest time before a Sunday or special day',
        valueKind: 'hours',
        placeholder: '11',
        hint: 'Checks the rest time before a Sunday or special day. Value = minimum rest time in hours.',
        configFields: [SKIP_SAME_DAY_FIELD],
    },
    restTimeBetweenShiftGroups: {
        label: 'Rest time between shift groups',
        valueKind: 'hours',
        placeholder: '11',
        hint: 'Checks the rest time between shifts of different shift groups. Value = minimum rest time in hours. Requires maintained shift groups (tab \'shift groups\').',
    },
    halfDayOffConflict: {
        label: 'Conflict: half day off / shift',
        valueKind: 'time',
        placeholder: '14:00',
        hint: 'Checks whether a shift conflicts with a half day off. Value = time of day: a morning off requires the shift to start at or after this time, an afternoon off requires it to end at or before.',
    },
    halfDayOffOnSpecialDay: {
        label: 'No half day off on special days',
        valueKind: 'none',
        hint: 'Checks that no half day off is planned on a special day (holiday marked as special day). Applies regardless of the special-day rule in the person\'s contract.',
        configFields: [
            {
                key: 'include_sundays',
                kind: 'bool',
                default: false,
                label: 'Also applies on Sundays',
                hint: 'Without this option the rule only covers special days from the holiday calendar.',
            },
            {
                key: 'morning_only',
                kind: 'bool',
                default: false,
                label: 'Only the morning counts',
                hint: 'Only a half day off in the morning is a violation; a free afternoon stays allowed.',
            },
            {
                key: 'include_planned',
                kind: 'bool',
                default: false,
                label: 'Also check planned half days off',
                hint: 'Besides granted substitute half days, half days off entered as day status "Free" (morning or afternoon) are checked as well.',
            },
        ],
    },
    minDaysBeforeCommit: {
        label: 'Minimum days before binding commitment',
        valueKind: 'days',
        placeholder: '14',
        hint: 'Checks whether there is enough lead time between committing and the start of a shift. Value = minimum number of days before shifts become binding.',
    },
    workOnSunday: {
        label: 'Work on Sunday',
        valueKind: 'none',
        hint: 'Creates a rule violation for every Sunday on which the person has a shift (shift day = start day, also for shifts past midnight). No value required.',
    },
    workOnHoliday: {
        label: 'Work on special day',
        valueKind: 'none',
        hint: 'Creates a rule violation for every special day (public holiday flagged as special day, contract switch active) on which the person has a shift. The violation documents the entitlement to a replacement rest day. No value required.',
    },
    overtimeDeadline: {
        label: 'Overtime reduction deadline',
        valueKind: 'days',
        placeholder: '14',
        hint: 'Warns before open overtime expires. Value = number of days before the reduction deadline from which a rule violation is created; after the deadline the violation becomes an error. Applies to people whose contract has the overtime rule active. The violation has no shift — it appears on the deadline day.',
    },
    minFreeSundaysPerSeasonHalf: {
        label: 'Minimum free Sundays with Saturday/Monday per season half',
        valueKind: 'count',
        optional: true,
        placeholder: '3',
        hint: 'Checks per season half whether the person can still reach the minimum number of free Sundays with a free Saturday or Monday. Value = minimum number; leave empty to use the target from the contract. A violation is created on the last Sunday of the half when the target is no longer reachable. Without a season in the tool settings, the current calendar year counts as the season.',
    },
    minFreeSundaysPerYear: {
        label: 'Minimum free Sundays per calendar year',
        valueKind: 'count',
        optional: true,
        placeholder: '15',
        hint: 'Checks per calendar year whether the person can still reach the minimum number of free Sundays (Sunday without shift and without individual time). Value = minimum number; leave empty for the legal default of 15 (ArbZG § 11). A violation without shift is created on the last Sunday of the year as soon as the target is no longer reachable.',
    },
    averageWeeklyHours: {
        label: 'Average weekly hours over a compensation period',
        valueKind: 'hours',
        placeholder: '48',
        hasPeriodWeeks: true,
        periodPlaceholder: '24',
        hint: 'Checks for every calendar week the average of the planned weekly hours over the last n weeks (rolling compensation period, ArbZG § 3 / TVöD § 6). Value = maximum average in hours per week, period = number of weeks. One violation per week on the last shift of that week; weeks whose period has less than half of its weeks planned are not reported.',
    },
    nightWorkMaxHours: {
        label: 'Daily maximum on days with night work',
        valueKind: 'hours',
        placeholder: '8',
        hint: 'Checks days on which the person works at least 2 hours within the night window (shift settings, default 22:00–06:00): on such days the total planned working time may not exceed the value (ArbZG § 6). Value = maximum number of hours on a night work day.',
    },
    minFreeDaysPerWeek: {
        label: 'Minimum whole free days per week',
        valueKind: 'days',
        optional: true,
        placeholder: '2',
        hint: 'Checks per calendar week (Mon–Sun) whether the person has the minimum number of whole free days (day without shift and without individual time; shifts past midnight occupy both days). Value = minimum number; leave empty to use "free whole days per week" from the contract (0 = rule does not apply). The violation has no shift — it appears on the Sunday of the week.',
        configFields: [
            {
                key: 'workdays_only',
                kind: 'bool',
                default: false,
                label: 'Only free working days count',
                hint: 'A free Sunday or special day does not count as a free day.',
            },
            {
                key: 'excluded_dates',
                kind: 'monthDayList',
                default: [],
                label: 'Calendar days that never count as a free day',
                placeholder: '01.05., 24.12.',
                hint: 'Recurring every year, format DD.MM., separated by commas.',
            },
        ],
    },
    weeklyPlanCommitDeadline: {
        label: 'Weekly plan deadline',
        valueKind: 'none',
        hint: 'Checks whether the shifts of a calendar week are binding by a fixed weekday and time of the week before (e.g. Thursday 2 pm). After the deadline, every person who still has non-binding shifts in that week gets one violation on the Monday of the week. If the deadline day is a Sunday or special day, the working day before applies.',
        configFields: [
            { key: 'weekday', kind: 'weekday', default: 4, label: 'Deadline weekday' },
            { key: 'time', kind: 'time', default: '14:00', label: 'Deadline time' },
            {
                key: 'weeks_ahead',
                kind: 'int',
                default: 1,
                min: 1,
                max: 4,
                label: 'Weeks before the planned week',
                hint: '1 = the deadline lies in the week directly before the planned week.',
            },
        ],
    },
    halfDayOffMaxHours: {
        label: 'Working time on a half day off',
        valueKind: 'hours',
        placeholder: '4',
        hint: 'Checks days with exactly one granted half day off: the working time in the other half of the day may not exceed the value. Value = maximum number of hours. Whether a shift reaches into the free half is checked by the rule "Conflict: half day off / shift".',
        configFields: [
            {
                key: 'boundary',
                kind: 'time',
                default: '14:00',
                label: 'Boundary between morning and afternoon',
            },
            {
                key: 'extended_hours',
                kind: 'hours',
                default: null,
                placeholder: '5',
                label: 'Extended maximum (hours)',
                hint: 'Applies when a shift of one of the shift groups below lies in the working half, e.g. final rehearsals and performances. Leave empty for no extension.',
            },
            {
                key: 'extended_shift_group_ids',
                kind: 'shiftGroups',
                default: [],
                label: 'Shift groups with the extended maximum',
            },
        ],
    },
    restTimeBetweenShiftsOnDay: {
        label: 'Rest time between two duties on the same day',
        valueKind: 'hours',
        placeholder: '4',
        hint: 'Checks split duties: if a person has two duties on the same day with a gap in between, the gap must be at least the value. Duties that follow each other directly are not a split duty. Value = minimum gap in hours. Works without shift groups.',
        configFields: [
            {
                key: 'excluded_shift_group_ids',
                kind: 'shiftGroups',
                default: [],
                label: 'Excluded shift groups',
                hint: 'If one of the two duties belongs to one of these shift groups (e.g. final rehearsals), the gap is not checked.',
            },
        ],
    },
    freeDayAfterWorkOnDate: {
        label: 'Day off after work on a fixed date',
        valueKind: 'none',
        hint: 'Checks a pair of calendar days: whoever worked on the first day may not be scheduled on the following second day (e.g. worked on 24 December, off on 1 May).',
        configFields: [
            { key: 'worked', kind: 'monthDay', default: null, required: true, placeholder: '24.12.', label: 'Worked on (DD.MM.)' },
            { key: 'free', kind: 'monthDay', default: null, required: true, placeholder: '01.05.', label: 'Then off on (DD.MM.)' },
        ],
    },
    freeDayAnnouncementLeadTime: {
        label: 'Announcement period for days off',
        valueKind: 'days',
        placeholder: '14',
        hint: 'Checks planned days off (day status "Free", whole or half) and granted substitute days off: the day off must have been entered at least the given number of days in advance. Value = minimum lead time in days. Days that are free only because no shift was planned are not checked.',
    },
}

/** Typen für die Auswahl beim Anlegen (Alias ausgeblendet) */
export const SELECTABLE_RULE_TYPES = Object.keys(RULE_TYPES).filter((key) => !RULE_TYPES[key].hidden)

export function ruleTypeLabelKey(type) {
    return RULE_TYPES[type]?.label ?? type
}

export function ruleTypeValueKind(type) {
    return RULE_TYPES[type]?.valueKind ?? 'hours'
}

export function ruleTypeNeedsValue(type) {
    return ruleTypeValueKind(type) !== 'none'
}

/** Wert darf leer bleiben (Ziel aus dem Vertrag) */
export function ruleTypeValueOptional(type) {
    return !!RULE_TYPES[type]?.optional
}

/** Zusatzparameter des Typs (shift_rules.config), leer wenn der Typ keine kennt */
export function ruleTypeConfigFields(type) {
    return RULE_TYPES[type]?.configFields ?? []
}

/** Standard-Konfiguration eines Typs, null wenn der Typ keine Zusatzparameter kennt */
export function defaultRuleConfig(type) {
    const fields = ruleTypeConfigFields(type)
    if (!fields.length) return null
    return Object.fromEntries(fields.map((field) => [
        field.key,
        Array.isArray(field.default) ? [...field.default] : field.default,
    ]))
}

/** Übersetzungsschlüssel der Wochentage, Index = ISO-Wochentag − 1 */
export const WEEKDAY_KEYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']

/** "MM-DD" -> "TT.MM." */
export function monthDayToDisplay(value) {
    if (typeof value !== 'string' || !/^\d{2}-\d{2}$/.test(value)) return ''
    const [month, day] = value.split('-')
    return `${day}.${month}.`
}

/** "TT.MM." (auch "T.M" oder "TT.MM") -> "MM-DD"; nicht lesbare Eingaben bleiben unverändert (Server meldet sie) */
export function displayToMonthDay(value) {
    const text = String(value ?? '').trim()
    const match = text.match(/^(\d{1,2})\.\s*(\d{1,2})\.?$/)
    if (!match) return text
    return `${match[2].padStart(2, '0')}-${match[1].padStart(2, '0')}`
}

/** Typ hat zusätzlich einen Ausgleichszeitraum in Wochen (period_weeks) */
export function ruleTypeHasPeriodWeeks(type) {
    return !!RULE_TYPES[type]?.hasPeriodWeeks
}

/** Dezimalstunde -> "HH:MM" (14.5 -> "14:30") */
export function decimalHourToTime(value) {
    const number = Number(value)
    if (!Number.isFinite(number) || number < 0) return ''
    const hours = Math.floor(number)
    const minutes = Math.round((number - hours) * 60)
    const pad = (n) => String(n).padStart(2, '0')
    return `${pad(Math.min(hours, 23))}:${pad(Math.min(minutes, 59))}`
}

/** "HH:MM" -> Dezimalstunde ("14:30" -> 14.5) */
export function timeToDecimalHour(value) {
    if (typeof value !== 'string' || !/^\d{1,2}:\d{2}$/.test(value)) return null
    const [h, m] = value.split(':').map(Number)
    return h + m / 60
}

/** Zahl mit Komma als Dezimaltrenner, max. 1 Nachkommastelle */
export function formatNumber(value) {
    const number = Number(value)
    if (!Number.isFinite(number)) return String(value ?? '')
    return (Math.round(number * 10) / 10).toLocaleString('de-DE', { maximumFractionDigits: 1 })
}

/**
 * Regelwert einer Regel formatiert anzeigen (Tabelle, Vertragszuordnung).
 * @param {object} rule mit trigger_type und individual_number_value
 * @param {(key: string) => string} t Übersetzungsfunktion
 */
export function formatRuleValue(rule, t) {
    const kind = ruleTypeValueKind(rule?.trigger_type)
    const value = rule?.individual_number_value
    const config = { ...(defaultRuleConfig(rule?.trigger_type) ?? {}), ...(rule?.config ?? {}) }

    // Typen ohne Zahlenwert, deren Aussage in den Zusatzparametern steckt
    if (rule?.trigger_type === 'weeklyPlanCommitDeadline') {
        // "Donnerstag 14:00"
        return `${t(WEEKDAY_KEYS[(Number(config.weekday) || 4) - 1] ?? 'Thursday')} ${config.time ?? ''}`.trim()
    }
    if (rule?.trigger_type === 'freeDayAfterWorkOnDate') {
        // "24.12. → 01.05."
        return `${monthDayToDisplay(config.worked)} → ${monthDayToDisplay(config.free)}`
    }
    switch (kind) {
        case 'hours':
            // Wochendurchschnitt: "48 h Ø über 24 Wochen"
            if (ruleTypeHasPeriodWeeks(rule?.trigger_type)) {
                return `${formatNumber(value)} h Ø ${t('over {weeks} weeks', { weeks: rule?.period_weeks ?? '–' })}`
            }
            return `${formatNumber(value)} h`
        case 'days':
            if (ruleTypeValueOptional(rule?.trigger_type) && !(Number(value) > 0)) {
                return t('From contract')
            }
            return `${formatNumber(value)} ${t('Days')}`
        case 'count':
            return Number(value) > 0 ? formatNumber(value) : t('From contract')
        case 'time':
            return decimalHourToTime(value)
        default:
            return '–'
    }
}

/** Minuten -> "3:30 h" */
export function formatMinutesAsHours(minutes) {
    const total = Math.max(0, Math.round(Number(minutes) || 0))
    const h = Math.floor(total / 60)
    const m = total % 60
    return `${h}:${String(m).padStart(2, '0')} h`
}

/** "YYYY-MM-DD" -> "DD.MM.YYYY" */
function formatDateDe(value) {
    if (!value) return ''
    const [y, m, d] = String(value).slice(0, 10).split('-')
    return y && m && d ? `${d}.${m}.${y}` : String(value)
}

/**
 * Messwert eines Verstoßes aus violation_data (z. B. "9,5 h von max. 8 h").
 * Gibt einen leeren String zurück, wenn nichts Sinnvolles vorliegt.
 * @param {object} violation mit violation_data und shift_rule.trigger_type
 * @param {(key: string, params?: object) => string} t Übersetzungsfunktion
 */
export function formatViolationMeasure(violation, t) {
    const data = violation?.violation_data
    if (!data || typeof data !== 'object') return ''

    const ofMax = (actual, max) => t('{actual} of max. {max}', { actual, max })
    const ofMin = (actual, min) => t('{actual} of min. {min}', { actual, min })

    if (data.type === 'compensation_deadline_expired') {
        return t('Compensation deadline expired')
    }
    if (data.type === 'overtime_deadline' || data.remaining_minutes !== undefined) {
        // "3:30 h offen, Frist 30.10.2026" (+ überfällig / noch n Tage)
        const parts = [t('{hours} open, deadline {date}', {
            hours: formatMinutesAsHours(data.remaining_minutes),
            date: formatDateDe(data.deadline),
        })]
        if (data.days_left !== undefined) {
            parts.push(Number(data.days_left) < 0 ? t('Deadline expired') : t('{days} days left', { days: data.days_left }))
        }
        return parts.join(', ')
    }
    if (data.type === 'min_free_sundays_per_season_half' || data.half !== undefined) {
        // "1. Hälfte: 1 von min. 3 freien Sonntagen, noch 1 möglich"
        const half = Number(data.half) === 2 ? t('2nd half') : t('1st half')
        const base = `${half}: ${ofMin(String(data.have ?? 0), `${data.target ?? ''} ${t('free Sundays')}`)}`
        return data.completed ? base : `${base}, ${t('{count} still possible', { count: data.possible ?? 0 })}`
    }
    if (data.type === 'min_free_sundays_per_year') {
        // "2026: 4 von min. 15 freien Sonntagen, noch 8 möglich"
        const base = `${data.year ?? ''}: ${ofMin(String(data.have ?? 0), `${data.target ?? ''} ${t('free Sundays')}`)}`
        return data.completed ? base : `${base}, ${t('{count} still possible', { count: data.possible ?? 0 })}`
    }
    if (data.type === 'average_weekly_hours' || data.average_hours !== undefined) {
        // "Ø 49,2 h über 24 Wochen, max. 48 h"
        return t('Ø {hours} h over {weeks} weeks, max. {max} h', {
            hours: formatNumber(data.average_hours),
            weeks: data.period_weeks ?? '',
            max: formatNumber(data.max_allowed),
        })
    }
    if (data.type === 'night_work_max_hours' || data.night_hours !== undefined) {
        // "9,5 h (davon 3 h nachts) von max. 8 h"
        return ofMax(
            t('{hours} h ({night} h of it at night)', {
                hours: formatNumber(data.planned_hours),
                night: formatNumber(data.night_hours),
            }),
            `${formatNumber(data.max_allowed)} h`
        )
    }
    if (data.type === 'min_free_days_per_week' || data.free_days !== undefined) {
        // "KW 37: 1 von min. 2 freien Tagen"
        const week = data.week ? `${t('CW')} ${data.week}: ` : ''
        return `${week}${ofMin(String(data.free_days ?? 0), `${data.target ?? ''} ${t('free days')}`)}`
    }

    if (data.type === 'weekly_plan_commit_deadline') {
        // "KW 49: 3 Schichten nicht verbindlich, Frist 26.11.2026 14:00"
        const [date, time] = String(data.deadline ?? '').split(' ')
        return t('CW {week}: {count} shifts not binding, deadline {date}', {
            week: data.week ?? '',
            count: data.open_shifts ?? 0,
            date: `${formatDateDe(date)} ${time ?? ''}`.trim(),
        })
    }
    if (data.type === 'free_day_after_work_on_date') {
        return t('Scheduled on {free} after working on {worked}', {
            free: formatDateDe(data.free_date),
            worked: formatDateDe(data.worked_date),
        })
    }
    if (data.type === 'free_day_announcement') {
        return t('Granted {days} days in advance, min. {min} days', {
            days: data.lead_days ?? 0,
            min: data.min_required ?? '',
        })
    }

    const type = violation?.shift_rule?.trigger_type ?? violation?.trigger_type

    if (data.planned_hours !== undefined && data.max_allowed !== undefined) {
        return ofMax(`${formatNumber(data.planned_hours)} h`, `${formatNumber(data.max_allowed)} h`)
    }
    if (data.weekly_hours !== undefined && data.max_allowed !== undefined) {
        return ofMax(`${formatNumber(data.weekly_hours)} h`, `${formatNumber(data.max_allowed)} h`)
    }
    if (data.consecutive_days !== undefined && data.max_allowed !== undefined) {
        return ofMax(`${formatNumber(data.consecutive_days)} ${t('Days')}`, `${formatNumber(data.max_allowed)} ${t('Days')}`)
    }
    if (data.rest_hours !== undefined && data.min_required !== undefined) {
        return ofMin(`${formatNumber(data.rest_hours)} h`, `${formatNumber(data.min_required)} h`)
    }
    if (data.days_until_shift !== undefined && data.min_required !== undefined) {
        return ofMin(`${formatNumber(data.days_until_shift)} ${t('Days')}`, `${formatNumber(data.min_required)} ${t('Days')}`)
    }
    if (type === 'halfDayOffConflict' || data.threshold_hour !== undefined) {
        const parts = []
        if (data.half_day_period) {
            parts.push(t(data.half_day_period === 'morning' ? 'Morning off' : (data.half_day_period === 'afternoon' ? 'Afternoon off' : 'Whole day off')))
        }
        if (data.threshold_hour !== undefined) {
            parts.push(`${t('Threshold')}: ${decimalHourToTime(data.threshold_hour)}`)
        }
        return parts.join(', ')
    }
    if (type === 'halfDayOffOnSpecialDay' || data.reason === 'half_day_off_on_special_day') {
        return data.on_sunday ? t('Half day off on a Sunday') : t('Half day off on a special day')
    }
    if (type === 'workOnHoliday' || data.holiday_name) {
        return data.holiday_name
            ? t('Shift on special day {name}', { name: data.holiday_name })
            : t('Shift on a special day')
    }
    if (type === 'workOnSunday' || data.weekday === 'sunday') {
        return t('Shift on a Sunday')
    }
    return ''
}
