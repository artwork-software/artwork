/**
 * Serien von Abwesenheiten und Verfügbarkeiten enden spätestens fünf Jahre nach dem Starttag
 * (serverseitig MaxSeriesLength). Datumswerte im Format YYYY-MM-DD wie aus <input type="date">.
 */
export const MAX_SERIES_YEARS = 5

export function latestSeriesEnd(startDate, years = MAX_SERIES_YEARS) {
    if (!startDate) {
        return null
    }
    const date = new Date(`${startDate}T00:00:00Z`)
    if (Number.isNaN(date.getTime())) {
        return null
    }
    // setUTCFullYear läuft wie Carbon::addYears über (29.02. + 5 Jahre = 01.03.)
    date.setUTCFullYear(date.getUTCFullYear() + years)
    return date.toISOString().slice(0, 10)
}

export function exceedsSeriesLimit(startDate, endDate, years = MAX_SERIES_YEARS) {
    const latestEnd = latestSeriesEnd(startDate, years)
    return Boolean(latestEnd && endDate && endDate > latestEnd)
}
