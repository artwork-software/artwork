import dayjs from 'dayjs'
import { formatDate } from '@/Helper/ticketingFormat.js'

/* How a card or an Abo reads in the list and in its dialog. */

function pausedUntil(row) {
    return row.pausedUntil && dayjs(row.pausedUntil).isAfter(dayjs()) ? row.pausedUntil : null
}

export function passState(pass, t) {
    if (pass.status === 'revoked') return { label: t('Revoked'), variant: 'warning' }
    if (pass.validUntil && dayjs(pass.validUntil).isBefore(dayjs())) return { label: t('Expired'), variant: 'neutral' }
    if (pausedUntil(pass)) return { label: t('Paused'), variant: 'warning' }
    if (pass.usesUsed >= pass.usesTotal) return { label: t('Used up'), variant: 'neutral' }
    return { label: t('Active'), variant: 'success' }
}

export function subscriptionState(subscription, t) {
    if (subscription.status === 'past_due') return { label: t('Overdue'), variant: 'danger' }
    if (subscription.status === 'canceled') return { label: t('Ended'), variant: 'neutral' }
    if (pausedUntil(subscription)) return { label: t('Paused, usable again from {date}', { date: formatDate(subscription.pausedUntil) }), variant: 'warning' }
    if (subscription.cancelAt) return { label: t('Ends {date}', { date: formatDate(subscription.cancelAt) }), variant: 'warning' }
    return { label: t('Running'), variant: 'success' }
}
