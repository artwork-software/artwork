<template>
    <ArtworkBaseModal :title="$t('Ticket details')" :description="description" modal-size="sm:max-w-4xl" @close="$emit('close')">
        <div v-if="loading" class="flex items-center gap-2 text-sm text-text-subtle">
            <IconLoader2 class="size-4 animate-spin" />{{ $t('Loading…') }}
        </div>
        <p v-else-if="error" class="text-sm text-danger">{{ error }}</p>
        <p v-else-if="!sales.released" class="text-sm text-text-subtle">{{ $t('This date is not released for sale.') }}</p>
        <div v-else class="flex flex-col gap-5 text-[13px]">
            <dl class="grid grid-cols-2 gap-3 sm:grid-cols-5">
                <div v-for="stat in stats" :key="stat.label" class="rounded-lg border border-border-subtle bg-surface-sunken px-3.5 py-3">
                    <dt class="font-lexend text-xs text-text-subtle">{{ stat.label }}</dt>
                    <dd class="mt-0.5 text-[15px] font-semibold tabular-nums text-text">{{ stat.value }}</dd>
                </div>
            </dl>

            <BaseChip v-if="sales.status === 'cancelled'" variant="warning" class="self-start">{{ $t('Cancelled in Artwork-Tickets') }}</BaseChip>

            <div class="overflow-x-auto rounded-lg border border-border-subtle bg-surface">
                <table class="w-full min-w-[640px] border-collapse">
                    <thead>
                        <tr class="font-lexend text-xs font-medium text-text-subtle">
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Name') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('E-mail') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Price class') }}</th>
                            <th class="px-4 py-2.5 text-right font-medium">{{ $t('Price') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Code') }}</th>
                            <th class="px-4 py-2.5 text-left font-medium">{{ $t('Status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="sales.tickets.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-text-subtle">{{ $t('No tickets sold yet.') }}</td>
                        </tr>
                        <tr v-for="ticket in sales.tickets" :key="ticket.code" class="border-t border-border-hairline">
                            <td class="px-4 py-2.5">{{ ticket.holderName || '–' }}</td>
                            <td class="px-4 py-2.5 text-text-subtle">{{ ticket.email || '–' }}</td>
                            <td class="px-4 py-2.5">
                                {{ ticket.categoryName }}
                                <span v-if="ticket.reductionName" class="text-text-subtle">· {{ ticket.reductionName }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2.5 text-right tabular-nums">{{ formatEuro(ticket.priceCents) }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5 font-mono text-xs tabular-nums">{{ ticket.code }}</td>
                            <td class="whitespace-nowrap px-4 py-2.5">
                                <BaseChip v-if="ticket.checkedInAt" variant="success">{{ $t('Checked in {time}', { time: formatTime(ticket.checkedInAt) }) }}</BaseChip>
                                <BaseChip v-else-if="ticket.status === 'valid'" variant="neutral">{{ $t('Valid') }}</BaseChip>
                                <BaseChip v-else variant="warning">{{ ticket.status === 'refunded' ? $t('Refunded') : $t('Cancelled') }}</BaseChip>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <template #footer>
            <a v-if="sales?.released" :href="route('ticketing.open', { event: eventId })" target="_blank" rel="noopener" class="ui-button">
                <IconExternalLink class="size-3.5" />{{ $t('Open in Artwork-Tickets') }}
            </a>
            <button type="button" class="ui-button-add" @click="$emit('close')">{{ $t('Close') }}</button>
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import axios from 'axios'
import dayjs from 'dayjs'
import { useI18n } from 'vue-i18n'
import { IconExternalLink, IconLoader2 } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import { formatEuro } from '@/Pages/Projects/Tab/Components/Ticketing/ticketing.js'

/* Sales and guest list of one date, read from tickets when opened — never cached, so
   the numbers are those of the moment. Used from the project component and the calendar. */
const props = defineProps({
    eventId: { type: Number, required: true },
    description: { type: String, default: '' },
})

defineEmits(['close'])

const { t } = useI18n()

const sales = ref(null)
const loading = ref(true)
const error = ref('')

const stats = computed(() => [
    { label: t('Sold'), value: sales.value.sold },
    { label: t('Places'), value: sales.value.capacity },
    { label: t('Free'), value: Math.max(0, sales.value.capacity - sales.value.sold) },
    { label: t('Checked in'), value: sales.value.checkedInCount },
    { label: t('Revenue'), value: formatEuro(sales.value.revenueCents) },
])

function formatTime(value) {
    return dayjs(value).format('DD.MM. HH:mm')
}

onMounted(async () => {
    try {
        const { data } = await axios.get(route('ticketing.sales', props.eventId))
        sales.value = data
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || t('The ticketing data could not be loaded.')
    } finally {
        loading.value = false
    }
})
</script>
