import dayjs from 'dayjs'
import 'dayjs/locale/de'
import { formatEuro, fromCents, toCents } from '@/Pages/Settings/Tickets/drafts.js'

/* Shared helpers of the ticketing component: what a date currently sells for
   and holds (its own draft first, the venue's defaults otherwise), and the
   labels the modals and the table print. */

export { formatEuro, fromCents, toCents }

/** The room's price classes as a date starts out with them. */
export function defaultClassesOf(event) {
    return (event.venue?.zones ?? []).map((zone) => ({ zone_key: zone.key, name: zone.name, price_cents: zone.defaultPriceCents ?? 0, quota: zone.capacity ?? 0 }))
}

/** What the date sells: its own classes once saved, else the room's. Shape of the release table. */
export function classesOf(event) {
    return event.release?.classes?.length ? event.release.classes : defaultClassesOf(event)
}

export function capacityOf(event) {
    const classes = classesOf(event)
    return classes.length ? classes.reduce((sum, cls) => sum + (Number(cls.quota) || 0), 0) : null
}

export function isReleased(event) {
    return event.release?.state === 'released'
}

/* The weekday follows the interface language; dayjs knows nothing of vue-i18n on its own. */
export function formatDay(value, locale = 'de') {
    return dayjs(value).locale(locale).format('dd, DD.MM.YYYY')
}

export function formatTime(value) {
    return dayjs(value).format('HH:mm')
}

export function formatWhen(event, locale = 'de') {
    return event.allDay ? formatDay(event.start, locale) : `${formatDay(event.start, locale)} · ${formatTime(event.start)} – ${formatTime(event.end)}`
}

/** The dates that share a series with this one, itself included; just the date when it has none. */
export function seriesOf(event, events) {
    return event.seriesId ? events.filter((other) => other.seriesId === event.seriesId) : [event]
}

/** Whether a date can go on sale: synced room, at least one price class, not over yet. */
export function isReleasable(event) {
    return Boolean(event.venue) && classesOf(event).length > 0 && !dayjs(event.start).isBefore(dayjs())
}
