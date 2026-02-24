<template>
    <!-- Zusatz: Gelber Balken über dem Termin, wenn ganztägig -->
    <div v-if="event.allDay" class="w-full rounded-t-lg bg-yellow-300 text-black text-[11px] font-lexend text-center py-0.5 mb-0.5">
        {{$t('All day')}}
    </div>
    <!-- Hauptkarte: Einheitliches Styling (ehemals "bei Kollision") -->
    <div :class="['w-full min-w-64 select-none rounded-lg border']"
         :style="{ backgroundColor: hexColor + '40', borderColor: borderColor }">
        <!-- Inhalt: zweizeilig (Zeit/Typ, darunter Titel + Menü) -->
        <div class="flex justify-between font-lexend min-w-0">
            <!-- Zeile 1: Zeit + Typ -->
            <div class="flex items-center gap-x-2 min-w-0 pr-3">
                <div :class="['rounded-md', timePillPadding, 'whitespace-nowrap']" :style="{ backgroundColor: hexColor + '90' }">
                    <span v-if="event.allDay">{{ $t('All day') }}</span>
                    <span v-else>{{ event.formattedDates.startTime }} - {{ event.formattedDates.endTime }}</span>
                </div>
                <div class="whitespace-nowrap font-medium" :class="subtitleTextClass">
                    {{ event.eventType?.abbreviation }}:
                </div>
                <span
                    :class="['truncate flex-1 min-w-0 cursor-pointer', titleTextClass]"
                    ref="titleRef"
                    v-tooltip.bottom="titleTooltip"
                    :aria-label="eventTitleFull"
                    @click.stop="toggleEventDetails"
                >
                    {{ eventTitle }}
                </span>
            </div>
            <div class="flex items-center min-w-0 pr-1">
                <div class="flex transition-opacity duration-150">
                    <BaseMenu has-no-offset :dots-color="$page.props.auth.user.calendar_settings.high_contrast ? 'text-white' : ''" white-menu-background class="cursor-pointer">
                        <BaseMenuItem white-menu-background v-if="can('can plan shifts') || is('artwork admin')" @click="showEventComponent = true" :icon="IconEdit" title="edit" />
                        <BaseMenuItem white-menu-background v-if="can('can plan shifts') || is('artwork admin')" @click="openConfirmDeleteModal" :icon="IconTrash" :title="$t('Delete event')" />
                        <BaseMenuItem white-menu-background v-if="event.timelines?.length > 0" @click="showCreateTimelinePresetModal = true" :icon="IconDeviceFloppy" :title="$t('Save timeline as preset')" />
                        <BaseMenuItem white-menu-background @click="showSearchTimelinePresetModal = true" :icon="IconFileImport" :title="$t('Import timeline preset')" />
                    </BaseMenu>
                </div>
            </div>
        </div>
    </div>

    <div v-if="showEventDetails" class="mt-1 ml-2">
        <div v-if="event.timelines?.length !== 0" class="space-y-1">
            <div v-for="(timeline, index) in event.timelines"
                :key="timeline.id"
                class="flex items-center gap-x-2 font-lexend rounded-lg"
                :style="{ backgroundColor: hexColor + '20' }">
                <div class="py-1.5 px-3 min-w-28 rounded-l-lg" :style="{ backgroundColor: hexColor + '50' }">
                    <p class="text-xs">
                        <template v-if="timeline.start_date !== timeline.end_date">
                            {{ timeline.formatted_dates.start_date }} {{ timeline.start }} -
                            {{ timeline.formatted_dates.end_date }} {{ timeline.end }}
                        </template>
                        <template v-else-if="timeline.start_or_end && timeline.start === timeline.end">
                            {{ $t('From') }} {{ timeline.start }}
                        </template>
                        <template v-else-if="!timeline.start_or_end && timeline.start === timeline.end">
                            {{ $t('Until') }} {{ timeline.end }}
                        </template>
                        <template v-else>
                            {{ timeline.start }} - {{ timeline.end }}
                        </template>
                    </p>
                </div>
                <p class="text-xs" v-html="timeline.description"></p>
            </div>
        </div>

        <div class="w-full bg-gray-100 rounded-lg py-1.5 px-3 mt-1 border border-artwork-buttons-create">
            <div class="text-artwork-buttons-create cursor-pointer flex items-center gap-x-1"
                @click="showAddTimeLineModal = true">
                <component :is="IconWand" class="size-4"/>
                {{ event.timelines.length === 0 ? $t('Create new timeline') : $t('Edit timeline') }}
            </div>
        </div>
    </div>
    <EventComponent
        v-if="showEventComponent"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :calendarProjectPeriod="usePage().props.auth.user.calendar_settings.use_project_time_period"
        :project="null"
        :event="event"
        :wantedRoomId="wantedRoomId"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        :requires-axios-requests="true"
        @closed="showEventComponent = false"
        :event-statuses="eventStatuses"
        :is-planning="isPlanning"
        :wanted-date="wantedDate"

    />

    <AddEditTimelineModal
        v-if="showAddTimeLineModal"
        :event="event"
        :timelineToEdit="event.timelines"
        @close="showAddTimeLineModal = false"/>

    <CreateTimelinePresetFormEvent
        v-if="showCreateTimelinePresetModal"
        :event="event"
        @close="showCreateTimelinePresetModal = false"
    />

    <SearchTimelinePresetModal
        v-if="showSearchTimelinePresetModal"
        :event="event"
        @close="showSearchTimelinePresetModal = false"
    />

    <!-- Bestätigungsmodal: Termin löschen -->
    <ConfirmationComponent
        v-if="showConfirmDeleteModal"
        titel="Termin löschen"
        description="Möchtest du diesen Termin wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden."
        @closed="handleConfirmDelete"
    />
</template>

<script setup>

import {computed, defineAsyncComponent, ref, watch, onMounted, onBeforeUnmount, nextTick} from "vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {usePage} from "@inertiajs/vue3";
import {can, is} from "laravel-permission-to-vuejs";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {IconDeviceFloppy, IconEdit, IconFileImport, IconTrash, IconWand} from "@tabler/icons-vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import { router } from '@inertiajs/vue3'

const props = defineProps({
    event: {
        type: Object,
        required: true,
        default: () => ({})
    },
    rooms: {
        type: [Object, Array],
        required: true
    },
    eventStatuses: {
        type: [Object, Array],
        required: true
    },
    eventTypes: {
        type: [Object, Array],
        required: true
    },
    first_project_calendar_tab_id: {
        type: Number,
        required: true
    },
    // hasCollision wird nicht mehr für Styling verwendet, bleibt für Abwärtskompatibilität erhalten
    hasCollision: {
        type: Boolean,
        default: false
    },
})

// Anforderung: Termine in der Daily-Ansicht standardmäßig aufgeklappt anzeigen,
// damit der Button „Create new timeline“ direkt sichtbar ist.
const showEventDetails = ref(true);
const showAddTimeLineModal = ref(false);
const showCreateTimelinePresetModal = ref(false);
const showSearchTimelinePresetModal = ref(false);
const showEventComponent = ref(false);
const showConfirmDeleteModal = ref(false);
const hexColor = computed(() => props.event.eventType.hex_code || '#cccccc');
const borderColor = computed(() => hexColor.value + 'A0')

const wantedRoomId = ref(props.event.roomId);
const wantedDate = ref(null);
const isPlanning = ref(false);
const roomCollisions = ref([]);

const hasAdminRole = () => is('artwork admin')

const AddEditTimelineModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue'),
    delay: 200,
    timeout: 5000
})

const CreateTimelinePresetFormEvent = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/CreateTimelinePresetFormEvent.vue'),
    delay: 200,
    timeout: 5000
})

const SearchTimelinePresetModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/SearchTimelinePresetModal.vue'),
    delay: 200,
    timeout: 5000
})

const ConfirmationComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/ConfirmationComponent.vue'),
    delay: 200,
});

// Eigene Events für die übergeordnete Timeline-Komponente
const emit = defineEmits(['toggle'])

const toggleEventDetails = () => {
    showEventDetails.value = !showEventDetails.value
    emit('toggle', showEventDetails.value)
}

// Öffnet das Bestätigungsmodal zum Löschen des Termins
const openConfirmDeleteModal = () => {
    showConfirmDeleteModal.value = true;
}

// Handler für Confirm/Cancel aus ConfirmationComponent
const handleConfirmDelete = (confirmed) => {
    showConfirmDeleteModal.value = false;
    if (!confirmed) return;
    router.delete(route('events.delete', { event: props.event.id }), {
        preserveScroll: true,
    });
}

// initialen Zustand einmalig emittieren, damit Containerhöhen korrekt berechnet werden können
watch(() => showEventDetails.value, (val, oldVal) => {
    if (val !== oldVal) return
    // beim ersten Setup wird oldVal undefined sein; nichts tun
}, { flush: 'post', immediate: true })

// Einheitliche UI-States (verwende nur das bisherige "bei Kollision" Styling)
const timePillPadding = computed(() => 'py-2 pr-2 pl-1 text-xs')
// Titeltypografie konsistent
const titleTextClass = computed(() => 'text-sm font-semibold')
const subtitleTextClass = computed(() => 'text-xs')

// Titel: Eventname oder Projektname (gleiche Logik wie Anforderung)
const eventTitle = computed(() => {
    return props.event?.project?.name || props.event?.eventName || props.event?.eventType?.name || ''
})
const eventTitleFull = computed(() => eventTitle.value)

// Truncate-Tooltip nur wenn tatsächlich abgeschnitten
const titleRef = ref(null)
const isTitleTruncated = ref(false)
const updateTitleTruncation = () => {
    const el = titleRef.value
    if (!el) { isTitleTruncated.value = false; return }
    // el kann ein HTMLElement sein; Zugriff defensiv
    try {
        isTitleTruncated.value = (el.scrollWidth || 0) > (el.clientWidth || 0)
    } catch (e) {
        isTitleTruncated.value = false
    }
}

const titleTooltip = computed(() => ({
    value: eventTitleFull.value,
    disabled: !eventTitleFull.value || !isTitleTruncated.value,
    appendTo: 'body',
    class: 'aw-tooltip',
    position: 'bottom',
    useTranslation: false,
}))

onMounted(() => {
    nextTick(updateTitleTruncation)
    window.addEventListener('resize', updateTitleTruncation)
})
onBeforeUnmount(() => {
    window.removeEventListener('resize', updateTitleTruncation)
})
watch(eventTitle, () => nextTick(updateTitleTruncation))
</script>

<style scoped>

</style>
