/**
 * Reine Hilfslogik der Seite "Räume & Areale": Suche, Filter und Namensprüfung.
 */

const normalize = (value) => String(value ?? '').trim().toLocaleLowerCase();

/**
 * @param {{search?: string, categoryIds?: number[], attributeIds?: number[]}} criteria
 * @returns {boolean}
 */
export function hasActiveRoomCriteria(criteria = {}) {
    return normalize(criteria.search) !== ''
        || (criteria.categoryIds?.length ?? 0) > 0
        || (criteria.attributeIds?.length ?? 0) > 0;
}

function roomMatchesProperties(room, criteria) {
    const categoryIds = criteria.categoryIds ?? [];
    const attributeIds = criteria.attributeIds ?? [];
    const matchesCategories = categoryIds.length === 0
        || (room.room_categories ?? []).some((category) => categoryIds.includes(category.id));
    const matchesAttributes = attributeIds.length === 0
        || (room.room_attributes ?? []).some((attribute) => attributeIds.includes(attribute.id));

    return matchesCategories && matchesAttributes;
}

/**
 * Areale mit den Räumen, die zu Suche und Filter passen. Ohne Kriterien bleibt die Liste unverändert.
 * Mit Kriterien fallen Areale ohne Treffer weg. Passt der Suchbegriff auf den Namen des Areals,
 * bleiben alle seine Räume stehen, die zum Kategorie- und Eigenschaftsfilter passen.
 *
 * @param {Array<{name: string, rooms: Array}>} areas
 * @param {{search?: string, categoryIds?: number[], attributeIds?: number[]}} criteria
 * @returns {Array}
 */
export function filterAreas(areas, criteria = {}) {
    if (!hasActiveRoomCriteria(criteria)) {
        return areas;
    }

    const search = normalize(criteria.search);

    return (areas ?? [])
        .map((area) => {
            const areaNameMatches = search !== '' && normalize(area.name).includes(search);
            const rooms = (area.rooms ?? []).filter((room) => {
                if (!roomMatchesProperties(room, criteria)) {
                    return false;
                }

                return search === '' || areaNameMatches || normalize(room.name).includes(search);
            });

            return {...area, rooms};
        })
        .filter((area) => area.rooms.length > 0);
}

/**
 * @param {Array<{name: string}>} items
 * @param {string} name
 * @returns {boolean}
 */
export function nameAlreadyExists(items, name) {
    const wanted = normalize(name);

    return wanted !== '' && (items ?? []).some((item) => normalize(item.name) === wanted);
}

/**
 * @param {Array<{id: number}>} areas
 * @param {number[]} openedIds
 * @returns {boolean}
 */
export function allAreasOpened(areas, openedIds) {
    return (areas ?? []).length > 0 && areas.every((area) => (openedIds ?? []).includes(area.id));
}

/**
 * Kurzinfos einer Raumzeile: Kapazität, Kategorien und Abweichungen vom Normalfall.
 *
 * @param {object} room
 * @returns {{capacity: number|null, categories: string[], hiddenFromCalendars: boolean}}
 */
export function roomRowFacts(room) {
    return {
        capacity: room.capacity > 0 ? room.capacity : null,
        categories: (room.room_categories ?? []).map((category) => category.name),
        hiddenFromCalendars: room.relevant_for_disposition === false,
    };
}

/**
 * Zahl der Räume je Kategorie oder Eigenschaft, über alle Areale.
 *
 * @param {Array<{rooms: Array}>} areas
 * @param {'room_categories'|'room_attributes'} relation
 * @returns {Record<number, number>}
 */
export function usageCounts(areas, relation) {
    const counts = {};
    (areas ?? []).forEach((area) => {
        (area.rooms ?? []).forEach((room) => {
            (room[relation] ?? []).forEach((item) => {
                counts[item.id] = (counts[item.id] ?? 0) + 1;
            });
        });
    });

    return counts;
}

/**
 * @param {Array<{rooms: Array<{id: number}>}>} areas
 * @returns {number[]}
 */
export function roomIdsOf(areas) {
    return (areas ?? []).flatMap((area) => (area.rooms ?? []).map((room) => room.id));
}

/**
 * Auswahl ohne Räume, die es in der Liste nicht mehr gibt (gelöscht oder von anderen entfernt).
 *
 * @param {number[]} selectedIds
 * @param {Array} areas
 * @returns {number[]}
 */
export function pruneSelection(selectedIds, areas) {
    const existing = new Set(roomIdsOf(areas));

    return (selectedIds ?? []).filter((id) => existing.has(id));
}

/**
 * Schaltet alle übergebenen Räume gemeinsam: sind alle gewählt, werden sie abgewählt, sonst alle gewählt.
 *
 * @param {number[]} selectedIds
 * @param {number[]} roomIds
 * @returns {number[]}
 */
export function toggleRoomIds(selectedIds, roomIds) {
    const selected = new Set(selectedIds ?? []);
    const allSelected = roomIds.length > 0 && roomIds.every((id) => selected.has(id));
    roomIds.forEach((id) => (allSelected ? selected.delete(id) : selected.add(id)));

    return [...selected];
}

/**
 * @param {number[]} selectedIds
 * @param {number[]} roomIds
 * @returns {'none'|'some'|'all'}
 */
export function selectionState(selectedIds, roomIds) {
    const hits = roomIds.filter((id) => (selectedIds ?? []).includes(id)).length;
    if (hits === 0) {
        return 'none';
    }

    return hits === roomIds.length ? 'all' : 'some';
}

/**
 * Räume nach der Reihenfolge der Areale geordnet; innerhalb eines Areals bleibt die bisherige Reihenfolge.
 * Räume ohne bekanntes Areal landen am Ende.
 *
 * @param {Array<{id: number, area_id: number}>} rooms
 * @param {Array<{id: number}>} areas
 * @returns {Array}
 */
export function orderRoomsByArea(rooms, areas) {
    const areaIndex = new Map((areas ?? []).map((area, index) => [area.id, index]));
    const rank = (room) => areaIndex.get(room.area_id) ?? Number.MAX_SAFE_INTEGER;

    return (rooms ?? [])
        .map((room, index) => ({room, index}))
        .sort((a, b) => rank(a.room) - rank(b.room) || a.index - b.index)
        .map((entry) => entry.room);
}

/**
 * @param {Array<{id: number, area_id: number}>} rooms
 * @param {Array<{id: number}>} areas
 * @returns {boolean}
 */
export function roomsFollowAreaOrder(rooms, areas) {
    const ordered = orderRoomsByArea(rooms, areas);

    return (rooms ?? []).every((room, index) => room.id === ordered[index].id);
}
