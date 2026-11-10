/**
 * Tageseinfärbung durch Feiertage (Kalender, Planungskalender, Dienstplan).
 *
 * Rangfolge bei mehreren Feiertagen am selben Tag: eigene Feiertage vor API-Feiertagen, innerhalb davon
 * eintägige vor mehrtägigen (Ferien). Vorher gewann der zuerst angelegte Eintrag – ein besonderer eigener
 * Feiertag ging dann unter. Hat der Tag eine zweite, andere Farbe, wird sie als Schraffur darübergelegt.
 *
 * Reines JS-Modul (tests/Frontend/holidayTint.test.js).
 */

/**
 * @param {{date?: string|null, end_date?: string|null}} holiday
 * @returns {boolean}
 */
export function isSingleDayHoliday(holiday) {
    return !holiday?.end_date || holiday.end_date === holiday.date;
}

const rankOf = (holiday) => (holiday?.from_api ? 2 : 0) + (isSingleDayHoliday(holiday) ? 0 : 1);

/**
 * Farbige Feiertage eines Tages in Anzeige-Rangfolge (stabil: gleicher Rang behält die Reihenfolge).
 * @param {Array<{color?: string|null, from_api?: boolean}>|null|undefined} holidays
 * @returns {Array<object>}
 */
export function rankHolidays(holidays) {
    return (holidays ?? [])
        .filter((holiday) => !!holiday?.color)
        .map((holiday, index) => ({ holiday, index }))
        .sort((a, b) => rankOf(a.holiday) - rankOf(b.holiday) || a.index - b.index)
        .map(({ holiday }) => holiday);
}

/**
 * Hauptfeiertag (Fläche) und – bei abweichender Farbe – zweiter Feiertag (Schraffur) eines Tages.
 * @param {Array<object>|null|undefined} holidays
 * @returns {{primary: object, secondary: object|null}|null}
 */
export function pickDayHolidays(holidays) {
    const ranked = rankHolidays(holidays);
    if (ranked.length === 0) {
        return null;
    }
    const primary = ranked[0];
    const primaryColor = normalizeHex(primary.color);
    const secondary = ranked.find((holiday) => normalizeHex(holiday.color) !== primaryColor) ?? null;

    return { primary, secondary };
}

/**
 * '#abc' → '#aabbcc'; andere Formate unverändert (ein angehängter Alpha-Wert machte '#333' sonst ungültig).
 * @param {string|null|undefined} color
 * @returns {string}
 */
export function normalizeHex(color) {
    const value = String(color ?? '').trim();
    const short = /^#([0-9a-f])([0-9a-f])([0-9a-f])$/i.exec(value);
    if (short) {
        return `#${short[1]}${short[1]}${short[2]}${short[2]}${short[3]}${short[3]}`.toLowerCase();
    }

    return value.toLowerCase();
}

/**
 * Farbe mit Hex-Alpha ('33' = 20 %). Nicht-Hex-Farben bleiben ohne Alpha.
 * @param {string} color
 * @param {string} alphaHex
 * @returns {string}
 */
export function withAlpha(color, alphaHex) {
    const hex = normalizeHex(color);

    return /^#[0-9a-f]{6}$/.test(hex) ? `${hex}${alphaHex}` : color;
}

/**
 * Schraffur-Layer für die zweite Farbe eines Tages.
 * @param {string} stripeColor bereits mit Alpha
 * @returns {string}
 */
export function holidayStripes(stripeColor) {
    return `repeating-linear-gradient(135deg, ${stripeColor} 0 4px, transparent 4px 10px)`;
}
