/**
 * Ob eine Änderung an einem fest gebuchten Termin neu angefragt werden muss, gespiegelt aus
 * EventChangeApprovalService (Backend): ohne Direktbuchrecht wird ein anderer Raum oder ein Zeitraum über die
 * bestätigte Belegung hinaus zur Raumanfrage, Verkürzen innerhalb der Belegung bleibt fest.
 * Der Dialog nutzt das nur für Beschriftung und Hinweis; entscheiden tut der Server.
 * Reines JS-Modul (tests/Frontend/roomChangeApproval.test.js).
 */

/**
 * @param {{
 *     approvedBooking: {room_id: number, start: string, end: string}|null|undefined,
 *     roomId: number|string|null|undefined,
 *     start: string|null|undefined,
 *     end: string|null|undefined,
 *     canBookDirectly: boolean,
 * }} input start/end/approvedBooking als ISO-Zeitpunkte
 * @returns {boolean}
 */
export function changeNeedsApproval({approvedBooking, roomId, start, end, canBookDirectly}) {
    if (canBookDirectly || !approvedBooking || roomId === null || roomId === undefined) {
        return false
    }

    if (Number(roomId) !== Number(approvedBooking.room_id)) {
        return true
    }

    const startMs = Date.parse(start ?? '')
    const endMs = Date.parse(end ?? '')
    if (Number.isNaN(startMs) || Number.isNaN(endMs)) {
        return false
    }

    return startMs < Date.parse(approvedBooking.start) || endMs > Date.parse(approvedBooking.end)
}

/**
 * Was der Dialog nach dem Speichern zeigt: "new" (neuer Termin als Raumanfrage), "change" (Änderung als
 * Raumanfrage) oder null (Dialog schließen, nichts wurde neu angefragt).
 *
 * @param {{
 *     isNewEvent: boolean,
 *     serverState: 'created'|'pending'|'none'|null|undefined, Antwort von events.update (axios)
 *     createdAsRequest: boolean|null|undefined, occupancy_option aus der Antwort von events.store (axios)
 *     predictedRequest: boolean, Vorhersage, wenn der Server nichts zurückgibt (Inertia-Weg)
 * }} input
 * @returns {'new'|'change'|null}
 */
export function requestSubmittedKind({isNewEvent, serverState, createdAsRequest, predictedRequest}) {
    if (isNewEvent) {
        const isRequest = typeof createdAsRequest === 'boolean' ? createdAsRequest : predictedRequest

        return isRequest ? 'new' : null
    }

    const isRequest = serverState ? serverState === 'created' : predictedRequest

    return isRequest ? 'change' : null
}
