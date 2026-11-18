/**
 * Regeln für den Hinweis auf gleich- oder ähnlich benannte Projekte und Projektgruppen im
 * Anlage-Modal (Endpunkt projects.similar-names). Der Vergleich selbst läuft im Backend.
 */

/** Kürzere Eingaben würden fast jedes Projekt treffen. */
export const MIN_SIMILAR_NAME_LENGTH = 3

/** Schlüssel einer Eingabe: gleiche Schlüssel brauchen keine neue Prüfung. */
export function similarNameKey(name) {
    return String(name ?? '').trim().replace(/\s+/g, ' ').toLowerCase()
}

export function shouldCheckSimilarNames(name) {
    return similarNameKey(name).length >= MIN_SIMILAR_NAME_LENGTH
}

/**
 * Soll ein Klick auf „Anlegen“ einmal angehalten werden? Nur wenn die Treffer erst durch diesen
 * Klick sichtbar wurden; was beim Tippen schon zu sehen war, hat die Person gelesen.
 */
export function shouldHoldCreation({ matches, visibleKey, currentKey }) {
    return Array.isArray(matches) && matches.length > 0 && visibleKey !== currentKey
}

/** „d.m.Y H:i“ aus dem Backend → Datum ohne Uhrzeit; gleicher Tag nur einmal. */
export function projectPeriodLabel(firstEventDate, lastEventDate) {
    const first = String(firstEventDate ?? '').split(' ')[0]
    const last = String(lastEventDate ?? '').split(' ')[0]
    if (!first && !last) {
        return ''
    }
    if (!first || !last || first === last) {
        return first || last
    }

    return `${first} – ${last}`
}
