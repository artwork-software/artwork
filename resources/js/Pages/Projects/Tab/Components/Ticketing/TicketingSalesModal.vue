<template>
    <ArtworkBaseModal :title="$t('Ticket details')" :description="description" modal-size="sm:max-w-5xl" @close="$emit('close')">
        <div v-if="loading" class="flex items-center gap-2 text-sm text-text-subtle">
            <IconLoader2 class="size-4 animate-spin" />{{ $t('Loading…') }}
        </div>
        <p v-else-if="error" class="text-sm text-danger">{{ error }}</p>
        <p v-else-if="!sales.released" class="text-sm text-text-subtle">{{ $t('This date is not released for sale.') }}</p>

        <!-- Kopf (Zahlen, Reiter) steht, nur die Liste darunter scrollt -->
        <div v-else class="flex flex-col gap-4 text-[13px]" :class="!sales.guests_hidden && 'h-[min(68vh,720px)]'">
            <div class="flex shrink-0 flex-col gap-3">
                <dl class="grid grid-cols-2 gap-2 sm:grid-cols-5">
                    <div v-for="stat in stats" :key="stat.label" class="rounded-lg border border-border-subtle bg-surface-sunken px-3 py-2">
                        <dt class="font-lexend text-[11px] text-text-subtle">{{ stat.label }}</dt>
                        <dd class="text-[15px] font-semibold tabular-nums text-text">{{ stat.value }}</dd>
                    </div>
                </dl>

                <BaseChip v-if="sales.status === 'cancelled'" variant="warning" class="self-start">{{ $t('Cancelled in Artwork-Tickets') }}</BaseChip>

                <BaseTabs v-if="tabs.length > 1" :tabs="tabs" navigation-mode="events" :use-translation="false" class="!mt-0" @tab-select="(tab) => (tabKey = tab.key)" />
            </div>

            <p v-if="sales.guests_hidden" class="text-sm text-text-subtle">
                {{ $t('The guest list and revenue are only visible to people who can see the ticketing component.') }}
            </p>
            <TicketingGuestList v-else-if="tabKey === 'tickets'" :event-id="eventId" :tickets="sales.tickets" :may-admit="sales.may_admit" @update="applyCheckIn" />
            <TicketingPassAdmission v-else :event-id="eventId" @admitted="reload" />
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
import { useI18n } from 'vue-i18n'
import { IconExternalLink, IconLoader2 } from '@tabler/icons-vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'
import TicketingGuestList from '@/Pages/Projects/Tab/Components/Ticketing/TicketingGuestList.vue'
import TicketingPassAdmission from '@/Pages/Projects/Tab/Components/Ticketing/TicketingPassAdmission.vue'
import { formatEuro } from '@/Helper/ticketingFormat.js'

/* Sales, guest list and door of one date, read from tickets when opened — never cached, so the numbers
   are those of the moment. Used from the project component, the calendar and the event dialog. */
const props = defineProps({
    eventId: { type: Number, required: true },
    description: { type: String, default: '' },
})

defineEmits(['close'])

const { t } = useI18n()

const sales = ref(null)
const loading = ref(true)
const error = ref('')
const tabKey = ref('tickets')

const stats = computed(() => [
    { label: t('Sold'), value: sales.value.sold },
    { label: t('Places'), value: sales.value.capacity },
    { label: t('Free'), value: Math.max(0, sales.value.capacity - sales.value.sold) },
    { label: t('Checked in'), value: `${sales.value.checkedInCount} / ${sales.value.ticketCount}` },
    // Umsatz liefert das Backend nur mit Sicht auf die Ticketing-Komponente
    ...(sales.value.guests_hidden ? [] : [{ label: t('Revenue'), value: formatEuro(sales.value.revenueCents) }]),
])

const tabs = computed(() => [
    { key: 'tickets', name: t('Tickets'), count: sales.value.tickets?.length, current: tabKey.value === 'tickets', permission: true },
    { key: 'passes', name: t('Passes & subscriptions'), current: tabKey.value === 'passes', permission: sales.value.may_admit },
].filter((tab) => tab.permission))

/** A check-in or its undo, as tickets confirmed it. */
function applyCheckIn({ id, checkedInAt }) {
    const ticket = sales.value.tickets.find((candidate) => candidate.id === id)
    if (!ticket) return
    sales.value.checkedInCount += Number(Boolean(checkedInAt)) - Number(Boolean(ticket.checkedInAt))
    ticket.checkedInAt = checkedInAt
}

async function reload() {
    try {
        const { data } = await axios.get(route('ticketing.sales', props.eventId))
        sales.value = data
        error.value = ''
    } catch (requestError) {
        error.value = requestError?.response?.data?.message || t('The ticketing data could not be loaded.')
    } finally {
        loading.value = false
    }
}

onMounted(reload)
</script>
