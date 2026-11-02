<template>
    <section class="flex flex-col gap-4">
        <div v-if="loading" class="flex items-center gap-2 text-text-subtle">
            <IconLoader2 class="size-4 animate-spin" />{{ $t('Loading…') }}
        </div>

        <template v-else-if="card">
            <dl class="grid grid-cols-3 gap-2">
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="font-lexend text-[11px] text-text-subtle">{{ $t('Uses') }}</dt>
                    <dd class="text-[15px] font-semibold tabular-nums text-text">{{ $t('{left} of {total} left', { left: card.usesTotal - card.usesUsed, total: card.usesTotal }) }}</dd>
                </div>
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="font-lexend text-[11px] text-text-subtle">{{ $t('Valid until') }}</dt>
                    <dd class="text-[15px] font-semibold tabular-nums text-text">{{ card.validUntil ? formatDate(card.validUntil) : $t('Unlimited') }}</dd>
                </div>
                <div class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                    <dt class="font-lexend text-[11px] text-text-subtle">{{ $t('Pass code') }}</dt>
                    <dd class="font-mono text-[15px] font-semibold text-text">{{ card.code }}</dd>
                </div>
            </dl>

            <p v-if="paused" class="rounded-lg border border-warning-border bg-warning-surface px-3 py-2">
                {{ $t('Paused: the pass works again from {date}.', { date: formatDate(card.pausedUntil) }) }}
            </p>
            <p v-if="card.status === 'revoked'" class="rounded-lg border border-warning-border bg-warning-surface px-3 py-2">
                {{ $t('This pass is blocked.') }}
            </p>

            <!-- Korrekturen von Hand: Nutzungen, Gültigkeit, Sperre -->
            <div v-if="mayManage" class="flex flex-col gap-3 rounded-lg border border-border-subtle px-4 py-3">
                <p class="font-lexend text-xs font-medium text-text">{{ $t('Correct uses') }}</p>
                <div class="flex flex-wrap items-end gap-2">
                    <BaseInput id="card-correct-count" v-model="count" type="number" min="1" max="500" label="Number" class="!w-24" />
                    <BaseInput id="card-correct-note" v-model="note" label="Reason (optional)" maxlength="500" class="min-w-48 flex-1" />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="ui-button" :disabled="busy || !validCount" @click="adjust(1)">
                        <IconPlus class="size-3.5" />{{ $t('Add uses') }}
                    </button>
                    <button type="button" class="ui-button" :disabled="busy || !validCount" @click="adjust(-1)">
                        <IconMinus class="size-3.5" />{{ $t('Take uses off') }}
                    </button>
                </div>
                <p class="text-xs text-text-subtle">
                    {{ subscription
                        ? $t('Added uses never lapse on a subscription and are spent last. Taking off counts uses as spent, without an admission.')
                        : $t('Taking off counts uses as spent, without an admission. Both show up in the history.') }}
                </p>

                <div v-if="!subscription" class="flex flex-wrap items-end gap-2 border-t border-border-hairline pt-3">
                    <BaseInput id="card-valid-until" v-model="validDay" type="date" label="Valid until (empty: no end)" class="!w-56" />
                    <button type="button" class="ui-button" :disabled="busy" @click="send({ kind: 'validUntil', day: validDay || null })">{{ $t('Save') }}</button>
                </div>

                <div class="border-t border-border-hairline pt-3">
                    <button v-if="card.status === 'revoked'" type="button" class="ui-button" :disabled="busy" @click="send({ kind: 'reactivate' })">
                        {{ $t('Unblock pass') }}
                    </button>
                    <div v-else-if="confirmingBlock" class="flex flex-wrap items-center gap-2">
                        <span class="flex-1">{{ $t('Block this pass? It can no longer be redeemed or stamped. The history stays.') }}</span>
                        <button type="button" class="ui-button" @click="confirmingBlock = false">{{ $t('Cancel') }}</button>
                        <button type="button" class="ui-button-delete" :disabled="busy" @click="block">{{ $t('Block pass') }}</button>
                    </div>
                    <button v-else type="button" class="ui-button-delete" @click="confirmingBlock = true">
                        {{ $t('Block pass') }}
                    </button>
                </div>
            </div>

            <div>
                <p class="mb-1.5 font-lexend text-xs font-medium text-text">{{ $t('History') }}</p>
                <p v-if="!card.history.length" class="text-text-subtle">{{ $t('No uses yet.') }}</p>
                <ul v-else class="divide-y divide-border-hairline">
                    <li v-for="entry in card.history" :key="entry.id" class="flex flex-col gap-0.5 py-2">
                        <span class="text-text">
                            {{ entryLabel(entry) }}
                            <span v-if="entry.eventTitle" class="text-text-subtle"> · {{ entry.eventTitle }}</span>
                            <span v-if="entry.status === 'released'" class="text-text-subtle"> · {{ $t('returned') }}</span>
                            <span v-else-if="entry.status === 'pending'" class="text-text-subtle"> · {{ $t('reserved') }}</span>
                        </span>
                        <span class="text-xs tabular-nums text-text-subtle">
                            {{ formatDateTime(entry.createdAt) }}<template v-if="entry.createdByName"> · {{ entry.createdByName }}</template><template v-if="entry.orderNumber"> · {{ entry.orderNumber }}</template>
                        </span>
                        <span v-if="entry.note" class="text-xs text-text-subtle">{{ entry.note }}</span>
                    </li>
                </ul>
            </div>
        </template>

        <p v-if="error" class="text-danger">{{ error }}</p>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import { IconLoader2, IconMinus, IconPlus } from '@tabler/icons-vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { formatDate, formatDateTime } from '@/Helper/ticketingFormat.js'

/* The card behind a row: balance, history, and the corrections by hand. Read from Artwork-Tickets when
   opened; every correction answers with the card as it now stands. */
const props = defineProps({
    passId: { type: String, required: true },
    mayManage: { type: Boolean, default: false },
    /** The card of an Abo: its last day follows the paid periods. */
    subscription: { type: Boolean, default: false },
})

const emit = defineEmits(['changed'])

const { t } = useI18n()

const card = ref(null)
const loading = ref(true)
const busy = ref(false)
const error = ref('')
const count = ref('1')
const note = ref('')
const validDay = ref('')
const confirmingBlock = ref(false)

const validCount = computed(() => Number.parseInt(count.value, 10) > 0)
const paused = computed(() => card.value?.pausedUntil && dayjs(card.value.pausedUntil).isAfter(dayjs()))

function show(pass) {
    card.value = pass
    validDay.value = pass.validUntil ? dayjs(pass.validUntil).format('YYYY-MM-DD') : ''
}

onMounted(async () => {
    try {
        const { data } = await axios.get(route('ticketing.section.pass', props.passId))
        show(data.pass)
    } catch (failure) {
        error.value = failure.response?.data?.message ?? t('Artwork-Tickets could not be reached.')
    } finally {
        loading.value = false
    }
})

async function send(command) {
    busy.value = true
    error.value = ''
    try {
        const { data } = await axios.post(route('ticketing.section.pass.correct', props.passId), command)
        show(data.pass)
        emit('changed')
    } catch (failure) {
        error.value = failure.response?.data?.message ?? t('Artwork-Tickets could not be reached.')
    } finally {
        busy.value = false
    }
}

async function adjust(sign) {
    await send({ kind: 'adjust', delta: sign * Number.parseInt(count.value, 10), note: note.value.trim() || null })
    if (!error.value) note.value = ''
}

async function block() {
    await send({ kind: 'revoke' })
    confirmingBlock.value = false
}

const changeLabels = {
    cancel: () => t('Subscription cancelled at the end of the period'),
    endNow: () => t('Subscription ended at once'),
    pause: (entry) => t('Subscription paused, usable again from {date}', { date: formatDate(entry.resumesAt) }),
    resume: () => t('Pause ended'),
    withdrawCancellation: () => t('Cancellation withdrawn'),
}

function entryLabel(entry) {
    if (entry.kind === 'subscriptionChange') return changeLabels[entry.change](entry)
    if (entry.kind === 'adjustment') {
        return entry.delta > 0
            ? t('{count} uses added', { count: entry.delta }, entry.delta)
            : t('{count} uses taken off', { count: -entry.delta }, -entry.delta)
    }
    return entry.kind === 'stamp' ? t('Stamped') : t('Redeemed in the checkout')
}
</script>
