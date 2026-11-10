// Leerzustand des Dienstplan-Rasters (Wochen- und Tagesansicht). Die Ursache wird in fester
// Reihenfolge bestimmt: (1) keine Räume für die Disposition, (2) Stammdaten fehlen
// (Gewerke/Funktionen), (3) keine Schichten im Zeitraum, (4) Schichten vorhanden, aber
// Anzeige-Filter blenden alle aus ("filtered").

/**
 * @param {{
 *     roomsCount: number,
 *     craftsCount: number,
 *     functionsCount: number,
 *     hasShifts: boolean,
 *     hasVisibleShifts?: boolean,
 * }} state
 * @returns {'no-rooms'|'no-master-data'|'no-shifts'|'filtered'|null}
 */
export const resolveShiftPlanEmptyState = ({roomsCount, craftsCount, functionsCount, hasShifts, hasVisibleShifts = true}) => {
    if (roomsCount === 0) {
        return 'no-rooms';
    }
    if (craftsCount === 0 || functionsCount === 0) {
        return 'no-master-data';
    }
    if (!hasShifts) {
        return 'no-shifts';
    }
    if (!hasVisibleShifts) {
        return 'filtered';
    }

    return null;
};

/**
 * Übersetzungsschlüssel der Ursachen, warum vorhandene Schichten nicht sichtbar sind.
 *
 * @param {{filtersActive?: boolean, onlyNotFullyStaffed?: boolean, hideUnoccupiedDays?: boolean}} causes
 * @returns {string[]}
 */
export const shiftPlanFilteredReasons = ({filtersActive = false, onlyNotFullyStaffed = false, hideUnoccupiedDays = false}) => {
    const reasons = [];
    if (filtersActive) {
        reasons.push('A filter is active — shifts outside the filter are not shown.');
    }
    if (onlyNotFullyStaffed) {
        reasons.push('"Only show shifts that are not fully staffed" is active — fully staffed shifts are hidden.');
    }
    if (hideUnoccupiedDays) {
        reasons.push('"Hide unoccupied days" is active in the display settings — days without visible shifts or events are hidden.');
    }

    return reasons;
};

/**
 * Bricht bei der ersten sichtbaren Schicht ab — im Normalfall (Schichten sichtbar) also nach
 * wenigen Zellen. `getShifts` ist die bereits gefilterte (und gecachte) Zell-Abfrage der Ansicht.
 *
 * @template Room
 * @param {Room[]} rooms
 * @param {string[]} dayKeys
 * @param {(room: Room, dayKey: string) => unknown[]|null|undefined} getShifts
 * @returns {boolean}
 */
export const hasVisibleShiftInCells = (rooms, dayKeys, getShifts) => {
    for (const room of rooms ?? []) {
        for (const dayKey of dayKeys ?? []) {
            if ((getShifts(room, dayKey)?.length ?? 0) > 0) {
                return true;
            }
        }
    }

    return false;
};
