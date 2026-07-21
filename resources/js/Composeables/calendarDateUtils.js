import dayjs from 'dayjs'
import weekOfYear from 'dayjs/plugin/weekOfYear'
import localeData from 'dayjs/plugin/localeData'
import 'dayjs/locale/de'

dayjs.extend(weekOfYear)
dayjs.extend(localeData)

/**
 * Compute derived calendar day properties from an ISO date string (YYYY-MM-DD).
 * Replaces the 15-field CalendarPeriodDTO with client-side computation.
 *
 * @param {string} isoDate - ISO date string (e.g. "2025-11-02")
 * @returns {object} computed day properties
 */
export function computeDayProperties(isoDate) {
    const d = dayjs(isoDate)
    const dayOfWeek = d.day() // 0=Sunday, 6=Saturday

    return {
        // Display formats
        day: d.format('DD.MM.'),
        dayString: d.locale('de').format('dd'),
        fullDay: d.format('DD.MM.YYYY'),
        shortDay: d.format('DD.MM'),
        withoutFormat: d.format('YYYY-MM-DD'),
        fullDayDisplay: d.format('DD.MM.YY'),

        // Week/month metadata
        weekNumber: d.week(),
        monthNumber: d.month() + 1, // dayjs months are 0-indexed
        isMonday: dayOfWeek === 1,
        isSunday: dayOfWeek === 0,
        isWeekend: dayOfWeek === 0 || dayOfWeek === 6,
        isFirstDayOfMonth: d.date() === 1,
        addWeekSeparator: dayOfWeek === 0,
    }
}

// Cache for computed properties to avoid re-computation
const _cache = new Map()

/**
 * Get computed day properties with caching.
 * @param {string} isoDate
 * @returns {object}
 */
export function getDayProps(isoDate) {
    if (_cache.has(isoDate)) {
        return _cache.get(isoDate)
    }
    const props = computeDayProperties(isoDate)
    _cache.set(isoDate, props)
    return props
}

/**
 * Clear the cache (call when navigating to a different date range).
 */
export function clearDayPropsCache() {
    _cache.clear()
}

/**
 * Enrich a slim day DTO (with just `date`, `holidays`, `hoursOfDay`, `isExtraRow`)
 * into the full day object expected by components.
 *
 * @param {object} slimDay - { date, holidays?, hoursOfDay?, isExtraRow }
 * @returns {object} enriched day object
 */
export function enrichDay(slimDay) {
    if (slimDay.isExtraRow) {
        const d = dayjs(slimDay.date)
        return {
            isExtraRow: true,
            weekNumber: d.week(),
            date: slimDay.date,
        }
    }

    const computed = getDayProps(slimDay.date)
    return {
        ...computed,
        date: slimDay.date,
        holidays: slimDay.holidays ?? [],
        hoursOfDay: slimDay.hoursOfDay ?? [],
        isExtraRow: false,
        // Tagesbemerkung ({ text, updated_by, updated_at } | null) — kommt nur im
        // Payload an, wenn Feature aktiv & User Sichtrecht hat
        dayRemark: slimDay.dayRemark ?? null,
    }
}

/**
 * Enrich an array of slim day DTOs.
 * @param {Array} slimDays
 * @returns {Array} enriched days
 */
export function enrichDays(slimDays) {
    return slimDays.map(enrichDay)
}

/**
 * Compute days in range as dd.mm.YYYY strings (replaces server-side daysOfShift/daysOfEvent).
 * @param {string} startDate - start date/datetime string
 * @param {string} endDate - end date/datetime string
 * @returns {string[]} array of "DD.MM.YYYY" day strings
 */
export function getDaysInRange(startDate, endDate) {
    const start = dayjs(startDate).startOf('day')
    const end = dayjs(endDate).startOf('day')
    const days = []
    let current = start
    while (current.isBefore(end) || current.isSame(end, 'day')) {
        days.push(current.format('DD.MM.YYYY'))
        current = current.add(1, 'day')
    }
    return days
}

/**
 * Format a date string as DD.MM.YYYY.
 * @param {string} dateStr
 * @returns {string}
 */
export function formatDate(dateStr) {
    return dayjs(dateStr).format('DD.MM.YYYY')
}

/**
 * German month label for the calendar month separator row, e.g. "Juli 2026".
 * @param {string} isoDate
 * @returns {string}
 */
export function formatMonthLabel(isoDate) {
    return dayjs(isoDate).locale('de').format('MMMM YYYY')
}

/**
 * Compute formatted dates for a shift (replaces server-side formatted_dates).
 * @param {string} startDate
 * @param {string} endDate
 * @param {string} start - time string (HH:mm)
 * @param {string} end - time string (HH:mm)
 * @returns {object}
 */
export function computeShiftFormattedDates(startDate, endDate, start, end) {
    return {
        start: dayjs(startDate).format('DD.MM.YYYY'),
        end: dayjs(endDate).format('DD.MM.YYYY'),
        startTime: start?.toString().slice(0, 5) ?? '',
        endTime: end?.toString().slice(0, 5) ?? '',
    }
}

/**
 * Compute formatted dates for an event (replaces server-side formattedDates).
 * @param {string} start - start datetime string
 * @param {string} end - end datetime string
 * @returns {object}
 */
export function computeEventFormattedDates(start, end) {
    const s = dayjs(start)
    const e = dayjs(end)
    return {
        start: s.format('DD.MM.YYYY'),
        end: e.format('DD.MM.YYYY'),
        startDate: s.format('DD.MM.YYYY'),
        endDate: e.format('DD.MM.YYYY'),
        startTime: s.format('HH:mm'),
        endTime: e.format('HH:mm'),
        start_without_year: s.format('DD.MM'),
        end_without_year: e.format('DD.MM'),
        startDateTime_without_year: s.format('DD.MM HH:mm'),
        endDateTime_without_year: e.format('DD.MM HH:mm'),
    }
}
