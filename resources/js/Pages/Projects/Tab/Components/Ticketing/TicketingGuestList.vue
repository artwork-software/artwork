<template>
    <div class="flex min-h-0 flex-1 flex-col gap-3">
        <div class="flex shrink-0 flex-wrap items-center gap-3">
            <div class="relative min-w-[220px] flex-1">
                <IconSearch class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                <input v-model="search" type="search" :placeholder="$t('Name, e-mail or ticket code')" :aria-label="$t('Search guests')"
                       class="h-9 w-full rounded-md border border-border bg-surface pl-8 pr-3 text-sm text-text focus:ring-0" />
            </div>
            <ChoiceSegments v-model="filter" :label="$t('Show')" :options="filterOptions" />
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto rounded-lg border border-border-subtle bg-surface">
            <table class="w-full border-collapse text-[13px]">
                <thead class="sticky top-0 z-10 bg-surface shadow-[0_1px_0_var(--color-border-hairline)]">
                    <tr class="font-lexend text-xs text-text-subtle">
                        <th class="px-4 py-2.5 text-left font-medium">{{ $t('Guest') }}</th>
                        <th class="px-4 py-2.5 text-left font-medium">{{ $t('Price class') }}</th>
                        <th class="px-4 py-2.5 text-left font-medium">{{ $t('Code') }}</th>
                        <th class="px-4 py-2.5 text-right font-medium"><span class="sr-only">{{ $t('Status') }}</span></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="shown.length === 0">
                        <td colspan="4" class="px-4 py-8 text-center text-text-subtle">
                            {{ tickets.length === 0 ? $t('No tickets sold yet.') : $t('No results') }}
                        </td>
                    </tr>
                    <tr v-for="ticket in shown" :key="ticket.id" class="border-t border-border-hairline"
                        :class="ticket.checkedInAt && 'bg-success-surface/40'">
                        <td class="px-4 py-2.5">
                            <span class="block font-medium text-text">{{ ticket.holderName || '–' }}</span>
                            <span class="block text-xs text-text-subtle">{{ ticket.email || '–' }}</span>
                        </td>
                        <td class="px-4 py-2.5">
                            {{ ticket.categoryName }}
                            <span v-if="ticket.reductionName" class="text-text-subtle">· {{ ticket.reductionName }}</span>
                            <BaseChip v-if="ticket.requiresProof" variant="warning" class="ml-1.5">{{ $t('Check proof') }}</BaseChip>
                            <span v-if="seatOf(ticket)" class="block text-xs text-text-subtle">{{ seatOf(ticket) }}</span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums text-text-muted">{{ ticket.code }}</td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-right">
                            <span v-if="ticket.status !== 'valid'">
                                <BaseChip variant="warning">{{ ticket.status === 'refunded' ? $t('Refunded') : $t('Cancelled') }}</BaseChip>
                            </span>
                            <span v-else-if="ticket.checkedInAt" class="inline-flex items-center gap-2">
                                <BaseChip variant="success">{{ $t('Checked in {time}', { time: formatTime(ticket.checkedInAt) }) }}</BaseChip>
                                <button v-if="mayAdmit" type="button" class="text-xs text-text-subtle underline-offset-2 hover:text-text hover:underline"
                                        :disabled="busyId === ticket.id" @click="revert(ticket)">
                                    {{ $t('Undo') }}
                                </button>
                            </span>
                            <button v-else-if="mayAdmit" type="button" class="ui-button-small" :disabled="busyId === ticket.id" @click="checkIn(ticket)">
                                <IconLoader2 v-if="busyId === ticket.id" class="size-3.5 animate-spin" />
                                <IconCheck v-else class="size-3.5" />{{ $t('Check in') }}
                            </button>
                            <BaseChip v-else variant="neutral">{{ $t('Valid') }}</BaseChip>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import { useI18n } from 'vue-i18n'
import { IconCheck, IconLoader2, IconSearch } from '@tabler/icons-vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import ChoiceSegments from '@/Pages/Settings/Tickets/ChoiceSegments.vue'
import { showAppToast } from '@/Helper/appToast'
import { formatTime } from '@/Helper/ticketingFormat.js'

/* The guest list of one date: searchable, and — with the admission permission — the door itself.
   Check-ins go to Artwork-Tickets at once; the row changes when tickets confirms. */
const props = defineProps({
    eventId: { type: Number, required: true },
    tickets: { type: Array, required: true },
    mayAdmit: { type: Boolean, default: false },
})

const emit = defineEmits(['update'])

const { t } = useI18n()

const search = ref('')
const filter = ref('all')
const busyId = ref(null)

const valid = computed(() => props.tickets.filter((ticket) => ticket.status === 'valid'))
const filterOptions = computed(() => [
    { value: 'all', label: t('All ({count})', { count: props.tickets.length }) },
    { value: 'open', label: t('Not in yet ({count})', { count: valid.value.filter((ticket) => !ticket.checkedInAt).length }) },
    { value: 'in', label: t('Checked in ({count})', { count: valid.value.filter((ticket) => ticket.checkedInAt).length }) },
])

const shown = computed(() => {
    const needle = search.value.trim().toLocaleLowerCase()
    const compactNeedle = needle.replace(/\s+/g, '')

    return props.tickets.filter((ticket) => {
        if (filter.value === 'open' && (ticket.status !== 'valid' || ticket.checkedInAt)) return false
        if (filter.value === 'in' && !ticket.checkedInAt) return false
        if (needle === '') return true

        return [ticket.holderName, ticket.email].some((value) => value?.toLocaleLowerCase().includes(needle))
            || ticket.code.toLocaleLowerCase().includes(compactNeedle)
    })
})

/** Why tickets refused a check-in, in words. */
const refusals = {
    wrong_event_date: t('This ticket is for another date.'),
    cancelled: t('This ticket was cancelled.'),
    refunded: t('This ticket was refunded.'),
    unknown: t('This ticket is unknown.'),
}

function seatOf(ticket) {
    const { section, row, number } = ticket.seat ?? {}
    if (!number) return ''
    return [section, row && t('Row {row}', { row }), t('Seat {number}', { number })].filter(Boolean).join(' · ')
}

async function checkIn(ticket) {
    busyId.value = ticket.id
    try {
        const { data } = await axios.post(route('ticketing.door.check-in', props.eventId), { ticket_id: ticket.id }, { skipErrorToast: true })
        if (data.outcome === 'accepted' || data.outcome === 'already_checked_in') {
            emit('update', { id: ticket.id, checkedInAt: data.ticket?.checkedInAt ?? null })
        } else {
            showAppToast('error', refusals[data.outcome] ?? t('This ticket cannot be checked in.'))
        }
    } catch (error) {
        showAppToast('error', error?.response?.data?.message || t('The check-in failed.'))
    } finally {
        busyId.value = null
    }
}

async function revert(ticket) {
    busyId.value = ticket.id
    try {
        await axios.post(route('ticketing.door.revert', props.eventId), { ticket_id: ticket.id }, { skipErrorToast: true })
        emit('update', { id: ticket.id, checkedInAt: null })
    } catch (error) {
        showAppToast('error', error?.response?.data?.message || t('The check-in could not be undone.'))
    } finally {
        busyId.value = null
    }
}
</script>
