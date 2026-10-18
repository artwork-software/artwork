/**
 * Merkt sich die letzte echte Nutzeraktion (Zeigerklick oder Taste). Damit lässt sich ein
 * Fokusverlust, den die Person ausgelöst hat (anderes Feld angeklickt, Tab), von einem
 * unterscheiden, den die Oberfläche verursacht (virtuelle Liste rendert Zeilen neu, Fenster-
 * wechsel). Ein einziger globaler Listener – nicht einer je Listenzeile.
 */
let lastInteractionAt = Number.NEGATIVE_INFINITY
let lastInteractionTarget = null

const remember = (event) => {
    lastInteractionAt = performance.now()
    lastInteractionTarget = event?.target ?? null
}

if (typeof document !== 'undefined') {
    document.addEventListener('pointerdown', remember, true)
    document.addEventListener('keydown', remember, true)
}

export const markUserInteraction = remember

/** War die letzte Nutzeraktion höchstens `withinMs` her? */
export const isRecentUserInteraction = (withinMs = 400, now = performance.now()) =>
    now - lastInteractionAt <= withinMs

/**
 * Hat die Person NACH `since` außerhalb von `element` geklickt/getippt? Für Fokusverluste: nur eine
 * Aktion nach dem Fokussieren und außerhalb des Felds kann ihn ausgelöst haben – weder der Klick,
 * der das Feld geöffnet hat, noch das Tippen darin.
 */
export const hasUserInteractedSince = (since, element = null) =>
    lastInteractionAt > since
    && !(element && lastInteractionTarget instanceof Node && element.contains(lastInteractionTarget))
