// Leerzustand des Kalenders (Monats-, Tages- und At-a-Glance-Ansicht): Der Server
// liefert die Räume bereits gefiltert — ist die Liste leer, sieht das Raster ohne
// Hinweis wie ein Fehler aus. Die Ursache wird in fester Reihenfolge bestimmt:
// (1) "Leere Räume ausblenden" aktiv → im Zeitraum gibt es (für diese Filter) keine Termine,
// (2) Raumfilter blendet alle Räume aus, (3) es gibt überhaupt keine Räume.

/** Zählt nur gesetzte Listen-Filter (Räume, Areale, Eigenschaften, Terminarten …). */
export const hasActiveCalendarFilters = (userFilters) => {
    if (!userFilters || typeof userFilters !== 'object') {
        return false;
    }

    return Object.values(userFilters).some((value) => Array.isArray(value) && value.length > 0);
};

/**
 * @param {{roomsCount: number, hideUnoccupiedRooms: boolean, filtersActive: boolean}} state
 * @returns {'no-events'|'filtered'|'no-rooms'|null}
 */
export const resolveCalendarEmptyState = ({roomsCount, hideUnoccupiedRooms, filtersActive}) => {
    if (roomsCount > 0) {
        return null;
    }
    if (hideUnoccupiedRooms) {
        return 'no-events';
    }
    if (filtersActive) {
        return 'filtered';
    }

    return 'no-rooms';
};
