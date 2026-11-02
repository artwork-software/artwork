<template>
    <TicketingSectionLayout :description="$t('What Artwork-Tickets sold, by period and production.')" :connected="connected" :tickets-error="ticketsError">
        <div v-if="sales" class="flex flex-col gap-5 text-[13px]">
            <div class="flex flex-wrap items-center gap-3">
                <ChoiceSegments :model-value="String(sales.range.preset ?? '')" :label="$t('Period')" :options="periods"
                                @update:model-value="(days) => go({ days, from: undefined, to: undefined })" />
                <DateRangeControl :date-value-array="[sales.range.fromDay, sales.range.toDay]" mode="local" compact past :show-today="false"
                                  @change="([from, to]) => go({ days: undefined, from, to })" />
                <div class="ml-auto">
                    <ProductionFilter :model-value="sales.eventIds" :productions="sales.productions" @update:model-value="(events) => go({ events })" />
                </div>
            </div>

            <div v-if="selectedProductions.length > 0" class="-mt-2 flex flex-wrap items-center gap-1.5">
                <span v-for="production in selectedProductions" :key="production.id"
                      class="inline-flex items-center gap-1 rounded-full border border-accent-600/30 bg-accent-600/5 py-0.5 pl-2.5 pr-1 text-xs text-text">
                    {{ production.title }}
                    <button type="button" class="rounded-full p-0.5 text-text-subtle hover:bg-accent-600/10 hover:text-text" :aria-label="$t('Remove filter')"
                            @click="go({ events: sales.eventIds.filter((id) => id !== production.id) })">
                        <IconX class="size-3" />
                    </button>
                </span>
                <button v-if="selectedProductions.length > 1" type="button" class="ml-1 text-xs text-text-subtle hover:text-text" @click="go({ events: [] })">
                    {{ $t('Clear selection') }}
                </button>
            </div>

            <div v-if="!sales.hasEverSold" class="rounded-lg border border-border-subtle/70 bg-surface px-5 py-12 text-center shadow-raised">
                <p class="font-lexend text-sm font-medium text-text">{{ $t('Nothing sold yet') }}</p>
                <p class="mx-auto mt-1 max-w-md text-text-muted">{{ $t('Once tickets, passes or subscriptions are sold, revenue and orders appear here.') }}</p>
            </div>

            <template v-else>
                <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    <div v-for="kpi in kpis" :key="kpi.label" class="rounded-lg border border-border-subtle/70 bg-surface px-4 py-3.5 shadow-raised">
                        <dt class="font-lexend text-xs text-text-subtle">{{ kpi.label }}</dt>
                        <dd class="mt-1 flex items-baseline gap-2">
                            <span class="text-xl font-semibold tabular-nums text-text">{{ kpi.value }}</span>
                            <span v-if="kpi.delta !== null" class="text-xs font-medium tabular-nums" :class="kpi.delta >= 0 ? 'text-success' : 'text-danger'">
                                {{ kpi.delta >= 0 ? '+' : '' }}{{ kpi.delta }} %
                            </span>
                        </dd>
                        <p v-if="kpi.hint" class="mt-0.5 text-[11px] text-text-subtle">{{ kpi.hint }}</p>
                    </div>
                </dl>

                <div class="grid gap-5 lg:grid-cols-3">
                    <section class="rounded-lg border border-border-subtle/70 bg-surface px-5 py-4 shadow-raised lg:col-span-2">
                        <h2 class="font-lexend text-sm font-semibold text-text">{{ $t('Revenue per day') }}</h2>
                        <div class="mt-4 flex h-40 items-end gap-[3px]">
                            <div v-for="day in sales.series" :key="day.day" class="group relative flex h-full flex-1 items-end"
                                 :title="`${formatDate(day.day)} · ${formatEuro(day.revenueCents)} · ${$t('{count} tickets', { count: day.ticketsSold }, day.ticketsSold)}`">
                                <div class="w-full rounded-t-sm bg-accent-500/80 transition-colors group-hover:bg-accent-600"
                                     :style="{ height: `${Math.max(day.revenueCents > 0 ? 3 : 0, (day.revenueCents / maxDay) * 100)}%` }"></div>
                            </div>
                        </div>
                        <div class="mt-1.5 flex justify-between text-[11px] text-text-subtle tabular-nums">
                            <span>{{ formatDate(sales.range.fromDay) }}</span>
                            <span>{{ formatDate(sales.range.toDay) }}</span>
                        </div>
                    </section>

                    <section class="rounded-lg border border-border-subtle/70 bg-surface px-5 py-4 shadow-raised">
                        <h2 class="font-lexend text-sm font-semibold text-text">{{ $t('By production') }}</h2>
                        <p v-if="sales.events.length === 0" class="mt-3 text-text-subtle">{{ $t('No sales in this period.') }}</p>
                        <ul v-else class="mt-3 flex flex-col gap-2.5">
                            <li v-for="production in sales.events" :key="production.id ?? 'other'">
                                <div class="flex items-baseline justify-between gap-3">
                                    <span class="truncate text-text">{{ productionTitle(production) }}</span>
                                    <span class="whitespace-nowrap tabular-nums text-text-muted">{{ formatEuro(production.revenueCents) }}</span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-sunken">
                                    <div class="h-full rounded-full bg-accent-500" :style="{ width: `${(production.revenueCents / maxProduction) * 100}%` }"></div>
                                </div>
                            </li>
                        </ul>
                    </section>
                </div>

                <section class="rounded-lg border border-border-subtle/70 bg-surface px-3 py-4 shadow-raised sm:px-5">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="font-lexend text-sm font-semibold text-text">{{ $t('Orders') }}</h2>
                        <span v-if="sales.orderTotal > sales.orders.length" class="text-xs text-text-subtle">
                            {{ $t('Latest {shown} of {total}', { shown: sales.orders.length, total: sales.orderTotal }) }}
                        </span>
                    </div>
                    <p v-if="sales.orders.length === 0" class="mt-3 text-text-subtle">{{ $t('No orders in this period.') }}</p>
                    <div v-else class="mt-3 overflow-x-auto">
                        <table class="w-full min-w-[760px] border-collapse">
                            <thead>
                                <tr class="text-left text-xs text-text-subtle">
                                    <th class="py-2.5 font-medium">{{ $t('Order') }}</th>
                                    <th class="py-2.5 font-medium">{{ $t('Buyer') }}</th>
                                    <th class="py-2.5 font-medium">{{ $t('Production') }}</th>
                                    <th class="py-2.5 text-right font-medium">{{ $t('Tickets') }}</th>
                                    <th class="py-2.5 text-right font-medium">{{ $t('Amount') }}</th>
                                    <th class="py-2.5 pl-4 font-medium">{{ $t('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="order in sales.orders" :key="order.id" class="border-t border-border-hairline">
                                    <td class="py-2.5 pr-3">
                                        <span class="block font-mono text-xs text-text">{{ order.orderNumber }}</span>
                                        <span class="block text-xs text-text-subtle">{{ formatDateTime(order.paidAt ?? order.createdAt) }}</span>
                                    </td>
                                    <td class="py-2.5 pr-3">
                                        <span class="block text-text">{{ order.buyerName || '–' }}</span>
                                        <span class="block text-xs text-text-subtle">{{ order.email || '–' }}</span>
                                    </td>
                                    <td class="max-w-[260px] py-2.5 pr-3">
                                        <span class="block truncate text-text">{{ order.eventTitle ?? (order.passCount > 0 ? $t('Pass or subscription') : '–') }}</span>
                                        <span v-if="order.otherEventCount > 0" class="block text-xs text-text-subtle">{{ $t('+ {count} more', { count: order.otherEventCount }) }}</span>
                                    </td>
                                    <td class="py-2.5 pr-3 text-right tabular-nums">{{ order.ticketCount || '–' }}</td>
                                    <td class="py-2.5 text-right tabular-nums">
                                        {{ formatEuro(order.subtotalCents - order.discountCents) }}
                                        <span v-if="order.couponCode" class="block font-mono text-[11px] text-text-subtle">{{ order.couponCode }}</span>
                                    </td>
                                    <td class="whitespace-nowrap py-2.5 pl-4">
                                        <BaseChip :variant="statusVariant[order.status] ?? 'neutral'">{{ statusLabel[order.status] ?? order.status }}</BaseChip>
                                        <BaseChip v-if="order.channel === 'box_office'" variant="accent" class="ml-1">{{ $t('Box office') }}</BaseChip>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </template>
        </div>
    </TicketingSectionLayout>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { IconX } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import DateRangeControl from '@/Artwork/DateRange/DateRangeControl.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import ProductionFilter from '@/Pages/Ticketing/Components/ProductionFilter.vue'
import TicketingSectionLayout from '@/Pages/Ticketing/Components/TicketingSectionLayout.vue'
import { visitWithFilters } from '@/Pages/Ticketing/Components/filterQuery.js'
import { formatDate, formatDateTime, formatEuro } from '@/Helper/ticketingFormat.js'

/* The sales of a period as Artwork-Tickets counts them: revenue without fees, by the day the order was
   paid; door sales count as revenue but are settled at the house's own till. */
const props = defineProps({
    connected: { type: Boolean, required: true },
    ticketsError: { type: String, default: null },
    filters: { type: Object, required: true },
    sales: { type: Object, default: null },
})

const { t } = useI18n()

const periods = [
    { value: '7', label: t('7 days') },
    { value: '30', label: t('30 days') },
    { value: '90', label: t('90 days') },
]

const statusLabel = {
    paid: t('Paid'),
    pending: t('Open'),
    refunded: t('Refunded'),
    cancelled: t('Cancelled'),
    expired: t('Expired'),
    failed: t('Failed'),
}
const statusVariant = { paid: 'success', refunded: 'warning', pending: 'neutral' }

const selectedProductions = computed(() => props.sales.productions.filter((production) => props.sales.eventIds.includes(production.id)))

const maxDay = computed(() => Math.max(1, ...props.sales.series.map((day) => day.revenueCents)))
const maxProduction = computed(() => Math.max(1, ...props.sales.events.map((production) => production.revenueCents)))

function delta(current, previous) {
    return previous > 0 ? Math.round(((current - previous) / previous) * 100) : null
}

const kpis = computed(() => {
    const { current, previous } = props.sales
    const filtered = props.sales.eventIds.length > 0
    const average = (window) => (filtered
        ? (window.ticketsSold > 0 ? Math.round(window.revenueCents / window.ticketsSold) : 0)
        : (window.orderCount > 0 ? Math.round(window.revenueCents / window.orderCount) : 0))

    return [
        {
            label: t('Revenue'),
            value: formatEuro(current.revenueCents),
            delta: delta(current.revenueCents, previous.revenueCents),
            hint: current.payoutCents !== null ? t('{amount} paid out online', { amount: formatEuro(current.payoutCents) }) : null,
        },
        { label: t('Tickets sold'), value: current.ticketsSold, delta: delta(current.ticketsSold, previous.ticketsSold) },
        { label: t('Orders'), value: current.orderCount, delta: delta(current.orderCount, previous.orderCount) },
        {
            label: filtered ? t('Average per ticket') : t('Average per order'),
            value: formatEuro(average(current)),
            delta: delta(average(current), average(previous)),
        },
    ]
})

function productionTitle(production) {
    if (production.id === 'passes') return t('Passes & subscriptions')
    return production.id ? production.title : t('Other')
}

/** A preset period and a custom range replace each other; the productions carry over. */
function go(change) {
    visitWithFilters('ticketing.section.sales', { days: props.filters.days, from: props.filters.from, to: props.filters.to, events: props.sales.eventIds, ...change })
}
</script>
