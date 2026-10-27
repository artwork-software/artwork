<template>
    <AppLayout :title="$t('Artwork-Tickets')">
        <div class="artwork-container">
            <ToolbarHeader
                :icon="IconTicket"
                :title="$t('Artwork-Tickets')"
                icon-bg-class="bg-accent-50 text-accent-700"
                :description="description"
                :search-enabled="false"
            >
                <template #actions>
                    <slot name="actions"></slot>
                    <a v-if="connected" :href="route('ticketing.open')" target="_blank" rel="noopener" class="ui-button">
                        <IconExternalLink class="size-3.5" />{{ $t('Open in Artwork-Tickets') }}
                    </a>
                </template>
            </ToolbarHeader>

            <BaseTabs :tabs="tabs" :use-translation="false" />

            <div class="mt-6">
                <NotConnected v-if="!connected" :message="$t('Sales appear here once this installation is connected to Artwork-Tickets.')" />
                <div v-else-if="ticketsError" class="flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                    <IconAlertTriangle class="mt-0.5 size-4 shrink-0 text-warning" />
                    <span>{{ $t('Artwork-Tickets could not be reached: {message}', { message: ticketsError }) }}</span>
                </div>
                <slot v-else></slot>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { IconAlertTriangle, IconExternalLink, IconTicket } from '@tabler/icons-vue'
import { useI18n } from 'vue-i18n'
import AppLayout from '@/Layouts/AppLayout.vue'
import ToolbarHeader from '@/Artwork/Toolbar/ToolbarHeader.vue'
import BaseTabs from '@/Artwork/Tabs/BaseTabs.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'

/* Main menu → Artwork-Tickets: what the ticket house sells, without switching to its dashboard. */
defineProps({
    description: { type: String, required: true },
    connected: { type: Boolean, required: true },
    ticketsError: { type: String, default: null },
})

const { t } = useI18n()

const tabs = [
    { name: t('Sales'), href: route('ticketing.section.sales'), current: route().current('ticketing.section.sales'), permission: true },
    { name: t('Passes & subscriptions'), href: route('ticketing.section.passes'), current: route().current('ticketing.section.passes'), permission: true },
]
</script>
