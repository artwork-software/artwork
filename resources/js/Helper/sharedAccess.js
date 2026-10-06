/**
 * Freigabelisten von Projektdateien und Verträgen. Je nach Quelle heißen sie unterschiedlich:
 * - Eloquent-Modelle (Budget-Informationen) serialisieren die Relation als accessing_users /
 *   accessing_departments,
 * - aufbereitete Arrays (Vertragsübersicht, Verträge & Dokumente) liefern accessibleUsers /
 *   accessibleDepartments.
 * Wer eine Freigabeliste liest oder ein Bearbeiten-Formular vorbelegt, nutzt diese Helfer – sonst startet
 * das Formular leer und das Speichern entzieht allen anderen Freigegebenen den Zugriff.
 */

function firstList(...candidates) {
    const list = candidates.find((candidate) => Array.isArray(candidate));

    return list ? [...list] : [];
}

/**
 * @param {object|null|undefined} item Projektdatei oder Vertrag
 * @returns {Array<{ id: number }>} Kopie der Freigabeliste (Personen)
 */
export function sharedUsersOf(item) {
    return firstList(item?.accessibleUsers, item?.accessing_users, item?.accessingUsers);
}

/**
 * @param {object|null|undefined} item Vertrag
 * @returns {Array<{ id: number }>} Kopie der Freigabeliste (Abteilungen)
 */
export function sharedDepartmentsOf(item) {
    return firstList(item?.accessibleDepartments, item?.accessing_departments, item?.accessingDepartments);
}

/**
 * Nur zur Anzeige: Vertragsübersicht (ContractResource) liefert displayedAccessUsers = Freigaben plus
 * Projektleitungen. Diese Liste nie in ein Formular übernehmen – gespeichert wird nur sharedUsersOf().
 *
 * @param {object|null|undefined} item
 * @returns {Array<{ id: number }>}
 */
export function displayedAccessUsersOf(item) {
    return Array.isArray(item?.displayedAccessUsers) ? [...item.displayedAccessUsers] : sharedUsersOf(item);
}
