/**
 * Anzeige-Text der Projektkünstler*innen – Gegenstück zu ProjectArtistService::displayText():
 * Freitext zuerst, danach verknüpfte CRM-Künstler*innen, die im Freitext noch nicht vorkommen.
 */
export function artistDisplayText(freeText, linkedArtists) {
    const text = String(freeText ?? '').trim()
    const parts = text !== '' ? [text] : []
    const lowerText = text.toLowerCase()

    for (const artist of linkedArtists ?? []) {
        const name = String(artist?.name ?? '').trim()
        if (name === '' || lowerText.includes(name.toLowerCase()) || parts.includes(name)) {
            continue
        }
        parts.push(name)
    }

    return parts.join(', ')
}
