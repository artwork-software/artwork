<template>
    <TicketsSettingsHeader :description="$t('Who from this artwork works in the ticket house.')">
        <PendingChangesBar :visible="connection.connected && picked.size > 0"
                           :message="$t('{count} people picked', { count: picked.size })"
                           :submit-label="$t('Invite {count} people…', { count: picked.size })"
                           :icon="IconMailForward" :processing-label="$t('Inviting…')" :discard-label="$t('Clear')"
                           :can-submit="picked.size > 0 && !form.processing" :processing="form.processing" @discard="clear" @submit="inviting = true" />

        <TeamInviteModal v-if="inviting" v-model="form.preset" :people="pickedPeople" :options="roleOptions" :processing="form.processing"
                         @close="inviting = false" @submit="submit" />

        <NotConnected v-if="!connection.connected" :message="$t('People can be invited once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('The team could not be loaded from Artwork-Tickets: {message}', { message: ticketsError }) }}</span>
            </div>

            <div class="mb-5 flex flex-wrap items-end justify-between gap-x-8 gap-y-4">
                <p class="max-w-[560px] text-[13px] leading-5 text-text-muted">
                    <span class="block font-medium text-text">{{ $t('{members} of {total} people are in the ticket house, {invited} invited.', { members: counts.member, total: people.length, invited: counts.invited }) }}</span>
                    {{ $t('Pick the others; Artwork-Tickets sends each an e-mail with a link and you choose their role before it goes out.') }}
                </p>
            </div>

            <div class="mb-1 flex items-center justify-between gap-3 border-b border-border-hairline pb-3">
                <div class="relative max-w-xs flex-1">
                    <IconSearch class="pointer-events-none absolute left-2.5 top-1/2 size-4 -translate-y-1/2 text-text-subtle" />
                    <input v-model="search" type="search" :placeholder="$t('Name or e-mail')" class="h-9 w-full rounded-md border border-border bg-surface pl-8 pr-3 text-sm text-text focus:ring-0" />
                </div>
                <span class="text-xs text-text-subtle tabular-nums">{{ $t('{count} shown', { count: shown.length }) }}</span>
            </div>

            <table class="w-full border-collapse text-[13px]">
                <thead>
                    <tr class="text-left text-xs text-text-subtle">
                        <th class="w-9 py-2.5 pl-1 align-middle">
                            <BaseCheckbox id="tickets-team-all" :model-value="allShownState" :disabled="shownPickable.length === 0" @update:model-value="toggleAll" />
                        </th>
                        <th class="py-2.5 font-medium">{{ $t('Person') }}</th>
                        <th class="py-2.5 font-medium">{{ $t('In Artwork-Tickets') }}</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="person in shown" :key="person.id" class="border-t border-border-hairline transition-colors" :class="picked.has(person.id) ? 'bg-accent-50/60' : 'hover:bg-surface-sunken/60'">
                        <td class="py-2.5 pl-1 align-middle">
                            <BaseCheckbox v-if="person.status === null" :id="`tickets-team-${person.id}`" :model-value="picked.has(person.id)" @update:model-value="(on) => pick(person.id, on)" />
                        </td>
                        <td class="py-2.5">
                            <label :for="person.status === null ? `tickets-team-${person.id}` : undefined" class="block" :class="person.status === null ? 'cursor-pointer' : ''">
                                <span class="block font-medium text-text">{{ person.name }}</span>
                                <span class="block text-xs text-text-subtle">{{ person.email }}</span>
                            </label>
                        </td>
                        <td class="py-2.5">
                            <BaseChip v-if="person.status === 'member'" variant="success">{{ presetLabel(person.preset) }}</BaseChip>
                            <BaseChip v-else-if="person.status === 'invited'" variant="warning">{{ $t('Invited') }} · {{ presetLabel(person.preset) }}</BaseChip>
                            <span v-else class="text-xs text-text-subtle">{{ $t('Not yet') }}</span>
                        </td>
                    </tr>
                    <tr v-if="shown.length === 0">
                        <td colspan="3" class="py-8 text-center text-text-subtle">{{ $t('Nobody matches.') }}</td>
                    </tr>
                </tbody>
            </table>

            <ul v-if="Object.keys(form.errors).length" class="mt-4 rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
            </ul>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { IconAlertTriangle, IconMailForward, IconSearch } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import PendingChangesBar from '@/Pages/Settings/Tickets/PendingChangesBar.vue'
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import TeamInviteModal from '@/Pages/Settings/Tickets/TeamInviteModal.vue'

const props = defineProps({
    connection: { type: Object, required: true },
    people: { type: Array, default: () => [] },
    presets: { type: Array, required: true },
    ticketsError: { type: String, default: null },
})

const { t } = useI18n()

/* The same words as the team page of Artwork-Tickets, so a role means one thing in both. */
const presetNames = { owner: t('Owner'), admin: t('Admin'), staff: t('Staff'), door: t('Door'), custom: t('Custom rights') }
const presetHints = {
    admin: t('Everything in operations and settings, except payouts and the bank account.'),
    staff: t('Create and edit events, read the figures, look after customers and admit.'),
    door: t('Admit and stamp passes, nothing else.'),
}
const presetLabel = (preset) => presetNames[preset ?? 'custom'] ?? preset

const form = useForm({ user_ids: [], preset: 'staff' })
const roleOptions = computed(() => props.presets.map((preset) => ({ preset, label: presetLabel(preset), hint: presetHints[preset] ?? '' })))
const inviting = ref(false)

const counts = computed(() => ({
    member: props.people.filter((p) => p.status === 'member').length,
    invited: props.people.filter((p) => p.status === 'invited').length,
}))

const search = ref('')
const picked = ref(new Set())

const shown = computed(() => {
    const term = search.value.trim().toLowerCase()
    return term === '' ? props.people : props.people.filter((p) => `${p.name} ${p.email}`.toLowerCase().includes(term))
})
const shownPickable = computed(() => shown.value.filter((p) => p.status === null))
/** The header box: checked, indeterminate or empty for the rows on screen. */
const allShownState = computed(() => {
    const pickedShown = shownPickable.value.filter((p) => picked.value.has(p.id)).length
    return pickedShown === 0 ? false : pickedShown === shownPickable.value.length ? true : 'indeterminate'
})

function setPicked(next) {
    picked.value = next
    form.user_ids = [...next]
}

function pick(id, on) {
    const next = new Set(picked.value)
    if (on) next.add(id)
    else next.delete(id)
    setPicked(next)
}

function toggleAll(on) {
    const next = new Set(picked.value)
    for (const p of shownPickable.value) {
        if (on === true) next.add(p.id)
        else next.delete(p.id)
    }
    setPicked(next)
}

const pickedPeople = computed(() => props.people.filter((p) => picked.value.has(p.id)))

function clear() {
    setPicked(new Set())
}

function submit() {
    form.post(route('settings.tickets.team.invite'), {
        preserveScroll: true,
        onSuccess: () => {
            inviting.value = false
            clear()
        },
    })
}
</script>
