/**
 * Schnellwahl-Zeiträume der Materialausgabe-Filter (Heute / Diese Woche / Dieser Monat).
 * Alle Funktionen nehmen das Bezugsdatum als Parameter, damit sie ohne Uhr testbar sind.
 */

const pad = (n) => String(n).padStart(2, '0')

/** Lokales Datum als YYYY-MM-DD (Format der <input type="date">-Felder) */
export const formatIsoDate = (date) => `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`

const todayRange = (now) => [now, now]

/** Woche von Montag bis Sonntag */
const thisWeekRange = (now) => {
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate() - ((now.getDay() + 6) % 7))
    const end = new Date(start.getFullYear(), start.getMonth(), start.getDate() + 6)
    return [start, end]
}

const thisMonthRange = (now) => [
    new Date(now.getFullYear(), now.getMonth(), 1),
    new Date(now.getFullYear(), now.getMonth() + 1, 0),
]

export const ISSUE_QUICK_RANGES = [
    { key: 'today', label: 'Today', resolve: todayRange },
    { key: 'week', label: 'This week', resolve: thisWeekRange },
    { key: 'month', label: 'This month', resolve: thisMonthRange },
]

/**
 * @returns {{dateFrom: string, dateTo: string}}
 */
export const resolveQuickRange = (key, now = new Date()) => {
    const range = ISSUE_QUICK_RANGES.find((quickRange) => quickRange.key === key)
    const [start, end] = range.resolve(now)
    return { dateFrom: formatIsoDate(start), dateTo: formatIsoDate(end) }
}

/** Schlüssel der Schnellwahl, deren Zeitraum exakt Von/Bis entspricht, sonst null */
export const activeQuickRangeKey = (dateFrom, dateTo, now = new Date()) => {
    if (!dateFrom || !dateTo) {
        return null
    }
    const match = ISSUE_QUICK_RANGES.find((quickRange) => {
        const resolved = resolveQuickRange(quickRange.key, now)
        return resolved.dateFrom === dateFrom && resolved.dateTo === dateTo
    })
    return match?.key ?? null
}
