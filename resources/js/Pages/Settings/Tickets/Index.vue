<template>
    <TicketsSettingsHeader :description="$t('Connect this artwork to its ticket house and manage everything ticketing.')">
        <template #actions>
            <a v-if="connection.connected" :href="route('ticketing.open', { to: 'settings', tab: 'appearance' })" target="_blank" rel="noopener" class="ui-button">
                <IconPalette class="size-3.5" />{{ $t('Edit shop appearance') }}
            </a>
            <a v-if="connection.connected" :href="route('ticketing.open')" target="_blank" rel="noopener" class="ui-button">
                <IconExternalLink class="size-3.5" />{{ $t('Open Artwork-Tickets') }}
            </a>
        </template>

        <SettingsGuideBanner
            storage-key="settings-guide.tickets"
            title="How does this area work?"
            :paragraphs="[
                'Artwork-Tickets is the ticket shop of the suite. Connecting creates a ticket house for this installation; the person connecting becomes its owner.',
                'Once connected, artwork and tickets exchange released events, prices and sales. Rooms with their price classes and the reductions are synced from the other tabs.',
            ]"
        />

        <div class="mt-6 rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <TicketsConnection :connection="connection" :rooms="rooms" :linked-rooms="linkedRooms" :house-defaults="houseDefaults" :countries="countries" :legal-forms="legalForms"/>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { IconExternalLink, IconPalette } from '@tabler/icons-vue'
import SettingsGuideBanner from '@/Artwork/Guide/SettingsGuideBanner.vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import TicketsConnection from '@/Pages/Settings/Tickets/TicketsConnection.vue'

defineProps({
    connection: { type: Object, required: true },
    rooms: { type: Array, default: () => [] },
    /** How many of them already are a venue in Artwork-Tickets. */
    linkedRooms: { type: Number, default: 0 },
    houseDefaults: { type: Object, required: true },
    countries: { type: Array, required: true },
    legalForms: { type: Array, required: true },
})
</script>
