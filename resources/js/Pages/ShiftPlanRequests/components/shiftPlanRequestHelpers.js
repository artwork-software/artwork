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
