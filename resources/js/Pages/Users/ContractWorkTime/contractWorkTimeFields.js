import dayjs from "dayjs";

/**
 * Gemeinsame Felddefinitionen des Tabs "Vertrag & Arbeitszeit".
 * Labels sind $t()-Keys (englisch) – Übersetzung in lang/de.json.
 */

// Spielzeitbezogene Infodaten (DP-18) – gleiche Definition wie in der Vertragsvorlage
export const seasonInfoParams = [
    { key: 'free_sundays_per_season', activeKey: 'free_sundays_per_season_active', label: 'Free Sundays per season' },
    { key: 'days_off_first_26_weeks', activeKey: 'days_off_first_26_weeks_active', label: 'Days off in the first 26 weeks', step: '0.5', decimals: 2 },
    { key: 'free_sundays_sat_mon_per_half', activeKey: 'free_sundays_sat_mon_per_half_active', label: 'Free Sundays connected with Saturday/Monday per season half' },
    { key: 'free_sundays_and_saturdays_per_season', activeKey: 'free_sundays_and_saturdays_per_season_active', label: 'Free Sundays + Saturdays per season' },
    { key: 'free_sundays_per_calendar_year', activeKey: 'free_sundays_per_calendar_year_active', label: 'Free Sundays per calendar year' },
    { key: 'one_and_half_day_combinations', activeKey: 'one_and_half_day_combinations_active', label: '1.5-day combinations' },
];

// Label je Vertragsfeld (für Abweichungs-Chips und Zusammenfassung)
export const contractFieldLabels = {
    free_full_days_per_week: 'Free Full Days Per Week',
    free_half_days_per_week: 'Free Half Days Per Week',
    special_day_rule_active: 'Special Day Rule Active',
    compensation_period: 'Compensation Period (in days)',
    overtime_rule_active: 'Overtime rule active',
    overtime_compensation_period: 'Period within which overtime must be reduced (days)',
    annual_vacation_days: 'Annual vacation days (per calendar year)',
    ...Object.fromEntries(seasonInfoParams.flatMap(param => [
        [param.key, param.label],
        [param.activeKey, `${param.label} (active)`],
    ])),
};

export const weekDays = [
    { key: 'monday', label: 'Monday', short: 'Mon' },
    { key: 'tuesday', label: 'Tuesday', short: 'Tue' },
    { key: 'wednesday', label: 'Wednesday', short: 'Wed' },
    { key: 'thursday', label: 'Thursday', short: 'Thu' },
    { key: 'friday', label: 'Friday', short: 'Fri' },
    { key: 'saturday', label: 'Saturday', short: 'Sat' },
    { key: 'sunday', label: 'Sunday', short: 'Sun' },
];

/** Leere Vertragswerte (kein Vertrag / individuell) */
export const emptyContractValues = () => ({
    user_contract_id: null,
    free_full_days_per_week: 0,
    free_half_days_per_week: 0,
    special_day_rule_active: true,
    compensation_period: 0,
    overtime_rule_active: false,
    overtime_compensation_period: null,
    free_sundays_per_season: 0,
    days_off_first_26_weeks: 0,
    free_sundays_per_season_active: false,
    days_off_first_26_weeks_active: false,
    free_sundays_sat_mon_per_half: 0,
    free_sundays_sat_mon_per_half_active: false,
    free_sundays_and_saturdays_per_season: 0,
    free_sundays_and_saturdays_per_season_active: false,
    free_sundays_per_calendar_year: 0,
    free_sundays_per_calendar_year_active: false,
    one_and_half_day_combinations: 0,
    one_and_half_day_combinations_active: false,
    annual_vacation_days: 0,
});

/** Vertragswerte aus Vorlage ODER bestehender Zuweisung übernehmen (Vorlagen-ID getrennt) */
export const contractValuesFrom = (source, userContractId = null) => {
    const base = emptyContractValues();
    if (!source) {
        return { ...base, user_contract_id: userContractId };
    }
    const values = {};
    Object.keys(base).forEach((key) => {
        if (key === 'user_contract_id') {
            return;
        }
        values[key] = source[key] ?? base[key];
    });
    return { ...values, user_contract_id: userContractId };
};

export const emptyWorkTimeValues = () => ({
    work_time_pattern_id: null,
    monday: '00:00',
    tuesday: '00:00',
    wednesday: '00:00',
    thursday: '00:00',
    friday: '00:00',
    saturday: '00:00',
    sunday: '00:00',
});

/** "08:00:00" / "08:00" → "08:00"; leer → "00:00" */
export const normalizeTime = (value) => {
    if (!value || typeof value !== 'string') {
        return '00:00';
    }
    const [hours = '0', minutes = '0'] = value.split(':');
    return `${String(parseInt(hours, 10) || 0).padStart(2, '0')}:${String(parseInt(minutes, 10) || 0).padStart(2, '0')}`;
};

export const workTimeValuesFrom = (source, patternId = null) => {
    const values = emptyWorkTimeValues();
    if (source) {
        weekDays.forEach(day => {
            values[day.key] = normalizeTime(source[day.key]);
        });
    }
    values.work_time_pattern_id = patternId;
    return values;
};

export const timeToMinutes = (value) => {
    if (!value || typeof value !== 'string' || !value.includes(':')) {
        return 0;
    }
    const [hours, minutes] = value.split(':');
    return (parseInt(hours, 10) || 0) * 60 + (parseInt(minutes, 10) || 0);
};

/** Minuten → "38:30 h" */
export const formatMinutesAsHours = (totalMinutes) => {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours}:${String(minutes).padStart(2, '0')} h`;
};

export const weeklyMinutes = (values) => weekDays.reduce((sum, day) => sum + timeToMinutes(values?.[day.key]), 0);

export const formatDate = (value) => (value ? dayjs(value).format('DD.MM.YYYY') : '');

/** Zeitraum-Text: "01.01.2026 – offen" / "ab Beginn – 31.12.2026" */
export const formatPeriod = (from, until, $t) => {
    const start = from ? formatDate(from) : $t('from the beginning');
    const end = until ? formatDate(until) : $t('open-ended');
    return `${start} – ${end}`;
};

/** Gilt der Satz (valid_from/valid_until, null = offen) am Tag? */
export const coversDate = (entry, date) => {
    if (!entry) {
        return false;
    }
    if (entry.valid_from && entry.valid_from > date) {
        return false;
    }
    return !entry.valid_until || entry.valid_until >= date;
};

/** Am Tag gültiger Satz – bei Überlappung gewinnt der jüngste valid_from */
export const entryValidOn = (entries, date) => {
    const matching = (entries ?? []).filter(entry => coversDate(entry, date));
    matching.sort((a, b) => (b.valid_from ?? '').localeCompare(a.valid_from ?? ''));
    return matching[0] ?? null;
};

export const addDays = (date, days) => dayjs(date).add(days, 'day').format('YYYY-MM-DD');
