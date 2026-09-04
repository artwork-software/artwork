/**
 * Gemeinsame Quelle für Regeltypen des Regel-Engines (Settings "Regeln & Verstöße", Vertragszuordnung,
 * Marker im Schichtplan und in der Prüfansicht): Label, Einheit/Eingabeart des Werts, Erklärung und die
 * Formatierung des Messwerts aus violation_data ("9,5 h von max. 8 h").
 *
 * Labels/Hinweise sind Übersetzungsschlüssel (englisch) — im Template über $t() auflösen.
 * valueKind: 'hours' | 'days' | 'time' | 'none'
 *   - hours: Zahl mit Suffix "h"
 *   - days:  Zahl mit Suffix "Tage"
 *   - time:  Uhrzeit-Picker (HH:MM), intern Dezimalstunde (14.5 = 14:30)
 *   - none:  kein Zahlenwert (Feld ausblenden)
 */
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
    },
    restTimeBeforeHoliday: {
        label: 'Rest time before a Sunday or special day',
        valueKind: 'hours',
        placeholder: '11',
        hint: 'Checks the rest time before a Sunday or special day. Value = minimum rest time in hours.',
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
        hint: 'Checks that no half day off is planned on a special day. Requires the special-day rule to be active in the assigned contract.',
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
    switch (kind) {
        case 'hours':
            return `${formatNumber(value)} h`
        case 'days':
            return `${formatNumber(value)} ${t('Days')}`
        case 'time':
            return decimalHourToTime(value)
        default:
            return '–'
    }
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

    if (data.type === 'compensation_deadline_expired') {
        return t('Compensation deadline expired')
    }

    const type = violation?.shift_rule?.trigger_type ?? violation?.trigger_type
    const ofMax = (actual, max) => t('{actual} of max. {max}', { actual, max })
    const ofMin = (actual, min) => t('{actual} of min. {min}', { actual, min })

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
        return t('Half day off on a special day')
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
