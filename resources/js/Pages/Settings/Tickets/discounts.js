import { formatDate, formatEuro } from '@/Helper/ticketingFormat.js'

/* How an automatic discount reads in a list: what it is worth and when it applies. */

export function discountWorth(discount, t) {
    if (discount.kind === 'package') {
        return t('{count} dates for {amount}', { count: discount.minDates, amount: formatEuro(discount.value) })
    }
    const amount = discount.kind === 'percent' ? `${discount.value / 100} %` : t('{amount} per ticket', { amount: formatEuro(discount.value) })
    return discount.minDates ? t('{amount} from {count} dates', { amount, count: discount.minDates }) : amount
}

export function discountPeriod(discount, t) {
    if (!discount.validFrom && !discount.validUntil) return t('Always')
    if (!discount.validFrom) return t('until {date}', { date: formatDate(discount.validUntil) })
    if (!discount.validUntil) return t('from {date}', { date: formatDate(discount.validFrom) })
    return `${formatDate(discount.validFrom)} – ${formatDate(discount.validUntil)}`
}
