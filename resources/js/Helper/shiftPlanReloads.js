/**
 * Wann der Dienstplan nach Aktionen/Broadcasts was nachladen muss.
 *
 * Inertia-Aktionen aus dem Dienstplan (Woche/Tag) leiten per Redirect zurück auf die Dienstplanseite.
 * Ohne Einschränkung baute der Folge-Request die komplette Seite neu auf (alle Props, voller
 * Controller-Durchlauf) und tauschte sämtliche Props aus, obwohl die Ansichten ihre Daten selbst
 * nachladen und nur die Flash-Daten (Meldung, neu angelegte Schichten) brauchen.
 *
 * Reines JS-Modul ohne Vue-Abhängigkeit, damit es in Node-Tests läuft
 * (tests/Frontend/shiftPlanReloads.test.js).
 */

export const SHIFT_PLAN_COMPONENTS = ['Shifts/ShiftPlan', 'Shifts/ShiftPlanDailyView'];

/**
 * Visit-Optionen für Inertia-Aktionen: auf den Dienstplanseiten nur `flash` nachladen, überall sonst
 * (z.B. Projekt-Tabs) unverändert den vollen Folge-Request.
 *
 * @param {string|null|undefined} currentComponent usePage().component
 * @returns {{only?: string[]}}
 */
export function shiftPlanFlashOnlyReload(currentComponent) {
    return SHIFT_PLAN_COMPONENTS.includes(currentComponent) ? { only: ['flash'] } : {};
}

/**
 * Müssen nach angelegten Schichten (Broadcast .multi-shifts-created) die Personenzeilen neu geladen
 * werden? Nur wenn eine Schicht schon Personen trägt (Duplizieren mit Personen, Wiederherstellen aus
 * dem Papierkorb) — leere neue Schichten ändern weder Stunden noch die eigenen Schichten einer Person.
 * Unbekannte Payload-Form (ohne workers-Array) lädt vorsichtshalber neu.
 *
 * @param {Array<{workers?: unknown[]}>|null|undefined} shifts
 * @returns {boolean}
 */
export function createdShiftsCarryWorkers(shifts) {
    if (!Array.isArray(shifts)) {
        return true;
    }

    return shifts.some((shift) => !Array.isArray(shift?.workers) || shift.workers.length > 0);
}
