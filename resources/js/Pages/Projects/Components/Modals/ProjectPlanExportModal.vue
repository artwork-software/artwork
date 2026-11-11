<template>
    <ArtworkBaseModal
        title="Export plans"
        description="Event plan and artist plan as a Word document that you can continue editing."
        modal-size="sm:max-w-2xl"
        @close="emits('close')"
    >
        <div v-if="loading" class="space-y-3" aria-busy="true">
            <div class="h-16 animate-pulse rounded-lg bg-surface-sunken"></div>
            <div class="h-10 animate-pulse rounded-lg bg-surface-sunken"></div>
            <div class="h-32 animate-pulse rounded-lg bg-surface-sunken"></div>
        </div>

        <div v-else-if="loadError" class="rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-sm text-danger">
            {{ $t('The export options could not be loaded. Please try again.') }}
        </div>

        <div v-else class="space-y-6">
            <fieldset>
                <legend class="font-lexend text-sm font-medium text-text">{{ $t('Plan') }}</legend>
                <div class="mt-3 grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in planTypes"
                        :key="option.id"
                        :for="`project-plan-type-${option.id}`"
                        class="flex cursor-pointer items-start gap-3 rounded-lg border px-3.5 py-2.5 transition-colors duration-150 motion-reduce:transition-none has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-accent-600"
                        :class="planType === option.id ? 'border-accent-600 bg-accent-50' : 'border-border-subtle bg-surface hover:bg-surface-hover'"
                    >
                        <input
                            :id="`project-plan-type-${option.id}`"
                            v-model="planType"
                            type="radio"
                            name="project-plan-type"
                            :value="option.id"
                            class="sr-only"
                        />
                        <span
                            class="mt-0.5 box-border size-4 shrink-0 rounded-full border bg-surface"
                            :class="planType === option.id ? 'border-[5px] border-accent-600' : 'border-border'"
                            aria-hidden="true"
                        ></span>
                        <span>
                            <span class="block text-sm font-medium text-text">{{ $t(option.title) }}</span>
                            <span class="mt-0.5 block text-xs text-text-muted">{{ $t(option.hint) }}</span>
                        </span>
                    </label>
                </div>
            </fieldset>

            <div>
                <BaseCombobox
                    v-model="venueRoomId"
                    :items="venueItems"
                    :option-label="venueLabel"
                    option-key="id"
                    :label="$t('Venue')"
                    :placeholder="$t('Please select')"
                    :empty-text="$t('No results')"
                />
                <p class="mt-1 text-xs text-text-muted">{{ $t('Name and address of the room appear at the top of the plan. Addresses are maintained in the room settings.') }}</p>
            </div>

            <template v-if="planType === 'artist'">
                <div v-if="options.can_see_artist_residencies">
                    <BaseCombobox
                        v-model="artistResidencyId"
                        :items="residencyItems"
                        :option-label="residencyLabel"
                        option-key="id"
                        :label="$t('Artist residency')"
                        :placeholder="$t('Please select')"
                        :empty-text="$t('No results')"
                    />
                    <p class="mt-1 text-xs text-text-muted">{{ $t('The plan is created for this person or ensemble and contains the accommodation details without costs.') }}</p>
                </div>

                <fieldset v-if="options.event_types.length > 0">
                    <legend class="font-lexend text-sm font-medium text-text">{{ $t('Event types') }}</legend>
                    <p class="mt-0.5 text-xs text-text-muted">{{ $t('Preselects all events of these types, e.g. transfer, arrival and departure. Your selection is saved for the next export.') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button
                            v-for="eventType in options.event_types"
                            :key="eventType.id"
                            type="button"
                            class="rounded-full border px-3 py-1 text-xs font-medium transition-colors duration-150 motion-reduce:transition-none focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent-600"
                            :class="selection.typeIds.has(eventType.id) ? 'border-accent-600 bg-accent-50 text-accent-700' : 'border-border-subtle bg-surface text-text hover:bg-surface-hover'"
                            :aria-pressed="selection.typeIds.has(eventType.id)"
                            @click="toggleEventType(eventType.id)"
                        >
                            {{ eventType.name }}
                        </button>
                    </div>
                </fieldset>

                <fieldset>
                    <div class="flex items-baseline justify-between gap-3">
                        <legend class="font-lexend text-sm font-medium text-text">{{ $t('Events') }}</legend>
                        <span class="text-xs text-text-muted tabular-nums">
                            {{ $t('{count} of {total} selected', {count: selectedEventIds.length, total: options.events.length}) }}
                        </span>
                    </div>
                    <p v-if="options.events.length === 0" class="mt-2 text-sm text-text-muted">
                        {{ $t('This project has no events yet.') }}
                    </p>
                    <div v-else class="mt-2 max-h-72 overflow-y-auto rounded-lg border border-border-subtle">
                        <div v-for="day in eventsByDay" :key="day.label">
                            <div class="sticky top-0 border-b border-border-subtle bg-surface-sunken px-3 py-1.5 text-xs font-semibold text-text">
                                {{ day.label }}
                            </div>
                            <BaseCheckbox
                                v-for="event in day.events"
                                :id="`project-plan-event-${event.id}`"
                                :key="event.id"
                                class="px-3 py-1.5"
                                :model-value="isEventSelected(event)"
                                @update:model-value="toggleEvent(event)"
                            >
                                <template #label>
                                    <span class="tabular-nums text-text-muted">{{ event.time }}</span>
                                    <span class="ml-2 font-medium text-text">{{ event.title || $t('Without name') }}</span>
                                    <span v-if="event.room" class="ml-2 text-text-muted">· {{ event.room }}</span>
                                </template>
                            </BaseCheckbox>
                        </div>
                    </div>
                </fieldset>
            </template>

            <p v-if="exportError" class="rounded-lg border border-danger-border bg-danger-surface px-3 py-2 text-sm text-danger" role="alert">
                {{ exportError }}
            </p>
        </div>

        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="emits('close')"
            />
            <BaseUIButton
                label="Export as Word"
                variant="primary"
                icon="IconFileTypeDocx"
                :processing="processing"
                :disabled="loading || loadError"
                @click="exportPlan"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, onMounted, ref} from "vue";
import axios from "axios";
import {useI18n} from "vue-i18n";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseCombobox from "@/Artwork/Inputs/BaseCombobox.vue";
import BaseCheckbox from "@/Artwork/Inputs/BaseCheckbox.vue";
import {useBlobDownload} from "@/Layouts/Components/Export/Components/useBlobDownload.js";
import {
    createPlanEventSelection,
    isPlanEventSelected,
    selectedPlanEventIds,
    togglePlanEvent,
    togglePlanEventType,
} from "@/Helper/planEventSelection.js";

defineOptions({name: 'ProjectPlanExportModal'})

const props = defineProps({
    project: {
        type: Object,
        required: true,
    },
})

const emits = defineEmits(['close'])

/** Platzhalter-ID für „keine Auswahl“ in den Comboboxen (null kann die Combobox nicht als Eintrag führen) */
const NONE = 0

const planTypes = [
    {id: 'event', title: 'Event plan', hint: 'All events of the project, artists, venue and project team.'},
    {id: 'artist', title: 'Artist plan', hint: 'Selected events, accommodation and contact persons for one person or ensemble.'},
]

const {t: trans} = useI18n()
const {download} = useBlobDownload()

const loading = ref(true)
const loadError = ref(false)
const processing = ref(false)
const exportError = ref('')
const options = ref({rooms: [], events: [], event_types: [], artist_residencies: [], can_see_artist_residencies: false})

const planType = ref('event')
const venueRoomId = ref(NONE)
const artistResidencyId = ref(NONE)
const selection = ref(createPlanEventSelection())

const venueItems = computed(() => [{id: NONE, name: trans('Without venue'), address: null}, ...options.value.rooms])
const residencyItems = computed(() => [{id: NONE, name: trans('Without accommodation')}, ...options.value.artist_residencies])

const venueLabel = (room) => (room.address ? `${room.name} (${room.address})` : room.name)

const residencyLabel = (residency) => {
    if (residency.id === NONE) {
        return residency.name
    }
    const period = [residency.arrival, residency.departure].filter(Boolean).join(' – ')
    const details = [residency.accommodation, period].filter(Boolean).join(', ')
    const name = residency.name || trans('Without name')

    return details ? `${name} (${details})` : name
}

const eventsByDay = computed(() => {
    const days = []
    for (const event of options.value.events) {
        const lastDay = days[days.length - 1]
        if (lastDay?.label === event.day) {
            lastDay.events.push(event)
        } else {
            days.push({label: event.day, events: [event]})
        }
    }
    return days
})

const isEventSelected = (event) => isPlanEventSelected(selection.value, event)

const selectedEventIds = computed(() => selectedPlanEventIds(selection.value, options.value.events))

const toggleEventType = (eventTypeId) => {
    selection.value = togglePlanEventType(selection.value, eventTypeId, options.value.events)
}

const toggleEvent = (event) => {
    selection.value = togglePlanEvent(selection.value, event)
}

const loadOptions = async () => {
    loading.value = true
    loadError.value = false
    try {
        const {data} = await axios.get(route('projects.plans.options', {project: props.project.id}))
        options.value = data
        venueRoomId.value = data.suggested_venue_room_id ?? NONE
        selection.value = createPlanEventSelection(data.remembered_event_type_ids ?? [])
        if (data.artist_residencies?.length === 1) {
            artistResidencyId.value = data.artist_residencies[0].id
        }
    } catch {
        loadError.value = true
    } finally {
        loading.value = false
    }
}

const exportPlan = async () => {
    if (processing.value) {
        return
    }
    processing.value = true
    exportError.value = ''

    const venue = venueRoomId.value || null
    const isArtistPlan = planType.value === 'artist'
    const params = isArtistPlan
        ? {
            venue_room_id: venue,
            artist_residency_id: artistResidencyId.value || null,
            event_type_ids: [...selection.value.typeIds],
            event_ids: selectedEventIds.value,
        }
        : {venue_room_id: venue}

    try {
        await download(
            route(isArtistPlan ? 'projects.plans.artist-plan' : 'projects.plans.event-plan', {project: props.project.id}),
            params,
            `${trans(isArtistPlan ? 'Artist plan' : 'Event plan')}.docx`
        )
        emits('close')
    } catch (error) {
        exportError.value = error?.fromServer ? error.message : trans('Export could not be created. Please try again.')
    } finally {
        processing.value = false
    }
}

onMounted(loadOptions)
</script>
