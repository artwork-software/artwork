/**
 * Tageseinträge einer Person im Schichtverlauf (Abwesenheit, Verfügbarkeit, Notiz, individuelle Zeit,
 * Ausgleichstag, Projektzuordnung) — Gegenstück zu ShiftActivityPresenter::dayEntry()/dayEntryDescription()
 * im Excel-Export. Inhalt aus properties.day_entry (LogsShiftPlanDayActivity), bei älteren Einträgen aus
 * attributes bzw. old.
 */

export const DAY_ENTRY_KINDS = {
    'Artwork\\Modules\\Vacation\\Models\\Vacation': 'absence',
    'Artwork\\Modules\\Availability\\Models\\Availability': 'availability',
    'Artwork\\Modules\\Shift\\Models\\ShiftPlanComment': 'note',
    'Artwork\\Modules\\IndividualTimes\\Models\\IndividualTime': 'individual_time',
    'Artwork\\Modules\\Shift\\Models\\CompensationDayOff': 'compensation',
    'Artwork\\Modules\\Project\\Models\\ProjectDayAssignment': 'project_assignment',
};

/** Über den Log-Namen erkannt: Projektzuordnungen (teils ohne Subject) und Tagesdienste (Subject = Dienst) */
const DAY_ENTRY_LOG_NAMES = {
    project_day_assignment: 'project_assignment',
    day_service: 'day_service',
};

const MESSAGES = {
    absence: { created: 'Absence entered: {0}', updated: 'Absence changed: {0}', deleted: 'Absence removed: {0}' },
    availability: { created: 'Availability entered: {0}', updated: 'Availability changed: {0}', deleted: 'Availability removed: {0}' },
    note: { created: 'Note added: {0}', updated: 'Note changed: {0}', deleted: 'Note removed: {0}' },
    individual_time: {
        created: 'Individual time entered: {0}',
        updated: 'Individual time changed: {0}',
        deleted: 'Individual time removed: {0}',
    },
    compensation: {
        created: 'Compensation day off entered: {0}',
        updated: 'Compensation day off changed: {0}',
        deleted: 'Compensation day off removed: {0}',
        deleted_with_reason: 'Compensation day off removed: {0}',
    },
    day_service: { created: 'Day service assigned: {0}', deleted: 'Day service removed: {0}', updated: 'Day service assigned: {0}' },
    // Projektzuordnungen bringen ihre Meldung als translation_key mit
};

/** Rubrik im Verlauf je Art */
const CATEGORIES = {
    absence: 'availability',
    availability: 'availability',
    note: 'note',
    individual_time: 'day_planning',
    compensation: 'day_planning',
    project_assignment: 'day_planning',
    day_service: 'day_planning',
};

const ABSENCE_TYPE_LABELS = {
    // Bezeichnungen wie im Tagesdialog des Dienstplans
    OFF_WORK: 'Work-free day',
    NOT_AVAILABLE: 'Not available',
    FREE_WORK: 'Day off',
    AVAILABLE: 'Available',
};

const timeOfDay = (value) => {
    if (value === null || value === undefined || value === '') return null;
    const match = String(value).match(/(\d{2}:\d{2})(:\d{2})?$/);
    return match ? match[1] : null;
};

/**
 * @param {{subject_type?: string|null, log_name?: string|null, event?: string|null, properties?: object|null}} log
 * @returns {{kind: string, day: string|null, dayEnd: string|null, data: object}|null}
 */
export function dayEntryOf(log) {
    const kind = DAY_ENTRY_KINDS[log?.subject_type ?? ''] ?? DAY_ENTRY_LOG_NAMES[log?.log_name ?? ''] ?? null;
    if (!kind) return null;
    const properties = log.properties ?? {};
    const data = properties.day_entry && typeof properties.day_entry === 'object'
        ? properties.day_entry
        : ((log.event === 'deleted' ? properties.old : properties.attributes) ?? {});
    const day = properties.worker_day ?? properties.attributes?.date ?? properties.old?.date ?? null;
    const dayEnd = properties.worker_day_end ?? null;
    return {
        kind,
        day: day ? String(day).slice(0, 10) : null,
        dayEnd: dayEnd ? String(dayEnd).slice(0, 10) : null,
        data,
    };
}

/** Rubrik des Eintrags im Verlauf (availability | note | day_planning) */
export function dayEntryCategory(entry) {
    return CATEGORIES[entry?.kind] ?? 'availability';
}

/** Zeit des Eintrags: "08:00 – 12:00", Vormittag/Nachmittag oder ganztägig (übersetzt über t) */
export function dayEntryTime(data, t = (key) => key) {
    if (data?.kind === 'compensation') {
        if (data?.half_day_period === 'morning') return t('Morning');
        if (data?.half_day_period === 'afternoon') return t('Afternoon');
        return Number(data?.value) === 0.5 ? t('Half day') : t('Full day');
    }
    if (data?.kind === 'project_assignment' || data?.kind === 'day_service') return '';
    const start = timeOfDay(data?.start_time);
    const end = timeOfDay(data?.end_time);
    if (!data?.full_day && start && end) return `${start} – ${end}`;
    if (data?.day_part === 'morning') return t('Morning');
    if (data?.day_part === 'afternoon') return t('Afternoon');
    return data?.full_day ? t('Full day') : '';
}

/** "Nicht verfügbar · Ganztägig · „Arzttermin“" bzw. bei Notizen „Text“ */
export function describeDayEntry(entry, t = (key) => key) {
    const data = entry?.data ?? {};
    const comment = String(data.comment ?? '').trim();
    if (entry?.kind === 'note') {
        return comment ? `„${comment}“` : '–';
    }
    const parts = [];
    if (entry?.kind === 'individual_time') {
        const title = String(data.title ?? '').trim();
        if (title) parts.push(title);
    }
    if (entry?.kind === 'day_service') {
        const name = String(data.name ?? '').trim();
        if (name) parts.push(name);
    }
    const type = String(data.type ?? '');
    if (entry?.kind === 'absence' && type) {
        parts.push(t(ABSENCE_TYPE_LABELS[type] ?? type));
    }
    const time = dayEntryTime(data, t);
    if (time) parts.push(time);
    if (entry?.kind === 'compensation' && data.for_holiday) parts.push(t('For a public holiday'));
    // Abwesenheiten speichern im Kommentar teils nur den Typ — nicht doppelt zeigen
    if (comment && comment !== type) parts.push(`„${comment}“`);
    return parts.length ? parts.join(' · ') : '–';
}

/** Übersetzungsschlüssel der Meldung je Art und Vorgang (created/updated/deleted); null = translation_key nutzen */
export function dayEntryMessageKey(entry, event) {
    const messages = MESSAGES[entry?.kind];
    if (!messages) return null;
    return messages[event] ?? messages.updated;
}

/** Tag bzw. Zeitraum "25.11.2026" / "25.11.2026 – 27.11.2026" (formatDay: Y-m-d → Anzeige) */
export function dayEntryDayLabel(entry, formatDay = (day) => day) {
    if (!entry?.day) return '–';
    if (entry.dayEnd && entry.dayEnd !== entry.day) return `${formatDay(entry.day)} – ${formatDay(entry.dayEnd)}`;
    return formatDay(entry.day);
}
