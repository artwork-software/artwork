import { isoWeekToDateRange, toDmy } from '../../../Helper/IsoWeek.js';

/**
 * Reine Helfer der Freigabe-Anfragen (ohne Vue/Inertia), damit sie in Node-Tests laufen
 * (tests/Frontend/shiftPlanRequestHelpers.test.js). useShiftPlanRequestActions bindet sie an
 * die eingeloggte Person. Relativer Import statt „@/", weil Node den Vite-Alias nicht kennt.
 */

/**
 * Ist die Person die Antragstellerin? requested_by_user_id fehlt in manchen Listen-Payloads
 * (my.index) → assumeOwn erlaubt dem Aufrufer, die Zugehörigkeit aus dem Kontext zu setzen.
 * @param {object|null|undefined} request
 * @param {number} authUserId
 * @param {boolean} assumeOwn
 */
export function isRequesterOf(request, authUserId, assumeOwn = false) {
    if (!request) return false;
    if (request.requested_by_user_id !== undefined && request.requested_by_user_id !== null) {
        return Number(request.requested_by_user_id) === Number(authUserId);
    }
    if (request.requested_by?.id !== undefined) {
        return Number(request.requested_by.id) === Number(authUserId);
    }
    return assumeOwn;
}

/** Zurückziehen nur bei pending und eigener Anfrage */
export function canWithdrawRequest(request, authUserId, assumeOwn = false) {
    return request?.status === 'pending' && isRequesterOf(request, authUserId, assumeOwn);
}

/** Erneut einreichen nur bei rejected und eigener Anfrage */
export function canResubmitRequest(request, authUserId, assumeOwn = false) {
    return request?.status === 'rejected' && isRequesterOf(request, authUserId, assumeOwn);
}

/** "KW 37 / 2026 (07.09.2026 – 13.09.2026)"; ohne gültige KW nur "KW 37 / 2026" bzw. Striche */
export function shiftPlanRequestWeekLabel(request) {
    const range = isoWeekToDateRange(request?.week_number, request?.year);
    if (!range) return `KW ${request?.week_number ?? '–'} / ${request?.year ?? '–'}`;
    return `KW ${request.week_number} / ${request.year} (${toDmy(range.monday)} – ${toDmy(range.sunday)})`;
}

/**
 * Dauer einer Schicht in Stunden (Ende minus Start minus Pause). Endet die Schicht vor ihrem Start,
 * läuft sie über Mitternacht (z. B. 22:00–06:00).
 * @param {{start?: string, end?: string, break_minutes?: number}} shift
 */
export function shiftDurationHours(shift) {
    if (!shift?.start || !shift?.end) return 0;
    const [sh, sm] = String(shift.start).split(':').map(Number);
    const [eh, em] = String(shift.end).split(':').map(Number);
    let diff = (eh * 60 + em) - (sh * 60 + sm);
    if (diff < 0) diff += 24 * 60;
    if (shift.break_minutes) diff -= shift.break_minutes;
    return diff > 0 ? diff / 60 : 0;
}

/**
 * Hängt gewerksfremde Schichten (Prop foreignCraftShifts) an die vorhandenen Personen-Zeilen der
 * Prüfansicht. Neue Zeilen entstehen dabei nicht: gezeigt wird nur, wer schon eine Zeile hat.
 * Die Einträge zählen nicht als Schichten der Anfrage, sondern in totals.foreign_shifts/_hours.
 * @param {Map<string, {days: Object<string, Array>, totals: Object}>} rowsByKey
 * @param {Array} foreignCraftShifts
 * @param {Array<string>} dayDates
 * @param {Object<number, string>} qualificationNames
 */
export function appendForeignCraftEntries(rowsByKey, foreignCraftShifts, dayDates, qualificationNames = {}) {
    for (const shift of (foreignCraftShifts || [])) {
        const date = shift.date;
        if (!date || !dayDates.includes(date)) continue;
        for (const worker of (shift.workers || [])) {
            const row = rowsByKey.get(`${worker.type}-${worker.id}`);
            if (!row) continue;
            if (!row.days[date]) row.days[date] = [];
            const uniqueKey = `foreign-${shift.id}-${worker.type}-${worker.id}`;
            if (row.days[date].some(e => e.unique_key === uniqueKey)) continue;
            row.days[date].push({
                unique_key: uniqueKey,
                is_foreign_craft: true,
                shift_id: shift.id,
                start_time: shift.start,
                end_time: shift.end,
                room_name: shift.room_name || null,
                qualification: qualificationNames[worker.shift_qualification_id] ?? null,
                is_committed: !!shift.is_committed,
                craft_name: shift.craft?.name || null,
                craft_abbreviation: shift.craft?.abbreviation || shift.craft?.name || null,
            });
            row.totals.foreign_shifts = (row.totals.foreign_shifts || 0) + 1;
            row.totals.foreign_hours = (row.totals.foreign_hours || 0) + shiftDurationHours(shift);
        }
    }
}

/** Tageseinträge chronologisch sortieren (stabil; Einträge ohne Startzeit, z. B. ganztägig, zuerst) */
export function sortDayEntriesByStart(entries) {
    return entries.sort((a, b) => String(a.start_time || '').localeCompare(String(b.start_time || '')));
}

/**
 * Hängt Abwesenheiten (Prop workerAbsences.absences: alles außer „Verfügbar“) an die vorhandenen
 * Personen-Zeilen der Prüfansicht. Wie bei gewerksfremden Schichten entstehen keine neuen Zeilen.
 * @param {Map<string, {days: Object<string, Array>}>} rowsByKey
 * @param {Array} absences
 * @param {Array<string>} dayDates
 */
export function appendAbsenceEntries(rowsByKey, absences, dayDates) {
    for (const absence of (absences || [])) {
        const date = absence.date;
        if (!date || !dayDates.includes(date)) continue;
        const row = rowsByKey.get(`${absence.worker_type}-${absence.worker_id}`);
        if (!row) continue;
        if (!row.days[date]) row.days[date] = [];
        const uniqueKey = `absence-${absence.id}`;
        if (row.days[date].some(e => e.unique_key === uniqueKey)) continue;
        row.days[date].push({
            unique_key: uniqueKey,
            is_absence: true,
            absence_type: absence.type,
            full_day: !!absence.full_day,
            day_part: absence.day_part || null,
            start_time: absence.full_day ? null : (absence.start_time || null),
            end_time: absence.full_day ? null : (absence.end_time || null),
            comment: absence.comment || null,
        });
    }
}

/** Bezeichnung wie im Tagesdialog des Dienstplans (Übersetzungs-Keys) */
export const ABSENCE_TYPE_LABEL_KEYS = {
    OFF_WORK: 'Work-free day',
    NOT_AVAILABLE: 'Not available',
    FREE_WORK: 'Day off',
};

/** "08:00 – 12:00", Vormittag/Nachmittag oder ganztägig */
export function absenceTimeLabel(entry, t = (key) => key) {
    if (!entry?.full_day && entry?.start_time && entry?.end_time) return `${entry.start_time} – ${entry.end_time}`;
    if (entry?.day_part === 'morning') return t('Morning');
    if (entry?.day_part === 'afternoon') return t('Afternoon');
    return t('Full day');
}
