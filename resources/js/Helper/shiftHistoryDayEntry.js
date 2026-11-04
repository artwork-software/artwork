/**
 * Tageseinträge einer Person im Schichtverlauf (Abwesenheit, Verfügbarkeit, Notiz) — Gegenstück zu
 * ShiftActivityPresenter::dayEntry()/dayEntryDescription() im Excel-Export. Inhalt aus
 * properties.day_entry (LogsShiftPlanDayActivity), bei älteren Einträgen aus attributes bzw. old.
 */

export const DAY_ENTRY_KINDS = {
    'Artwork\\Modules\\Vacation\\Models\\Vacation': 'absence',
    'Artwork\\Modules\\Availability\\Models\\Availability': 'availability',
    'Artwork\\Modules\\Shift\\Models\\ShiftPlanComment': 'note',
};

const MESSAGES = {
    absence: { created: 'Absence entered: {0}', updated: 'Absence changed: {0}', deleted: 'Absence removed: {0}' },
    availability: { created: 'Availability entered: {0}', updated: 'Availability changed: {0}', deleted: 'Availability removed: {0}' },
    note: { created: 'Note added: {0}', updated: 'Note changed: {0}', deleted: 'Note removed: {0}' },
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
 * @param {{subject_type?: string|null, event?: string|null, properties?: object|null}} log
 * @returns {{kind: string, day: string|null, data: object}|null}
 */
export function dayEntryOf(log) {
    const kind = DAY_ENTRY_KINDS[log?.subject_type ?? ''] ?? null;
    if (!kind) return null;
    const properties = log.properties ?? {};
    const data = properties.day_entry && typeof properties.day_entry === 'object'
        ? properties.day_entry
        : ((log.event === 'deleted' ? properties.old : properties.attributes) ?? {});
    const day = properties.worker_day ?? properties.attributes?.date ?? properties.old?.date ?? null;
    return { kind, day: day ? String(day).slice(0, 10) : null, data };
}

/** Zeit des Eintrags: "08:00 – 12:00", Vormittag/Nachmittag oder ganztägig (übersetzt über t) */
export function dayEntryTime(data, t = (key) => key) {
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
    const type = String(data.type ?? '');
    const parts = [];
    if (entry?.kind === 'absence' && type) {
        parts.push(t(ABSENCE_TYPE_LABELS[type] ?? type));
    }
    const time = dayEntryTime(data, t);
    if (time) parts.push(time);
    // Abwesenheiten speichern im Kommentar teils nur den Typ — nicht doppelt zeigen
    if (comment && comment !== type) parts.push(`„${comment}“`);
    return parts.length ? parts.join(' · ') : '–';
}

/** Übersetzungsschlüssel der Meldung je Art und Vorgang (created/updated/deleted) */
export function dayEntryMessageKey(entry, event) {
    const messages = MESSAGES[entry?.kind];
    if (!messages) return null;
    return messages[event] ?? messages.updated;
}
