<template>
    <div class="flex min-h-0 flex-1 flex-col gap-3">
        <!-- Eingelassen: was tickets gebucht hat, dann weiter mit dem nächsten Gast -->
        <div v-if="admitted" class="flex flex-1 flex-col items-center justify-center gap-3 rounded-lg border border-success-border bg-success-surface px-6 py-10 text-center">
            <IconCircleCheck class="size-9 text-success" />
            <p class="font-lexend text-base font-semibold text-text">{{ $t('{name} is in', { name: selected.holderName }) }}</p>
            <p class="text-[13px] text-text-muted">
                {{ admitted.paidCents > 0
                    ? $t('{amount} paid externally · ticket {code} · order {order}', { amount: formatEuro(admitted.paidCents), code: admitted.ticketCode, order: admitted.orderNumber })
                    : $t('Covered by the pass · ticket {code} · order {order}', { code: admitted.ticketCode, order: admitted.orderNumber }) }}
            </p>
            <button type="button" class="ui-button-add mt-2" @click="reset">{{ $t('Next guest') }}</button>
        </div>

        <!-- Gewählte Karte: Preisklasse, was zu zahlen ist, und wie -->
        <div v-else-if="selected" class="flex min-h-0 flex-1 flex-col gap-4 overflow-y-auto">
            <button type="button" class="inline-flex items-center gap-1 self-start text-xs text-text-subtle hover:text-text" @click="selected = null">
                <IconArrowLeft class="size-3.5" />{{ $t('Back to the search') }}
            </button>

            <div class="rounded-lg border border-border-subtle bg-surface-sunken px-4 py-3">
                <p class="font-lexend text-sm font-semibold text-text">{{ selected.holderName }}</p>
                <p class="text-xs text-text-subtle">
                    {{ selected.name }} · <span class="font-mono">{{ selected.code }}</span> ·
                    {{ $t('{count} uses left', { count: selected.usesLeft }, selected.usesLeft) }}
                </p>
            </div>

            <div>
                <span class="mb-1.5 block font-lexend text-xs font-medium text-[#3F424A]">{{ $t('Price class') }}</span>
                <div class="grid gap-2 sm:grid-cols-2">
                    <button v-for="category in categories" :key="category.id" type="button" :disabled="category.available < 1"
                            class="flex items-start justify-between gap-3 rounded-lg border px-3.5 py-3 text-left transition-colors disabled:cursor-not-allowed disabled:opacity-60"
                            :class="categoryId === category.id ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface hover:bg-surface-sunken'"
                            @click="categoryId = category.id">
                        <span>
                            <span class="block font-lexend text-[13px] font-medium text-text">{{ category.name }}</span>
                            <span class="block text-xs text-text-subtle">
                                {{ category.available < 1 ? $t('Sold out') : $t('Price {price}', { price: formatEuro(category.priceCents) }) }}
                            </span>
                        </span>
                        <span class="whitespace-nowrap text-[13px] font-semibold tabular-nums text-text">{{ formatEuro(toPayFor(category.id)) }}</span>
                    </button>
                </div>
            </div>

            <div v-if="categoryId" class="rounded-lg border px-4 py-4" :class="toPay > 0 ? 'border-warning-border bg-warning-surface' : 'border-success-border bg-success-surface'">
                <p class="font-lexend text-base font-semibold text-text">
                    {{ toPay > 0 ? $t('Customer still pays {amount}', { amount: formatEuro(toPay) }) : $t('Fully covered by the pass') }}
                </p>
                <p class="mt-0.5 text-xs text-text-muted">
                    {{ toPay > 0 ? $t('One use of the pass is applied; the rest is paid at the box office.') : $t('One use of the pass is applied.') }}
                </p>
                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <template v-if="toPay > 0">
                        <button type="button" class="ui-button-add" :disabled="admitting" @click="admit">
                            <IconLoader2 v-if="admitting" class="size-4 animate-spin" />
                            <IconCash v-else class="size-4" />{{ $t('Paid externally') }}
                        </button>
                        <button type="button" class="ui-button" disabled :title="$t('Coming soon')">
                            <IconCreditCard class="size-4" />{{ $t('Pay with Artwork-Tickets') }}
                        </button>
                        <span class="text-xs text-text-subtle">{{ $t('Coming soon') }}</span>
                    </template>
                    <button v-else type="button" class="ui-button-add" :disabled="admitting" @click="admit">
                        <IconLoader2 v-if="admitting" class="size-4 animate-spin" />
                        <IconCheck v-else class="size-4" />{{ $t('Check in') }}
                    </button>
                </div>
            </div>
            <p v-if="admitError" class="text-[13px] text-danger">{{ admitError }}</p>
        </div>

        <!-- Suche -->
        <template v-else>
            <div class="relative shrink-0">
                <IconSearch class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                <input v-model="query" type="search" :placeholder="$t('Holder name, e-mail or pass code')" :aria-label="$t('Search passes and subscriptions')"
                       class="h-9 w-full rounded-md border border-border bg-surface pl-8 pr-3 text-sm text-text focus:ring-0" />
            </div>

            <p v-if="result?.seated" class="shrink-0 rounded-md bg-surface-sunken px-3.5 py-3 text-[13px] leading-5 text-text-muted">
                {{ $t('This date has a seating plan. Admitting pass holders here works for free seating only; sell the seat in Artwork-Tickets.') }}
            </p>

            <div class="min-h-0 flex-1 overflow-y-auto rounded-lg border border-border-subtle bg-surface">
                <div v-if="loading" class="flex items-center justify-center gap-2 px-4 py-8 text-sm text-text-subtle">
                    <IconLoader2 class="size-4 animate-spin" />{{ $t('Loading…') }}
                </div>
                <p v-else-if="searchError" class="px-4 py-8 text-center text-sm text-danger">{{ searchError }}</p>
                <p v-else-if="query.trim().length < 2" class="px-4 py-8 text-center text-[13px] text-text-subtle">
                    {{ $t('Search for a holder to admit them with a pass or subscription, even without a ticket.') }}
                </p>
                <p v-else-if="passes.length === 0" class="px-4 py-8 text-center text-[13px] text-text-subtle">{{ $t('No results') }}</p>
                <ul v-else class="divide-y divide-border-hairline">
                    <li v-for="pass in passes" :key="pass.id" class="flex items-center justify-between gap-4 px-4 py-3">
                        <div class="min-w-0">
                            <p class="truncate text-[13px] font-medium text-text">
                                {{ pass.holderName }}
                                <span class="font-normal text-text-subtle">· {{ pass.holderEmail }}</span>
                            </p>
                            <p class="text-xs text-text-subtle">
                                {{ pass.name }}
                                <BaseChip v-if="pass.subscription" variant="accent" class="ml-1">{{ $t('Subscription') }}</BaseChip>
                                · <span class="font-mono">{{ pass.code }}</span>
                                · {{ $t('{count} uses left', { count: pass.usesLeft }, pass.usesLeft) }}
                            </p>
                        </div>
                        <BaseChip v-if="pass.block" variant="warning" class="shrink-0">{{ blockLabel[pass.block] }}</BaseChip>
                        <button v-else type="button" class="ui-button-small shrink-0" :disabled="result.seated" @click="choose(pass)">{{ $t('Admit') }}</button>
                    </li>
                </ul>
            </div>
        </template>
    </div>
</template>

<script setup>
import { computed, ref, watch } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconArrowLeft, IconCash, IconCheck, IconCircleCheck, IconCreditCard, IconLoader2, IconSearch } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import { formatEuro } from '@/Helper/ticketingFormat.js'

/* Admitting a holder of a Mehrfachkarte or Abo who has no ticket: Artwork-Tickets books a ticket at the
   box office, applies one use of the card and checks it in. What the card does not cover is paid at the
   house's own till ("Extern bezahlt"); paying through Artwork-Tickets is still to come. */
const props = defineProps({
    eventId: { type: Number, required: true },
})

const emit = defineEmits(['admitted'])

const { t } = useI18n()

const query = ref('')
const result = ref(null)
const loading = ref(false)
const searchError = ref('')
const selected = ref(null)
const categoryId = ref(null)
const admitting = ref(false)
const admitError = ref('')
const admitted = ref(null)

const passes = computed(() => result.value?.passes ?? [])
const categories = computed(() => result.value?.categories ?? [])
const toPay = computed(() => toPayFor(categoryId.value))

const blockLabel = {
    inactive: t('Revoked'),
    expired: t('Expired'),
    exhausted: t('No uses left'),
    notForEvent: t('Not valid for this production'),
    notAtDoor: t('Online only'),
}

function toPayFor(id) {
    return selected.value?.toPay.find((entry) => entry.priceCategoryId === id)?.cents ?? 0
}

let searchTimer = null
let searchRun = 0

watch(query, (value) => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => load(value), 300)
})

async function load(value) {
    const run = ++searchRun
    loading.value = value.trim().length >= 2
    searchError.value = ''
    try {
        const { data } = await axios.get(route('ticketing.door.passes', props.eventId), { params: { q: value.trim() } })
        if (run === searchRun) result.value = data
    } catch (error) {
        if (run === searchRun) searchError.value = error?.response?.data?.message || t('The passes could not be loaded.')
    } finally {
        if (run === searchRun) loading.value = false
    }
}

function choose(pass) {
    selected.value = pass
    admitError.value = ''
    categoryId.value = categories.value.find((category) => category.available > 0)?.id ?? null
}

async function admit() {
    admitting.value = true
    admitError.value = ''
    try {
        const { data } = await axios.post(route('ticketing.door.admit', props.eventId), {
            pass_id: selected.value.id,
            price_category_id: categoryId.value,
        }, { skipErrorToast: true })
        admitted.value = data
        emit('admitted')
    } catch (error) {
        admitError.value = error?.response?.data?.message || t('The admission failed.')
    } finally {
        admitting.value = false
    }
}

function reset() {
    admitted.value = null
    selected.value = null
    categoryId.value = null
    query.value = ''
}

load('')
</script>
