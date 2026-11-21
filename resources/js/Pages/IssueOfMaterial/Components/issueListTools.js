/**
 * Reine Hilfsfunktionen der Materialausgabe-Listen (Sortierung, Auswahl, Abfrage-Parameter), ohne Vue.
 */

/**
 * Nächster Sortierzustand beim Klick auf eine Spalte: aufsteigend, absteigend, dann zurück zur
 * Standardreihenfolge des Servers.
 */
export function nextIssueSort(current, key) {
    if (current?.sort !== key) {
        return {sort: key, direction: 'asc'};
    }
    if (current.direction === 'asc') {
        return {sort: key, direction: 'desc'};
    }
    return {sort: null, direction: null};
}

/** Entfernt leere Werte, damit die Adresse nur gesetzte Filter trägt. */
export function compactIssueQuery(params) {
    const query = {};
    for (const [key, value] of Object.entries(params ?? {})) {
        if (value === undefined || value === null || value === '' || value === false) {
            continue;
        }
        if (Array.isArray(value)) {
            if (value.length > 0) {
                query[key] = value.join(',');
            }
            continue;
        }
        query[key] = value === true ? 1 : value;
    }
    return query;
}

/** Sind alle Zeilen der aktuellen Seite ausgewählt? */
export function allIssuesSelected(issues, selectedIds) {
    return (issues ?? []).length > 0 && issues.every(issue => selectedIds[issue.id] === true);
}

/** Wählt alle Zeilen der Seite aus oder ab, Auswahl auf anderen Seiten bleibt erhalten. */
export function togglePageSelection(issues, selectedIds, checked) {
    const next = {...selectedIds};
    for (const issue of issues ?? []) {
        if (checked) {
            next[issue.id] = true;
        } else {
            delete next[issue.id];
        }
    }
    return next;
}
