import { usePage } from '@inertiajs/vue3'
import dayjs from 'dayjs'
import 'dayjs/locale/de'
import { createInstanceFormatter } from '@/Helper/instanceFormat.js'

/* Amounts and dates from Artwork-Tickets in the instance's regional format. The currency stays the euro
   whatever the instance budgets in: Artwork-Tickets sells in euros. */
function formatter() {
    return createInstanceFormatter({ ...usePage().props.instanceFormat, currency: 'EUR' })
}

export function formatEuro(cents) {
    return formatter().formatCurrency(cents / 100)
}

export function formatDate(value) {
    return formatter().formatDate(value)
}

export function formatDateTime(value) {
    return formatter().formatDateTime(value)
}

export function formatTime(value) {
    return dayjs(value).format('HH:mm')
}

/** The date with its weekday, which follows the interface language; dayjs knows nothing of vue-i18n on its own. */
export function formatDay(value, locale) {
    return `${dayjs(value).locale(locale).format('dd')}, ${formatDate(value)}`
}
