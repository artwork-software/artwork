<template>
    <div class="space-y-4">
        <p v-if="connection.connected" class="text-sm text-text-subtle">
            {{ $t('artwork tickets sells the tickets for your events. Once connected, artwork and tickets exchange released events, prices and sales.') }}
        </p>

        <div v-if="!connection.configured" class="text-sm text-text-subtle">
            {{ $t('artwork tickets is not configured for this installation. Ask your Caldero contact to set it up.') }}
        </div>

        <ConnectStart v-else-if="!connection.connected && !wizardOpen" :user-email="userEmail" @start="wizardOpen = true" />

        <ConnectWizard v-else-if="!connection.connected"
                       :rooms="rooms" :house-defaults="houseDefaults" :countries="countries" :user-email="userEmail" :tickets-url="connection.url"
                       @cancel="wizardOpen = false" />

        <div v-else class="space-y-3">
            <div class="flex items-center gap-2 text-sm">
                <IconCircleCheck class="h-5 w-5 text-success" />
                <span>{{ $t('Connected with house {slug}', { slug: connection.organizationSlug }) }}</span>
            </div>
            <p class="text-xs text-text-subtle">
                {{ $t('Connected on {date} by {name}', { date: formatDate(connection.connectedAt), name: connection.connectedBy ?? '–' }) }}
            </p>
            <div class="flex items-center gap-4">
                <a :href="connection.dashboardUrl" target="_blank" rel="noopener" class="text-accent-700 hover:underline text-sm">
                    {{ $t('Open artwork tickets') }}
                </a>
                <button type="button" @click="confirmDisconnect = true" class="text-error hover:underline text-sm">
                    {{ $t('Disconnect') }}
                </button>
            </div>
        </div>

        <confirmation-component
            v-if="confirmDisconnect"
            :titel="$t('Disconnect artwork tickets')"
            :description="$t('The access of this installation is revoked; the ticket house and its data stay in artwork tickets. Continue?')"
            :confirm="$t('Disconnect')"
            @closed="handleDisconnect"
        />
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import { IconCircleCheck } from '@tabler/icons-vue'
import dayjs from 'dayjs'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import ConnectStart from '@/Pages/Settings/Tickets/ConnectStart.vue'
import ConnectWizard from '@/Pages/Settings/Tickets/ConnectWizard.vue'

defineProps({
    connection: { type: Object, required: true },
    rooms: { type: Array, default: () => [] },
    houseDefaults: { type: Object, required: true },
    countries: { type: Array, required: true },
})

const userEmail = usePage().props.auth.user.email
const confirmDisconnect = ref(false)
const wizardOpen = ref(false)

function handleDisconnect(confirmed) {
    confirmDisconnect.value = false
    if (confirmed) {
        router.delete(route('settings.tickets.disconnect'), { preserveScroll: true })
    }
}

function formatDate(value) {
    return value ? dayjs(value).format('DD.MM.YYYY HH:mm') : '–'
}
</script>
