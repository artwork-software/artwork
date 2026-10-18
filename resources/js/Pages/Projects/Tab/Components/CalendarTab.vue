<template>
    <div class="mt-6">
        <div class="mt-6 ">
            <!-- Loading State -->
            <div v-if="isLoadingCalendar" class="pl-16 py-8 text-center">
                <div class="text-text-muted">{{ $t('Loading calendar data...') }}</div>
            </div>

            <!-- Error State -->
            <div v-else-if="loadCalendarError" class="pl-16 py-8 text-center text-danger">
                {{ $t(loadCalendarError) }}
            </div>

            <!-- Daily Calendar View -->
            <div v-else-if="calendarType && calendarType === 'daily'">
                <CalendarComponent
                    initial-view="day"
                    :project="project ?? headerObject.project"
                    :selected-date="selectedDate ?? effectiveCalendarData.selectedDate"
                    :dateValue="dateValue ?? effectiveCalendarData.dateValue"
                    :eventTypes="eventTypes ?? effectiveCalendarData.eventTypes"
                    :events="events.events ?? effectiveCalendarData.events?.events"
                    :rooms="rooms ?? effectiveCalendarData.rooms"
                    :events-without-room="eventsWithoutRoom ?? headerObject.eventsWithoutRoom"
                    :filter-options="filterOptions ?? effectiveCalendarData.filterOptions"
                    :personal-filters="personalFilters ?? effectiveCalendarData.personalFilters"
                    :user_filters="user_filters ?? effectiveCalendarData.user_filters"
                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                    :event-statuses="eventStatuses"
                    :can-edit-component="canEditComponent"/>
            </div>

            <!-- Monthly/Weekly Calendar View -->
            <div v-else-if="hasRequiredCalendarData" class="pl-16">
                <BaseCalendar v-if="!atAGlance"
                              :project="project ?? headerObject.project"
                              :rooms="rooms ?? effectiveCalendarData.rooms"
                              :days="days ?? effectiveCalendarData.days"
                              :calendar-data="calendar ?? effectiveCalendarData.calendar"
                              :event-statuses="eventStatuses"
                              :events-without-room="eventsWithoutRoom ?? effectiveCalendarData.eventsWithoutRoom"
                              :can-edit-component="canEditComponent"
                />
                <IndividualCalendarAtGlanceComponent v-else
                                                     :event-statuses="eventStatuses"
                                                     :atAGlance="atAGlance"
                                                     :project="project ?? headerObject.project"
                                                     :rooms="rooms ?? effectiveCalendarData.rooms"
                                                     :dateValue="dateValue ?? effectiveCalendarData.dateValue"
                                                     :eventTypes="eventTypes ?? effectiveCalendarData.eventTypes"
                                                     :eventsAtAGlance="eventsAtAGlance ?? effectiveCalendarData.eventsAtAGlance"
                                                     :filter-options="filterOptions ?? effectiveCalendarData.filterOptions"
                                                     :personal-filters="personalFilters ?? effectiveCalendarData.personalFilters"
                                                     :user_filters="user_filters ?? effectiveCalendarData.user_filters"
                                                     :first_project_tab_id="first_project_tab_id ?? effectiveCalendarData.first_project_tab_id"
                                                     :first_project_calendar_tab_id="first_project_calendar_tab_id ?? effectiveCalendarData.first_project_calendar_tab_id"
                                                     :isCalendarViewRoute="false"
                                                     :can-edit-component="canEditComponent"/>
            </div>

            <!-- Fallback if data is missing -->
            <div v-else class="pl-16 py-8 text-center text-text-muted">
                {{ $t('Calendar data is not available.') }}
            </div>
        </div>
    </div>
</template>

<script setup>
import {router, usePage} from "@inertiajs/vue3";
import {provide, ref, onMounted, computed} from "vue";
import axios from 'axios';
import { createLatestRequestTracker } from "@/Helper/latestRequest.js";
import BaseCalendar from "@/Components/Calendar/BaseCalendar.vue";
import IndividualCalendarAtGlanceComponent from "@/Layouts/Components/IndividualCalendarAtGlanceComponent.vue";
import CalendarComponent from "@/Layouts/Components/CalendarComponent.vue";

const props = defineProps([
    'project',
    'calendarType',
    'selectedDate',
    'dateValue',
    'events',
    'rooms',
    'eventsWithoutRoom',
    'filterOptions',
    'personalFilters',
    'atAGlance',
    'eventsAtAGlance',
    'calendar',
    'days',
    'eventTypes',
    'projectWriteIds',
    'projectManagerIds',
    'user_filters',
    'loadedProjectInformation',
    'headerObject',
    'first_project_tab_id',
    'first_project_calendar_tab_id',
    'eventStatuses',
    'canEditComponent'
]),
atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);

const isLoadingCalendar = ref(false);
const loadCalendarError = ref('');
const localCalendarData = ref(props.loadedProjectInformation?.['CalendarTab'] || null);

const effectiveCalendarData = computed(() => {
    return localCalendarData.value || props.loadedProjectInformation?.['CalendarTab'] || {};
});

const hasRequiredCalendarData = computed(() => {
    // Check if we have the required data for BaseCalendar
    const calData = effectiveCalendarData.value;

    // For BaseCalendar, we need days, rooms, and calendar data
    const hasDays = props.days || (calData.days && (Array.isArray(calData.days) || typeof calData.days === 'object'));
    const hasRooms = props.rooms || (calData.rooms && (Array.isArray(calData.rooms) || typeof calData.rooms === 'object'));
    const hasCalendar = props.calendar || (calData.calendar && (Array.isArray(calData.calendar) || typeof calData.calendar === 'object'));

    // For AtAGlance, we need different data
    if (atAGlance.value) {
        const hasEventsAtAGlance = props.eventsAtAGlance || calData.eventsAtAGlance;
        return hasRooms && hasEventsAtAGlance;
    }

    return hasDays && hasRooms && hasCalendar;
});

const calendarRequests = createLatestRequestTracker();

async function fetchCalendarData({ force = false } = {}) {
    if (localCalendarData.value && !force) {
        return;
    }

    const projectId = props.project?.id;
    if (!projectId) {
        return;
    }

    // Nachladen (force) still im Hintergrund: der Kalender bleibt stehen (Scroll, Multi-Edit-Modus)
    if (!force) {
        isLoadingCalendar.value = true;
        loadCalendarError.value = '';
    }

    // Überlappende Läufe: nur die zuletzt gestartete Antwort übernehmen, nur sie beendet das Laden
    const requestId = calendarRequests.begin();
    try {
        const { data } = await axios.get(
            route('projects.tabs.calendar', { project: projectId }),
            { params: { atAGlance: atAGlance.value } }
        );
        if (!calendarRequests.isLatest(requestId)) {
            return;
        }
        localCalendarData.value = data?.CalendarTab || (force ? localCalendarData.value : null);
    } catch (error) {
        if (!calendarRequests.isLatest(requestId)) {
            return;
        }
        console.error(error);
        if (!force) {
            loadCalendarError.value = 'Unable to load calendar data.';
        }
    } finally {
        if (calendarRequests.isLatest(requestId)) {
            isLoadingCalendar.value = false;
        }
    }
}

onMounted(() => {
    fetchCalendarData();
});

/**
 * Kalenderdaten nach Mehrfachbearbeitung neu holen: localCalendarData ist eine Kopie, die ein
 * router.reload nicht erneuert. Kommen die Termine direkt als Prop, lädt die Seite neu.
 */
function reloadCalendarData() {
    if (props.eventsAtAGlance || props.calendar) {
        router.reload();
        return;
    }
    fetchCalendarData({ force: true });
}

provide('reloadCalendarTabData', reloadCalendarData);

provide('eventTypes', props.eventTypes ?? effectiveCalendarData.value.eventTypes);
provide('dateValue', props.dateValue ?? effectiveCalendarData.value.dateValue);
provide('first_project_tab_id', props.first_project_tab_id ?? effectiveCalendarData.value.first_project_tab_id);
provide('first_project_calendar_tab_id', props.first_project_calendar_tab_id ?? effectiveCalendarData.value.first_project_calendar_tab_id);
// Computed, damit die asynchron nachgeladenen Kalenderdaten (fetchCalendarData) ankommen
provide('user_filters', computed(() => props.user_filters ?? effectiveCalendarData.value.user_filters));
provide('personalFilters', props.personalFilters ?? effectiveCalendarData.value.personalFilters);
provide('filterOptions', props.filterOptions ?? effectiveCalendarData.value.filterOptions);
provide('eventStatuses', props.eventStatuses ?? effectiveCalendarData.value.eventStatuses);
provide('event_properties', effectiveCalendarData.value.event_properties);
</script>
