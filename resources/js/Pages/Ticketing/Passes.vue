<template>
    <TicketingSectionLayout :description="$t('Passes and subscriptions people hold, with what is left on them.')" :connected="connected" :tickets-error="ticketsError">
        <div class="flex flex-col gap-4 text-[13px]">
            <div class="flex flex-wrap items-center gap-3">
                <ChoiceSegments :model-value="filters.tab" :label="$t('Show')" :options="kinds" @update:model-value="(tab) => go({ tab, state: undefined })" />
                <ChoiceSegments v-if="filters.tab === 'subscriptions'" :model-value="filters.state ?? ''" :label="$t('State')" :options="states"
                                @update:model-value="(state) => go({ state: state || undefined })" />
                <div class="relative ml-auto w-full max-w-xs">
                    <IconSearch class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                    <input v-model="search" type="search" :placeholder="$t('Name, e-mail or pass code')" :aria-label="$t('Search')"
                           class="h-9 w-full rounded-md border border-border bg-surface pl-8 pr-3 text-sm text-text focus:ring-0" />
                </div>
            </div>

            <div class="rounded-lg border border-border-subtle/70 bg-surface px-3 py-4 shadow-raised sm:px-5">
                <!-- Mehrfachkarten (und die Karten hinter Abos) -->
                <template v-if="filters.tab === 'passes'">
                    <p v-if="!passes?.length" class="py-8 text-center text-text-subtle">
                        {{ filters.q ? $t('No results') : $t('No passes sold yet.') }}
                    </p>
                    <table v-else class="w-full border-collapse">
                        <thead>
                            <tr class="text-left text-xs text-text-subtle">
                                <th class="py-2.5 font-medium">{{ $t('Holder') }}</th>
                                <th class="py-2.5 font-medium">{{ $t('Pass') }}</th>
                                <th class="py-2.5 font-medium">{{ $t('Uses') }}</th>
                                <th class="py-2.5 font-medium">{{ $t('Valid until') }}</th>
                                <th class="py-2.5 pl-4 font-medium">{{ $t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pass in passes" :key="pass.id" class="border-t border-border-hairline">
                                <td class="py-2.5 pr-3">
                                    <span class="block font-medium text-text">{{ pass.holderName }}</span>
                                    <span class="block text-xs text-text-subtle">{{ pass.holderEmail }}</span>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <span class="block text-text">
                                        {{ pass.name }}
                                        <BaseChip v-if="pass.subscriptionId" variant="accent" class="ml-1">{{ $t('Subscription') }}</BaseChip>
                                    </span>
                                    <span class="block font-mono text-xs text-text-subtle">{{ pass.code }}</span>
                                </td>
                                <td class="w-48 py-2.5 pr-3">
                                    <span class="tabular-nums">{{ $t('{left} of {total} left', { left: pass.usesTotal - pass.usesUsed, total: pass.usesTotal }) }}</span>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-sunken">
                                        <div class="h-full rounded-full bg-accent-500" :style="{ width: `${((pass.usesTotal - pass.usesUsed) / Math.max(1, pass.usesTotal)) * 100}%` }"></div>
                                    </div>
                                </td>
                                <td class="py-2.5 pr-3 tabular-nums text-text-muted">{{ pass.validUntil ? formatDate(pass.validUntil) : $t('Unlimited') }}</td>
                                <td class="whitespace-nowrap py-2.5 pl-4">
                                    <BaseChip :variant="passState(pass).variant">{{ passState(pass).label }}</BaseChip>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>

                <!-- Abos -->
                <template v-else>
                    <p v-if="!subscriptions?.length" class="py-8 text-center text-text-subtle">
                        {{ filters.q || filters.state ? $t('No results') : $t('No subscriptions sold yet.') }}
                    </p>
                    <table v-else class="w-full border-collapse">
                        <thead>
                            <tr class="text-left text-xs text-text-subtle">
                                <th class="py-2.5 font-medium">{{ $t('Holder') }}</th>
                                <th class="py-2.5 font-medium">{{ $t('Subscription') }}</th>
                                <th class="py-2.5 text-right font-medium">{{ $t('Price') }}</th>
                                <th class="py-2.5 pl-4 font-medium">{{ $t('Uses') }}</th>
                                <th class="py-2.5 font-medium">{{ $t('Next billing') }}</th>
                                <th class="py-2.5 pl-4 font-medium">{{ $t('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="subscription in subscriptions" :key="subscription.id" class="border-t border-border-hairline">
                                <td class="py-2.5 pr-3">
                                    <span class="block font-medium text-text">{{ subscription.holderName }}</span>
                                    <span class="block text-xs text-text-subtle">{{ subscription.holderEmail }}</span>
                                </td>
                                <td class="py-2.5 pr-3">
                                    <span class="block text-text">{{ subscription.planName }}</span>
                                    <span class="block text-xs text-text-subtle">{{ $t('since {date}', { date: formatDate(subscription.startedAt) }) }}</span>
                                </td>
                                <td class="whitespace-nowrap py-2.5 text-right tabular-nums">
                                    {{ formatEuro(subscription.priceCents) }}
                                    <span class="block text-xs text-text-subtle">{{ subscription.paymentOption === 'monthly' ? $t('per month') : $t('once') }}</span>
                                </td>
                                <td class="py-2.5 pl-4 pr-3 tabular-nums">
                                    {{ subscription.usesTotal === null ? '–' : $t('{left} of {total} left', { left: subscription.usesTotal - subscription.usesUsed, total: subscription.usesTotal }) }}
                                </td>
                                <td class="py-2.5 pr-3 tabular-nums text-text-muted">
                                    {{ subscription.status === 'canceled' || subscription.cancelAt ? '–' : formatDate(subscription.currentPeriodEnd) }}
                                </td>
                                <td class="whitespace-nowrap py-2.5 pl-4">
                                    <BaseChip :variant="subscriptionState(subscription).variant">{{ subscriptionState(subscription).label }}</BaseChip>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </template>
            </div>
        </div>
    </TicketingSectionLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import { IconSearch } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import TicketingSectionLayout from '@/Pages/Ticketing/Components/TicketingSectionLayout.vue'
import { visitWithFilters } from '@/Pages/Ticketing/Components/filterQuery.js'
import { formatDate, formatEuro } from '@/Helper/ticketingFormat.js'

/* Who holds which Mehrfachkarte or Abo, and what is left on it. Read from Artwork-Tickets per visit. */
const props = defineProps({
    connected: { type: Boolean, required: true },
    ticketsError: { type: String, default: null },
    filters: { type: Object, required: true },
    passes: { type: Array, default: null },
    subscriptions: { type: Array, default: null },
})

const { t } = useI18n()

const kinds = [
    { value: 'passes', label: t('Passes') },
    { value: 'subscriptions', label: t('Subscriptions') },
]
const states = [
    { value: '', label: t('All') },
    { value: 'running', label: t('Running') },
    { value: 'pastDue', label: t('Overdue') },
    { value: 'ended', label: t('Ended') },
]

const search = ref(props.filters.q ?? '')
let searchTimer = null

watch(search, (value) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => go({ q: value.trim() || undefined }), 300)
})

function passState(pass) {
    if (pass.status === 'revoked') return { label: t('Revoked'), variant: 'warning' }
    if (pass.validUntil && dayjs(pass.validUntil).isBefore(dayjs())) return { label: t('Expired'), variant: 'neutral' }
    if (pass.usesUsed >= pass.usesTotal) return { label: t('Used up'), variant: 'neutral' }
    return { label: t('Active'), variant: 'success' }
}

function subscriptionState(subscription) {
    if (subscription.status === 'past_due') return { label: t('Overdue'), variant: 'danger' }
    if (subscription.status === 'canceled') return { label: t('Ended'), variant: 'neutral' }
    if (subscription.cancelAt) return { label: t('Ends {date}', { date: formatDate(subscription.cancelAt) }), variant: 'warning' }
    return { label: t('Running'), variant: 'success' }
}

function go(change) {
    visitWithFilters('ticketing.section.passes', { tab: props.filters.tab, state: props.filters.state, q: props.filters.q, ...change })
}
</script>
