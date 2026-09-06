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
 * @param {number|string} week ISO-Kalenderwoche (1–53)
 * @param {number|string} year ISO-Jahr (das Jahr, zu dem die KW gehört – nicht zwingend das Kalenderjahr des Montags)
 * @returns {{start: string, end: string, monday: Date, sunday: Date}|null} null bei ungültiger Eingabe
 */
export function isoWeekToDateRange(week, year) {
    const w = Number(week)
    const y = Number(year)
    if (!Number.isInteger(w) || !Number.isInteger(y) || w < 1 || w > 53 || y < 1970 || y > 9999) {
        return null
    }

    const monday = mondayOfIsoWeek1(y)
    monday.setDate(monday.getDate() + (w - 1) * 7)
    const sunday = new Date(monday)
    sunday.setDate(monday.getDate() + 6)

    return { start: toYmd(monday), end: toYmd(sunday), monday, sunday }
}
