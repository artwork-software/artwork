/**
 * ISO-Kalenderwoche → Montag..Sonntag.
 *
 * Reines JS-Modul ohne Vue-Abhängigkeit, damit es in Node-Tests läuft
 * (tests/Frontend/IsoWeek.test.js). Rechnet in lokaler Zeit auf Tagesbasis –
 * Zeitzonen spielen für reine Datumsangaben keine Rolle.
 */

const pad = (n) => String(n).padStart(2, '0')

/** Date → 'YYYY-MM-DD' (lokale Zeit) */
export function toYmd(date) {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
}

/** Date → 'DD.MM.YYYY' (Anzeigeformat der App) */
export function toDmy(date) {
    return `${pad(date.getDate())}.${pad(date.getMonth() + 1)}.${date.getFullYear()}`
}

/**
 * Montag der ISO-Woche 1 eines Jahres: Woche 1 ist die Woche, die den 4. Januar enthält.
 */
export function mondayOfIsoWeek1(year) {
    const jan4 = new Date(year, 0, 4)
    const jan4Weekday = jan4.getDay() || 7 // Mo=1 … So=7
    return new Date(year, 0, 4 - (jan4Weekday - 1))
}

/**
 * Anzahl der ISO-Kalenderwochen eines Jahres (52 oder 53): der 28. Dezember liegt immer in der letzten KW.
 * @param {number} year
 * @returns {number}
 */
export function isoWeeksInYear(year) {
    const dec28 = new Date(year, 11, 28)
    const weekday = dec28.getDay() || 7
    // Donnerstag derselben ISO-Woche bestimmt das ISO-Jahr; Abstand zum Montag der KW 1 in Wochen
    const thursday = new Date(dec28.getFullYear(), dec28.getMonth(), dec28.getDate() + (4 - weekday))
    const week1Monday = mondayOfIsoWeek1(thursday.getFullYear())
    return Math.round((thursday - week1Monday) / (7 * 86400000)) + 1
}

/**
 * @param {number|string} week ISO-Kalenderwoche (1–53)
 * @param {number|string} year ISO-Jahr (das Jahr, zu dem die KW gehört – nicht zwingend das Kalenderjahr des Montags)
 * @returns {{start: string, end: string, monday: Date, sunday: Date}|null} null bei ungültiger Eingabe
 *   (auch KW 53 in einem 52-Wochen-Jahr — z. B. 2025 hat 52, 2026 hat 53 Wochen)
 */
export function isoWeekToDateRange(week, year) {
    const w = Number(week)
    const y = Number(year)
    if (!Number.isInteger(w) || !Number.isInteger(y) || w < 1 || w > 53 || y < 1970 || y > 9999) {
        return null
    }
    if (w > isoWeeksInYear(y)) {
        return null
    }

    const monday = mondayOfIsoWeek1(y)
    monday.setDate(monday.getDate() + (w - 1) * 7)
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)

    return { start: toYmd(monday), end: toYmd(sunday), monday, sunday }
}
