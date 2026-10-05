import {reactive, ref} from 'vue'

/**
 * Offene Beschreibungs-Bearbeitungen der Terminliste, je Termin (id bzw. localUid) – gemeinsam für
 * alle Zeilen-Komponenten. Die virtuelle Liste recycelt Komponenten; der Zustand darf deshalb nicht
 * in der Komponente liegen, sonst springt er beim Neu-Rendern auf einen anderen Termin.
 *
 * @type {Map<string|number, {draft: string}>}
 */
export const openDescriptionEdits = reactive(new Map())

/** Termin, dessen Beschreibungsfeld zuletzt den Fokus hatte (zum Zurückholen nach Recycling) */
export const focusedDescriptionKey = ref(null)
