<template>
    <TicketingSectionLayout :description="$t('Passes and subscriptions people hold, with what is left on them.')" :connected="connected" :tickets-error="ticketsError">
        <div class="flex flex-col gap-4 text-[13px]">
            <div class="flex flex-wrap items-center gap-3">
                <ChoiceSegments :model-value="filters.kind ?? ''" :label="$t('Show')" :options="kinds"
                                @update:model-value="(kind) => go({ kind: kind || undefined, state: kind === 'pass' && filters.state === 'pastDue' ? undefined : filters.state })" />
                <ChoiceSegments :model-value="filters.state ?? ''" :label="$t('State')" :options="states"
                                @update:model-value="(state) => go({ state: state || undefined })" />
                <div class="relative ml-auto w-full max-w-xs">
                    <IconSearch class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                    <input v-model="search" type="search" :placeholder="$t('Name, e-mail or pass code')" :aria-label="$t('Search')"
                           class="h-9 w-full rounded-md border border-border bg-surface pl-8 pr-3 text-sm text-text focus:ring-0" />
                </div>
            </div>

            <div class="rounded-lg border border-border-subtle/70 bg-surface px-3 py-4 shadow-raised sm:px-5">
                <p v-if="!holdings?.length" class="py-8 text-center text-text-subtle">
                    {{ filters.q || filters.kind || filters.state ? $t('No results') : $t('No passes or subscriptions sold yet.') }}
                </p>
                <table v-else class="w-full border-collapse">
                    <thead>
                        <tr class="text-left text-xs text-text-subtle">
                            <th class="py-2.5 font-medium">{{ $t('Holder') }}</th>
                            <th class="py-2.5 font-medium">{{ $t('Pass or subscription') }}</th>
                            <th class="py-2.5 font-medium">{{ $t('Uses') }}</th>
                            <th class="py-2.5 text-right font-medium">{{ $t('Price') }}</th>
                            <th class="py-2.5 pl-4 font-medium">{{ $t('Valid until / next charge') }}</th>
                            <th class="py-2.5 pl-4 font-medium">{{ $t('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="holding in holdings" :key="holding.id" class="cursor-pointer border-t border-border-hairline hover:bg-surface-sunken"
                            tabindex="0" @click="selected = holding" @keydown.enter="selected = holding">
                            <td class="py-2.5 pr-3">
                                <span class="block font-medium text-text">{{ holding.holderName }}</span>
                                <span class="block text-xs text-text-subtle">{{ holding.holderEmail }}</span>
                            </td>
                            <td class="py-2.5 pr-3">
                                <span class="flex flex-wrap items-center gap-1.5 text-text">
                                    {{ holding.kind === 'subscription' ? holding.planName : holding.name }}
                                    <BaseChip :variant="holding.kind === 'subscription' ? 'accent' : 'neutral'">
                                        {{ holding.kind === 'subscription' ? $t('Subscription') : $t('Multi-use pass') }}
                                    </BaseChip>
                                </span>
                                <span class="block font-mono text-xs text-text-subtle">{{ code(holding) }}</span>
                            </td>
                            <td class="w-48 py-2.5 pr-3">
                                <template v-if="hasCard(holding)">
                                    <span class="tabular-nums">{{ $t('{left} of {total} left', { left: holding.usesTotal - holding.usesUsed, total: holding.usesTotal }) }}</span>
                                    <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-surface-sunken">
                                        <div class="h-full rounded-full bg-accent-500" :style="{ width: `${((holding.usesTotal - holding.usesUsed) / Math.max(1, holding.usesTotal)) * 100}%` }"></div>
                                    </div>
                                </template>
                                <span v-else class="text-text-subtle">–</span>
                            </td>
                            <td class="whitespace-nowrap py-2.5 text-right tabular-nums">
                                <template v-if="holding.kind === 'subscription'">
                                    {{ formatEuro(holding.priceCents) }}
                                    <span class="block text-xs text-text-subtle">{{ holding.paymentOption === 'monthly' ? $t('per month') : $t('once') }}</span>
                                </template>
                                <span v-else class="text-text-subtle">{{ $t('since {date}', { date: formatDate(holding.createdAt) }) }}</span>
                            </td>
                            <td class="py-2.5 pl-4 pr-3 tabular-nums text-text-muted">{{ nextStep(holding) }}</td>
                            <td class="whitespace-nowrap py-2.5 pl-4">
                                <BaseChip :variant="stateOf(holding).variant">{{ stateOf(holding).label }}</BaseChip>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <TicketingHolderModal v-if="selected" :pass="selected.kind === 'pass' ? selected : null" :subscription="selected.kind === 'subscription' ? selected : null"
                              :may-manage="mayManage" @changed="reload" @close="selected = null" />
    </TicketingSectionLayout>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconSearch } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import TicketingHolderModal from '@/Pages/Ticketing/Components/TicketingHolderModal.vue'
import TicketingSectionLayout from '@/Pages/Ticketing/Components/TicketingSectionLayout.vue'
import { visitWithFilters } from '@/Pages/Ticketing/Components/filterQuery.js'
import { passState, subscriptionState } from '@/Pages/Ticketing/Components/holderState.js'
import { formatDate, formatEuro } from '@/Helper/ticketingFormat.js'

/* Who holds which Mehrfachkarte or Abo, and what is left on it — one list, each row marked as what it is.
   An Abo is one row with its card inside. Read from Artwork-Tickets per visit; a row opens its dialog,
   where who may manage passes corrects them. */
const props = defineProps({
    connected: { type: Boolean, required: true },
    ticketsError: { type: String, default: null },
    filters: { type: Object, required: true },
    holdings: { type: Array, default: null },
    mayManage: { type: Boolean, default: false },
})

const { t } = useI18n()

const kinds = [
    { value: '', label: t('All') },
    { value: 'pass', label: t('Multi-use passes') },
    { value: 'subscription', label: t('Subscriptions') },
]
/* Only Abos fall behind on a payment. */
const states = computed(() => [
    { value: '', label: t('All') },
    { value: 'running', label: t('Running') },
    ...(props.filters.kind === 'pass' ? [] : [{ value: 'pastDue', label: t('Overdue') }]),
    { value: 'ended', label: t('Ended') },
])

const search = ref(props.filters.q ?? '')
let searchTimer = null

watch(search, (value) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => go({ q: value.trim() || undefined }), 300)
})

/* The open row stays open after a change, even when the filter no longer shows it: it is read again on its own. */
const selected = ref(null)

function hasCard(holding) {
    return holding.kind === 'pass' || holding.passId !== null
}

function code(holding) {
    return holding.kind === 'pass' ? holding.code : holding.passCode
}

function stateOf(holding) {
    return holding.kind === 'pass' ? passState(holding, t) : subscriptionState(holding, t)
}

function nextStep(holding) {
    if (holding.kind === 'pass') {
        return holding.validUntil ? formatDate(holding.validUntil) : t('Unlimited')
    }
    if (holding.status === 'canceled' || !holding.currentPeriodEnd) return '–'
    const end = holding.seasonEndsAt ?? holding.cancelAt ?? (holding.afterTerm === 'ends' ? holding.termEndsAt : null)
    const chargesAgain = !holding.cancelAt && (!end || new Date(holding.currentPeriodEnd) < new Date(end))
    return chargesAgain
        ? t('Charge on {date}', { date: formatDate(holding.currentPeriodEnd) })
        : t('Ends {date}', { date: formatDate(end ?? holding.currentPeriodEnd) })
}

function go(change) {
    visitWithFilters('ticketing.section.passes', { kind: props.filters.kind, state: props.filters.state, q: props.filters.q, ...change })
}

async function reload() {
    router.reload({ only: ['holdings'] })
    const { kind, id, holderEmail } = selected.value
    // Fails the read, the dialog keeps the row as it was; the list shows the error.
    const fresh = await axios.get(route('ticketing.section.holdings'), { params: { kind, q: holderEmail } })
        .then(({ data }) => data.holdings?.find((holding) => holding.id === id), () => null)
    if (fresh && selected.value?.id === id) selected.value = fresh
}
</script>
