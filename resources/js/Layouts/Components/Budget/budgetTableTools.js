/**
 * Reine Hilfsfunktionen der Budgettabelle (Suche, Zellauswahl), bewusst ohne Vue,
 * damit sie sich isoliert testen lassen.
 */

/** Zellwerte liegen als Text mit Dezimalkomma vor ("1200,00"), Sage-Werte als Zahl. */
export function budgetCellAmount(cell) {
    const raw = cell?.column?.type === 'sage' ? cell?.sage_value : cell?.value;
    if (typeof raw === 'number') {
        return Number.isFinite(raw) ? raw : 0;
    }
    const parsed = parseFloat(String(raw ?? '').replace(/\s/g, '').replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : 0;
}

function contains(value, needle) {
    return String(value ?? '').toLowerCase().includes(needle);
}

/**
 * Treffer der Tabellensuche in Anzeige-Reihenfolge (erst Ausgaben, dann Einnahmen).
 * Durchsucht Namen der Haupt- und Unterpositionen sowie KTO, KST und Position jeder Zeile.
 */
export function findBudgetSearchMatches(mainPositions, term) {
    const needle = String(term ?? '').trim().toLowerCase();
    if (needle === '') {
        return [];
    }

    const ordered = [
        ...(mainPositions ?? []).filter(mainPosition => mainPosition.type === 'BUDGET_TYPE_COST'),
        ...(mainPositions ?? []).filter(mainPosition => mainPosition.type !== 'BUDGET_TYPE_COST'),
    ];
    const matches = [];

    for (const mainPosition of ordered) {
        const section = mainPosition.type === 'BUDGET_TYPE_COST' ? 'cost' : 'earning';
        if (contains(mainPosition.name, needle)) {
            matches.push({type: 'main', id: mainPosition.id, mainPositionId: mainPosition.id, subPositionId: null, section});
        }
        for (const subPosition of mainPosition.sub_positions ?? []) {
            if (contains(subPosition.name, needle)) {
                matches.push({
                    type: 'sub',
                    id: subPosition.id,
                    mainPositionId: mainPosition.id,
                    subPositionId: subPosition.id,
                    section,
                });
            }
            for (const row of subPosition.sub_position_rows ?? []) {
                const hit = (row.cells ?? []).slice(0, 3).some(
                    cell => contains(cell.display_value, needle) || contains(cell.value, needle)
                );
                if (hit) {
                    matches.push({
                        type: 'row',
                        id: row.id,
                        mainPositionId: mainPosition.id,
                        subPositionId: subPosition.id,
                        section,
                    });
                }
            }
        }
    }

    return matches;
}

/**
 * Zellen im Rechteck zwischen zwei Zellen derselben Unterposition (Umschalt-Klick).
 * Liegt eine der beiden Zellen nicht in den Zeilen, bleibt es bei der Zielzelle.
 */
export function budgetCellRange(rows, anchorCellId, targetCellId, isSelectable = () => true) {
    const locate = (cellId) => {
        for (let rowIndex = 0; rowIndex < (rows ?? []).length; rowIndex++) {
            const cellIndex = (rows[rowIndex].cells ?? []).findIndex(cell => cell.id === cellId);
            if (cellIndex !== -1) {
                return {rowIndex, cellIndex};
            }
        }
        return null;
    };

    const anchor = locate(anchorCellId);
    const target = locate(targetCellId);
    if (!anchor || !target) {
        return [targetCellId];
    }

    const ids = [];
    for (let rowIndex = Math.min(anchor.rowIndex, target.rowIndex); rowIndex <= Math.max(anchor.rowIndex, target.rowIndex); rowIndex++) {
        const cells = rows[rowIndex].cells ?? [];
        for (let cellIndex = Math.min(anchor.cellIndex, target.cellIndex); cellIndex <= Math.max(anchor.cellIndex, target.cellIndex); cellIndex++) {
            if (cells[cellIndex] && isSelectable(cells[cellIndex], cellIndex)) {
                ids.push(cells[cellIndex].id);
            }
        }
    }
    return ids;
}

/** Anzahl, Summe und Durchschnitt der ausgewählten Zellen. */
export function summarizeBudgetSelection(mainPositions, selectedIds) {
    let count = 0;
    let sum = 0;

    for (const mainPosition of mainPositions ?? []) {
        for (const subPosition of mainPosition.sub_positions ?? []) {
            for (const row of subPosition.sub_position_rows ?? []) {
                for (const cell of row.cells ?? []) {
                    if (selectedIds[cell.id]) {
                        count++;
                        sum += budgetCellAmount(cell);
                    }
                }
            }
        }
    }

    return {count, sum, average: count > 0 ? sum / count : 0};
}
