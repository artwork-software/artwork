/**
 * Übersetzt einen Text nur, wenn er als Schlüssel in den Sprachdateien steht. Namen und andere Daten
 * (Projekt, Artikel, Webhook …) bleiben unverändert: vue-i18n würde darin `|` als Plural-Trenner,
 * `{…}` als Platzhalter und `@` als verlinkte Nachricht auswerten und den Text verstümmeln.
 *
 * @param {string|null|undefined} text
 * @param {Record<string, unknown>|null|undefined} messages flache Meldungen der aktuellen Sprache
 * @param {(key: string) => string} translate
 * @returns {string}
 */
export function translateIfKnown(text, messages, translate) {
    if (text === null || text === undefined || text === '') {
        return '';
    }
    const value = String(text);

    return messages && Object.prototype.hasOwnProperty.call(messages, value) ? translate(value) : value;
}
