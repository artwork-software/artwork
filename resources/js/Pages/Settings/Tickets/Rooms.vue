<template>
    <TicketsSettingsHeader :description="$t('Which rooms sell tickets, with their address and price classes.')">
        <template #actions>
            <button v-if="connection.connected" type="button" class="ui-button-add relative" :disabled="!canSync" @click="submit">
                <IconRefresh class="size-4" />{{ form.processing ? $t('Syncing…') : $t('Sync rooms') }}
                <span v-if="dirty" class="absolute -top-1 -right-1 size-2.5 rounded-full bg-warning ring-2 ring-surface"></span>
            </button>
        </template>

        <PendingChangesBar :visible="connection.connected && dirty" :message="$t('Changes not yet in Artwork-Tickets')" :submit-label="$t('Sync rooms')"
                           :can-submit="canSync" :processing="form.processing" @discard="discard" @submit="submit" />

        <NotConnected v-if="!connection.connected" :message="$t('Rooms and price classes are synced once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <p class="mb-4 max-w-[760px] text-[13px] leading-5 text-text-subtle">
                {{ $t('Each selected room is a venue in Artwork-Tickets. The address is printed on the tickets and starts out as the house address from the general settings. Rooms already in Artwork-Tickets show their current state; a deselected room stays there untouched.') }}
            </p>

            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('The current state could not be loaded from Artwork-Tickets: {message} Syncing overwrites what is there with what you enter here.', { message: ticketsError }) }}</span>
            </div>

            <RoomSyncEditor :rooms="roomDrafts" :countries="countries" />

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
import { IconAlertTriangle, IconRefresh } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import RoomSyncEditor from '@/Pages/Settings/Tickets/RoomSyncEditor.vue'
import PendingChangesBar from '@/Pages/Settings/Tickets/PendingChangesBar.vue'
import { usePendingChanges } from '@/Pages/Settings/Tickets/usePendingChanges.js'
import { roomDraft, roomPayload, roomsValid } from '@/Pages/Settings/Tickets/drafts.js'

const props = defineProps({
    connection: { type: Object, required: true },
    rooms: { type: Array, default: () => [] },
    houseDefaults: { type: Object, required: true },
    countries: { type: Array, required: true },
    ticketsError: { type: String, default: null },
})

const { t } = useI18n()

const buildDrafts = () => props.rooms.map((room) => roomDraft(room, props.houseDefaults.address, t('Free seating')))
const roomDrafts = ref(buildDrafts())
const { dirty, markClean } = usePendingChanges(roomDrafts)
const selectedRooms = computed(() => roomDrafts.value.filter((room) => room.selected))

const form = useForm({ rooms: [] })

const canSync = computed(() => !form.processing && dirty.value && selectedRooms.value.length > 0 && roomsValid(selectedRooms.value))

function discard() {
    roomDrafts.value = buildDrafts()
    markClean()
}

function submit() {
    form.rooms = selectedRooms.value.map(roomPayload)
    // After a sync the page carries what tickets holds now; the drafts start over from that.
    form.post(route('settings.tickets.rooms.sync'), {
        preserveScroll: true,
        onSuccess: discard,
    })
}
</script>
