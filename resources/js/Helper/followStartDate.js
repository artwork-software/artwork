/**
 * Enddatum, nachdem das Startdatum eines Zeitraums geändert wurde ('YYYY-MM-DD').
 *
 * „Bis" springt auf das neue „Von", wenn es leer ist, davor läge oder bisher dem alten „Von"
 * entsprach (eintägiger Eintrag). Ein bewusst gewählter Zeitraum bleibt erhalten.
 *
 * @param {string|null} newStart
 * @param {string|null} oldStart
 * @param {string|null} currentEnd
 * @returns {string|null}
 */
export function followStartDate(newStart, oldStart, currentEnd) {
    if (!newStart) {
        return currentEnd
    }
    if (!currentEnd || currentEnd < newStart || currentEnd === oldStart) {
        return newStart
    }

    return currentEnd
}
