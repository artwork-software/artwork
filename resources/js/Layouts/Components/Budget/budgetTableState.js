/**
 * Geteilter, flüchtiger Zustand der Budgettabelle. Haupt-, Unterpositionen und die
 * BudgetComponent leben im selben Fenster und greifen gemeinsam darauf zu, statt
 * Auswahl, Suchtreffer und "wird gelöscht" über mehrere Ebenen durchzureichen.
 */
import { reactive } from 'vue';

export const budgetCellSelection = reactive({
    // cellId -> true
    ids: {},
    // Ausgangszelle für die Bereichsauswahl per Umschalt-Klick
    anchorId: null,
});

export function clearBudgetCellSelection() {
    budgetCellSelection.ids = {};
    budgetCellSelection.anchorId = null;
}

export const budgetSearchState = reactive({
    // "row:12" / "sub:3" / "main:1" -> true
    matchedKeys: {},
    activeKey: null,
});

export function resetBudgetSearchState() {
    budgetSearchState.matchedKeys = {};
    budgetSearchState.activeKey = null;
}

export const budgetPendingDeletes = reactive({
    // "row:12" / "sub:3" / "main:1" / "column:7" -> true, solange "Rückgängig" möglich ist
    keys: {},
});
