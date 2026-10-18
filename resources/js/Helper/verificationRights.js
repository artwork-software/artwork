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
