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

/** Neue Zeile hat ihre Server-id bekommen: offene Bearbeitung von localUid auf id umschlüsseln */
export const rekeyDescriptionEdit = (fromKey, toKey) => {
    if (fromKey === undefined || fromKey === null || !openDescriptionEdits.has(fromKey)) {
        return
    }
    openDescriptionEdits.set(toKey, openDescriptionEdits.get(fromKey))
    openDescriptionEdits.delete(fromKey)
    if (focusedDescriptionKey.value === fromKey) {
        focusedDescriptionKey.value = toKey
    }
}

/**
 * Liste verlassen: offene Bearbeitungen verwerfen. Sonst öffnet sich ein alter Entwurf beim nächsten
 * Besuch wieder (und überschreibt beim Verlassen eine inzwischen geänderte Beschreibung); localUids
 * beginnen je Liste neu bei 1 und träfen fremde Zeilen.
 */
export const resetDescriptionEdits = () => {
    openDescriptionEdits.clear()
    focusedDescriptionKey.value = null
}
