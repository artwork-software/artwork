import { formatDate, formatEuro, formatPercent } from '@/Helper/ticketingFormat.js'

/* How an automatic discount reads in a list: what it is worth and when it applies. */

/** What a discount or pass counts for, groups first; empty when it names nothing. */
export function validFor(item, productions, groups, t) {
    if (item.allEvents) return t('All productions')
    const named = (list, ids, name) => list.filter((entry) => ids.includes(entry.id)).map(name)
    return [...named(groups, item.groupIds, (group) => group.name), ...named(productions, item.eventIds, (production) => production.title)].join(', ')
}

export function discountWorth(discount, t) {
    if (discount.kind === 'package') {
        return t('{count} dates for {amount}', { count: discount.minDates, amount: formatEuro(discount.value) })
    }
    const amount = discount.kind === 'percent' ? formatPercent(discount.value) : t('{amount} per ticket', { amount: formatEuro(discount.value) })
    return discount.minDates ? t('{amount} from {count} dates', { amount, count: discount.minDates }) : amount
}

export function discountPeriod(discount, t) {
    if (!discount.validFrom && !discount.validUntil) return t('Always')
    if (!discount.validFrom) return t('until {date}', { date: formatDate(discount.validUntil) })
    if (!discount.validUntil) return t('from {date}', { date: formatDate(discount.validFrom) })
    return `${formatDate(discount.validFrom)} – ${formatDate(discount.validUntil)}`
}
