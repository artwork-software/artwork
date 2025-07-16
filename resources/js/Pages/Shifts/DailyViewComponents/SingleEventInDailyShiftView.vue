<template>
    <div class="grid grid-cols-1 md:grid-cols-2 w-full select-none rounded-lg"
        :style="{ backgroundColor: hexColor + '40' }"
        >
        <div class="flex items-center gap-x-2 font-lexend">
            <div class="py-1.5 px-3 min-w-20 rounded-l-lg" :style="{ backgroundColor: hexColor + '90' }">
                <span v-if="event.allDay">{{ $t('All day') }}</span>
                <span v-else>{{ event.formattedDates.startTime }} - {{ event.formattedDates.endTime }}</span>
            </div>
            <div class="text-gray-700 font-semibold">
                {{ event.eventType.abbreviation }}: {{ event.eventName }}
            </div>
        </div>
        <div class="flex items-center cursor-pointer justify-end gap-x-2 font-lexend px-3">
            <component is="IconEdit"
                       class="size-5 text-gray-500 hover:text-gray-700 cursor-pointer transition-all duration-150 ease-in-out"
                       @click.stop="showEventComponent = true" v-if="can('can plan shifts') || is('artwork admin')" />
            <component
                is="IconChevronDown"
                class="size-5 text-gray-500 hover:text-gray-700 transition-all duration-150 ease-in-out"
                :class="{ 'rotate-180': showEventDetails }"
                @click="showEventDetails = !showEventDetails"
            />
        </div>
    </div>

    <div v-if="showEventDetails" class="mt-1 ml-4">
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

        <div class="w-full bg-gray-100 rounded-lg py-1.5 px-3 mt-1">
            <div class="text-artwork-buttons-create cursor-pointer flex items-center gap-x-1"
                @click="showAddTimeLineModal = true">
                <component is="IconWand" class="size-4"/>
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
        :wantedRoomId="wantedRoom"
        :isAdmin="can('artwork admin')"
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
</template>

<script setup>

import {computed, defineAsyncComponent, ref} from "vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {usePage} from "@inertiajs/vue3";
import {can, is} from "laravel-permission-to-vuejs";
import EventComponent from "@/Layouts/Components/EventComponent.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true,
        default: () => ({})
    },
    rooms: {
        type: Object,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: true
    },
    eventTypes: {
        type: Object,
        required: true
    },
    first_project_calendar_tab_id: {
        type: Number,
        required: true
    },
})

const showEventDetails = ref(props.event.timelines?.length !== 0);
const showAddTimeLineModal = ref(false);
const showEventComponent = ref(false);
const hexColor = computed(() => props.event.eventType.hex_code || '#cccccc');

const wantedRoom = ref(null);
const wantedDate = ref(null);
const isPlanning = ref(false);
const roomCollisions = ref([]);


const AddEditTimelineModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue'),
    delay: 200,
    timeout: 5000
})
</script>

<style scoped>

</style>
