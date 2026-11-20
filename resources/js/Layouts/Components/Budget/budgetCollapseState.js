/**
 * Geteilter Einklapp-Zustand der Budgettabelle (Abschnitte, Haupt- und Unterpositionen).
 *
 * Der Zustand hing früher als `closed`-Flag direkt am Positions-Objekt. Jedes
 * Nachladen des Budgets (eigene Strukturänderung oder Broadcast eines anderen
 * Nutzers) lieferte neue Objekte ohne Flag, alle eingeklappten Positionen
 * sprangen wieder auf. Hier lebt der Zustand getrennt von den Daten und wird
 * pro Tabelle im Browser gespeichert.
 *
 * Speicherformat bleibt das bisherige (`[{id, closed}]`), damit bestehende
 * Einstellungen der Nutzer erhalten bleiben.
 */
import { reactive } from 'vue';
import { safeGetItem, safeSetItem } from '@/Helper/safeStorage.js';

const STORAGE_PREFIXES = {
    main: 'closedMainPositions',
    sub: 'closedSubPositions',
    section: 'closedBudgetSections',
};

// storageKey -> { [id]: true } (nur eingeklappte Einträge)
const closedByStorageKey = reactive({});

function storageKey(kind, tableId) {
    return `${STORAGE_PREFIXES[kind]}:${tableId}`;
}

function closedEntries(kind, tableId) {
    const key = storageKey(kind, tableId);
    if (!closedByStorageKey[key]) {
        const entries = {};
        try {
            const stored = JSON.parse(safeGetItem(key));
            if (Array.isArray(stored)) {
                stored.filter(entry => entry?.closed).forEach(entry => {
                    entries[entry.id] = true;
                });
            }
        } catch (e) {
            // unlesbarer Altbestand: alles aufgeklappt anzeigen
        }
        closedByStorageKey[key] = entries;
    }
    return closedByStorageKey[key];
}

function persist(kind, tableId) {
    const entries = closedEntries(kind, tableId);
    safeSetItem(
        storageKey(kind, tableId),
        JSON.stringify(Object.keys(entries).map(id => ({
            id: isNaN(Number(id)) ? id : Number(id),
            closed: true,
        })))
    );
}

export function isBudgetEntryClosed(kind, tableId, id) {
    return closedEntries(kind, tableId)[id] === true;
}

export function setBudgetEntryClosed(kind, tableId, id, closed) {
    const entries = closedEntries(kind, tableId);
    if (closed) {
        entries[id] = true;
    } else {
        delete entries[id];
    }
    persist(kind, tableId);
}

export function toggleBudgetEntryClosed(kind, tableId, id) {
    setBudgetEntryClosed(kind, tableId, id, !isBudgetEntryClosed(kind, tableId, id));
}

export function setAllBudgetEntriesClosed(kind, tableId, ids, closed) {
    const entries = closedEntries(kind, tableId);
    ids.forEach((id) => {
        if (closed) {
            entries[id] = true;
        } else {
            delete entries[id];
        }
    });
    persist(kind, tableId);
}
