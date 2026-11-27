<template>
    <TicketsSettingsHeader :description="$t('House-wide reductions; which ones a production grants is decided per production.')">
        <template #actions>
            <button v-if="connection.connected" type="button" class="ui-button-add relative" :disabled="!canSync" @click="submit">
                <IconDeviceFloppy class="size-4" />{{ form.processing ? $t('Saving…') : $t('Save') }}
                <span v-if="dirty" class="absolute -top-1 -right-1 size-2.5 rounded-full bg-warning ring-2 ring-surface"></span>
            </button>
        </template>

        <PendingChangesBar :visible="connection.connected && dirty" :message="$t('Changes not yet in Artwork-Tickets')" :submit-label="$t('Save')"
                           :can-submit="canSync" :processing="form.processing" @discard="discard" @submit="submit" />

        <NotConnected v-if="!connection.connected" :message="$t('Reductions are synced once this installation is connected to Artwork-Tickets.')" />

        <div v-else class="rounded-lg border border-border-subtle/70 bg-surface shadow-raised px-3 py-4 sm:px-5 sm:py-5">
            <div v-if="ticketsError" class="mb-4 flex items-start gap-2.5 rounded-md border border-warning-border bg-warning-surface px-3.5 py-3 text-[13px] leading-5 text-text">
                <IconAlertTriangle class="size-4 shrink-0 mt-0.5 text-warning" />
                <span>{{ $t('The reductions could not be loaded from Artwork-Tickets: {message} Saving is possible again once they load.', { message: ticketsError }) }}</span>
            </div>

            <ReductionsEditor :reductions="reductionDrafts">
                <template #hint>{{ $t('Removing a reduction deletes it in Artwork-Tickets too. One that was already sold disappears everywhere but stays on its orders.') }}</template>
            </ReductionsEditor>

            <ul v-if="Object.keys(form.errors).length" class="mt-4 rounded-md border border-danger-border bg-danger-surface p-3 text-[13px] text-danger">
                <li v-for="(message, key) in form.errors" :key="key">{{ message }}</li>
            </ul>
        </div>
    </TicketsSettingsHeader>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { IconAlertTriangle, IconDeviceFloppy } from '@tabler/icons-vue'
import TicketsSettingsHeader from '@/Pages/Settings/Tickets/TicketsSettingsHeader.vue'
import NotConnected from '@/Pages/Settings/Tickets/NotConnected.vue'
import ReductionsEditor from '@/Pages/Settings/Tickets/ReductionsEditor.vue'
import PendingChangesBar from '@/Pages/Settings/Tickets/PendingChangesBar.vue'
import { usePendingChanges } from '@/Pages/Settings/Tickets/usePendingChanges.js'
import { reductionDraft, reductionPayload, reductionsValid } from '@/Pages/Settings/Tickets/drafts.js'

const props = defineProps({
    connection: { type: Object, required: true },
    reductions: { type: Array, default: () => [] },
    ticketsError: { type: String, default: null },
})

const buildDrafts = () => props.reductions.map(reductionDraft)
const reductionDrafts = ref(buildDrafts())
const { dirty, markClean } = usePendingChanges(reductionDrafts)

const form = useForm({ reductions: [], removed_ids: [] })

/* Not on top of a list that failed to load: every row would be added a second time. */
const canSync = computed(() => !form.processing && !props.ticketsError && dirty.value && reductionsValid(reductionDrafts.value))

function discard() {
    reductionDrafts.value = buildDrafts()
    markClean()
}

function submit() {
    form.reductions = reductionDrafts.value.map(reductionPayload)
    /* Only rows loaded here and taken out are deleted; one added meanwhile elsewhere stays. */
    const kept = new Set(reductionDrafts.value.map((reduction) => reduction.id))
    form.removed_ids = props.reductions.map((reduction) => reduction.id).filter((id) => !kept.has(id))
    form.post(route('settings.tickets.reductions.sync'), { preserveScroll: true, onSuccess: discard })
}
</script>
