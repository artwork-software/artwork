/**
 * Wer im Planungskalender Prüfungen anfragen bzw. zurückziehen darf – Spiegel von
 * EventVerificationController::mayManageVerification: Admin, "Geplante Termine bearbeiten" oder die anlegende
 * Person. Fremde Termine einer Auswahl überspringt der Server mit Hinweis.
 *
 * Reines JS-Modul ohne Vue-Abhängigkeit (tests/Frontend/verificationRights.test.js).
 */

/**
 * Sammel-Schaltfläche "Prüfung anfragen": für alle, die geplante Termine anlegen dürfen ("Im Planungskalender
 * fest planen"), und für alle mit mindestens einem eigenen geplanten Termin in der Auswahl – z. B. Raumadmins,
 * die in ihren Räumen auch ohne dieses Recht geplante Termine anlegen.
 *
 * @param {{isAdmin: boolean, canEditPlanning: boolean, canPlanFixed: boolean, userId: number|null|undefined,
 *     selectedEvents: Array<{isPlanning?: boolean, created_by?: {id?: number}|null}|null|undefined>}} options
 * @returns {boolean}
 */
export function canRequestVerificationInBulk({ isAdmin, canEditPlanning, canPlanFixed, userId, selectedEvents }) {
    if (isAdmin || canEditPlanning || canPlanFixed) {
        return true;
    }
    if (userId === null || userId === undefined) {
        return false;
    }

    return (selectedEvents || []).some((event) => !!event?.isPlanning && event?.created_by?.id === userId);
}

/**
 * Sammel-Schaltflächen "Genehmigen"/"Ablehnen": nur, wenn die Auswahl mindestens einen Termin mit offener Prüfung
 * enthält, für dessen Terminart die Person prüfen darf – Spiegel des Kachel-Menüs (FullEventInCalendar). Vorher
 * reichte die Prüfer-Zuordnung zu irgendeiner Terminart; der Server übersprang den Rest stumm.
 *
 * @param {{verifierForEventTypIds: Array<number>|null|undefined,
 *     selectedEvents: Array<{hasVerification?: boolean, eventType?: {id?: number}|null}|null|undefined>}} options
 * @returns {boolean}
 */
export function canAnswerVerificationInBulk({ verifierForEventTypIds, selectedEvents }) {
    const verifiableTypeIds = new Set((verifierForEventTypIds || []).map(Number));
    if (verifiableTypeIds.size === 0) {
        return false;
    }

    return (selectedEvents || []).some((event) => !!event?.hasVerification
        && event?.eventType?.id !== undefined
        && verifiableTypeIds.has(Number(event.eventType.id)));
}

/**
 * Ob "Prüfung anfragen" den geplanten Termin für die Person sofort fest übernimmt – Spiegel von
 * EventVerificationService::approvesOnRequest: Termin ohne Terminart oder eine Terminart aus
 * approvedOnRequestEventTypeIds (ohne Prüfung, festgelegte Prüfperson, Modus "eine Person" mit der Person in der Liste).
 *
 * @param {{event: {eventType?: {id?: number}|null}|null|undefined,
 *     approvedOnRequestEventTypeIds: Array<number>|null|undefined}} options
 * @returns {boolean}
 */
export function isApprovedOnRequest({ event, approvedOnRequestEventTypeIds }) {
    if (!event) {
        return false;
    }
    if (event.eventType?.id === undefined || event.eventType?.id === null) {
        return true;
    }

    return (approvedOnRequestEventTypeIds || []).map(Number).includes(Number(event.eventType.id));
}

/**
 * Ob die Person den Termin mit "Genehmigen" fest übernehmen darf – Spiegel von
 * EventVerificationController::approveFor:
 * - Admins: jeden geplanten Termin, mit oder ohne offene Prüfung (Entscheidung 20.10.2026)
 * - Prüfende: offene Prüfungen ihrer Terminarten
 * - wer den Termin anfragen darf und ihn dabei sofort genehmigen würde: geplante Termine ohne offene Prüfung
 *
 * @param {{isAdmin: boolean, canEditPlanning: boolean, userId: number|null|undefined,
 *     verifierForEventTypIds: Array<number>|null|undefined, approvedOnRequestEventTypeIds: Array<number>|null|undefined,
 *     event: {isPlanning?: boolean, hasVerification?: boolean, eventType?: {id?: number}|null,
 *     created_by?: {id?: number}|null}|null|undefined}} options
 * @returns {boolean}
 */
export function canApproveEvent({
    isAdmin,
    canEditPlanning,
    userId,
    verifierForEventTypIds,
    approvedOnRequestEventTypeIds,
    event,
}) {
    if (!event) {
        return false;
    }
    if (isAdmin && event.isPlanning) {
        return true;
    }
    if (event.hasVerification) {
        return event.eventType?.id !== undefined
            && (verifierForEventTypIds || []).map(Number).includes(Number(event.eventType.id));
    }
    if (!event.isPlanning) {
        return false;
    }

    const mayManageVerification = canEditPlanning
        || (userId !== null && userId !== undefined && event.created_by?.id === userId);

    return mayManageVerification && isApprovedOnRequest({ event, approvedOnRequestEventTypeIds });
}

/**
 * Sammel-Schaltfläche "Genehmigen": mindestens ein Termin der Auswahl, den die Person genehmigen darf.
 *
 * @param {Parameters<typeof canApproveEvent>[0] & {selectedEvents: Array<object|null|undefined>}} options
 * @returns {boolean}
 */
export function canApproveInBulk({ selectedEvents, ...rights }) {
    return (selectedEvents || []).some((event) => canApproveEvent({ ...rights, event }));
}

/**
 * Sammel-Schaltfläche "Prüfung anfragen" ausblenden, wenn sie für jeden anfragbaren Termin der Auswahl nur sofort
 * genehmigen würde – das übernimmt dann "Genehmigen". Anfragbar: geplant, ohne offene Prüfung, eigene Termine bzw.
 * alle mit "Geplante Termine bearbeiten"/Admin.
 *
 * @param {{isAdmin: boolean, canEditPlanning: boolean, userId: number|null|undefined,
 *     approvedOnRequestEventTypeIds: Array<number>|null|undefined, selectedEvents: Array<object|null|undefined>}} options
 * @returns {boolean}
 */
export function requestOnlyApprovesSelection({
    isAdmin,
    canEditPlanning,
    userId,
    approvedOnRequestEventTypeIds,
    selectedEvents,
}) {
    const requestableEvents = (selectedEvents || []).filter((event) => !!event?.isPlanning
        && !event.hasVerification
        && (isAdmin || canEditPlanning
            || (userId !== null && userId !== undefined && event.created_by?.id === userId)));

    return requestableEvents.length > 0
        && requestableEvents.every((event) => isApprovedOnRequest({ event, approvedOnRequestEventTypeIds }));
}
