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
