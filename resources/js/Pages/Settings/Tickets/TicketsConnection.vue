<template>
    <div>
        <div v-if="!connection.configured" class="text-sm text-text-subtle">
            {{ $t('artwork tickets is not configured for this installation. Ask your Caldero contact to set it up.') }}
        </div>

        <ConnectStart v-else-if="!connection.connected && !wizardOpen" :user-email="userEmail" @start="wizardOpen = true" />

        <ConnectWizard v-else-if="!connection.connected"
                       :rooms="rooms" :house-defaults="houseDefaults" :countries="countries" :legal-forms="legalForms" :user-email="userEmail" :tickets-url="connection.url"
                       @cancel="wizardOpen = false" />

        <!-- Connected: the house on the left, the state of each tab on the right. -->
        <div v-else>
            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_400px] items-start">
                <div>
                    <BaseChip variant="success" class="mb-3.5">
                        <span class="size-1.5 rounded-full bg-success"></span>
                        {{ $t('Connected') }}
                    </BaseChip>
                    <h2 class="font-lexend text-xl font-semibold text-text mb-1">{{ connection.organizationSlug }}</h2>
                    <a :href="`${connection.url}/${connection.organizationSlug}`" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 font-mono text-xs text-text-subtle hover:text-accent-700">
                        {{ shopHost }}/{{ connection.organizationSlug }}<IconExternalLink class="size-3" />
                    </a>
                    <dl class="mt-6 grid grid-cols-[120px_minmax(0,1fr)] gap-x-3 gap-y-2 text-[13px] max-w-[520px]">
                        <dt class="text-text-subtle">{{ $t('Connected on') }}</dt><dd class="text-text">{{ formatDate(connection.connectedAt) }}</dd>
                        <dt class="text-text-subtle">{{ $t('Connected by') }}</dt><dd class="text-text">{{ connection.connectedBy ?? '–' }}</dd>
                    </dl>
                    <p class="mt-6 max-w-[520px] text-[13px] leading-5 text-text-muted">
                        {{ $t('Dates are released for sale from the ticketing tab of a project. Sales and payouts live in artwork tickets.') }}
                    </p>
                </div>

                <div class="rounded-lg bg-surface-sunken border border-border-subtle px-5 py-4">
                    <span class="font-lexend block text-[13px] font-semibold text-text mb-1">{{ $t('Set-up') }}</span>
                    <ul class="divide-y divide-border-hairline">
                        <li v-for="row in setup" :key="row.href">
                            <Link :href="row.href" class="group -mx-2 flex items-center gap-3 rounded-md px-2 py-3 hover:bg-surface">
                                <span class="min-w-0 flex-1">
                                    <span class="font-lexend block text-[13px] font-medium text-text">{{ row.title }}</span>
                                    <span class="block text-xs leading-[18px] text-text-subtle">{{ row.text }}</span>
                                </span>
                                <BaseChip v-if="row.state" :variant="row.state.variant" class="shrink-0">{{ row.state.label }}</BaseChip>
                                <IconChevronRight class="size-4 shrink-0 text-text-subtle group-hover:text-text" />
                            </Link>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 flex flex-wrap items-center justify-between gap-3 border-t border-border-hairline pt-4">
                <p class="text-xs text-text-subtle max-w-[560px]">{{ $t('Disconnecting revokes the access of this installation; the ticket house and its data stay in artwork tickets.') }}</p>
                <button type="button" class="ui-button text-danger hover:bg-danger-surface" @click="confirmDisconnect = true">
                    <IconPlugConnectedX class="size-4" />{{ $t('Disconnect') }}
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
import { computed, ref } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconChevronRight, IconExternalLink, IconPlugConnectedX } from '@tabler/icons-vue'
import dayjs from 'dayjs'
import ConfirmationComponent from '@/Layouts/Components/ConfirmationComponent.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import ConnectStart from '@/Pages/Settings/Tickets/ConnectStart.vue'
import ConnectWizard from '@/Pages/Settings/Tickets/ConnectWizard.vue'

const props = defineProps({
    connection: { type: Object, required: true },
    rooms: { type: Array, default: () => [] },
    linkedRooms: { type: Number, default: 0 },
    houseDefaults: { type: Object, required: true },
    countries: { type: Array, required: true },
    legalForms: { type: Array, default: () => [] },
})

const { t } = useI18n()

const userEmail = usePage().props.auth.user.email
const confirmDisconnect = ref(false)
const wizardOpen = ref(false)

const shopHost = computed(() => String(props.connection.url ?? '').replace(/^https?:\/\//, '').replace(/\/$/, ''))

/* One row per tab; the ones with a known state carry it, the rest say what they are for. */
const setup = computed(() => [
    {
        href: route('settings.tickets.billing'),
        title: t('Details & bank account'),
        text: props.connection.billingComplete === false
            ? t('Without them no date can be released for sale and nothing is paid out.')
            : t('Who stands behind the house and where it is paid out.'),
        state: props.connection.billingComplete === null ? null
            : props.connection.billingComplete ? { variant: 'success', label: t('Complete') } : { variant: 'warning', label: t('Missing') },
    },
    {
        href: route('settings.tickets.rooms'),
        title: t('Rooms & price classes'),
        text: t('{linked} of {total} rooms sell tickets.', { linked: props.linkedRooms, total: props.rooms.length }),
        state: props.linkedRooms === 0 ? { variant: 'warning', label: t('None yet') } : null,
    },
    {
        href: route('settings.tickets.reductions'),
        title: t('Reductions'),
        text: t('House-wide; which ones apply is decided per production.'),
        state: null,
    },
    {
        href: route('settings.tickets.team'),
        title: t('Team'),
        text: t('Invite people from this artwork into the ticket house.'),
        state: null,
    },
])

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
