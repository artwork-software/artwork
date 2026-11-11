/**
 * Terminauswahl des Künstlerplans: Terminarten wählen ihre Termine vor, einzelne Termine lassen sich
 * zusätzlich an- oder abwählen. Der Zustand ist unveränderlich ({typeIds, included, excluded} als Sets),
 * jede Änderung liefert einen neuen Zustand.
 */

export function createPlanEventSelection(typeIds = []) {
    return {typeIds: new Set(typeIds), included: new Set(), excluded: new Set()};
}

export function isPlanEventSelected(selection, event) {
    if (selection.included.has(event.id)) {
        return true;
    }
    if (selection.excluded.has(event.id)) {
        return false;
    }
    return selection.typeIds.has(event.event_type_id);
}

export function selectedPlanEventIds(selection, events) {
    return events.filter((event) => isPlanEventSelected(selection, event)).map((event) => event.id);
}

/** Terminart an/aus; ihre Termine folgen danach wieder der Terminart (Einzelauswahl wird verworfen). */
export function togglePlanEventType(selection, eventTypeId, events) {
    const typeIds = new Set(selection.typeIds);
    typeIds.has(eventTypeId) ? typeIds.delete(eventTypeId) : typeIds.add(eventTypeId);

    const included = new Set(selection.included);
    const excluded = new Set(selection.excluded);
    events
        .filter((event) => event.event_type_id === eventTypeId)
        .forEach((event) => {
            included.delete(event.id);
            excluded.delete(event.id);
        });

    return {typeIds, included, excluded};
}

/** Einzelnen Termin an/aus; Abweichungen von der Terminart werden gemerkt. */
export function togglePlanEvent(selection, event) {
    const byType = selection.typeIds.has(event.event_type_id);
    const included = new Set(selection.included);
    const excluded = new Set(selection.excluded);

    if (isPlanEventSelected(selection, event)) {
        included.delete(event.id);
        if (byType) {
            excluded.add(event.id);
        }
    } else {
        excluded.delete(event.id);
        if (!byType) {
            included.add(event.id);
        }
    }

    return {typeIds: new Set(selection.typeIds), included, excluded};
}
