/**
 * Geteilter Fokus-Zustand für die Budget-Zell-Navigation.
 *
 * Ersetzt den früheren localStorage-Mechanismus ('nextCellId'): der war global
 * über Projekte/Tabs hinweg geteilt und wurde beim Unmount einer beliebigen
 * SubPosition komplett gelöscht. Ein simples Modul-Objekt reicht, weil alle
 * SubPositionComponents im selben Fenster leben und der Zustand bewusst
 * flüchtig ist (kein Reload-Überleben nötig).
 */
import { reactive } from 'vue';

export const budgetCellFocus = reactive({
    // Zelle, die nach dem Speichern der aktuell editierten Zelle den
    // Editiermodus erhalten soll (per Klick oder Tastaturnavigation gesetzt).
    nextCellId: null,
});
