<template>
    <div>
        <div>
            <div class="text-secondary-hover rounded-lg flex flex-col"
                 :class="[shiftPlanSettings.time_period_project_id === project?.id && shiftPlanSettings.use_project_time_period ? 'border-[3px] border-dashed !border-pink-500' : '']"
                 :style="{backgroundColor: backgroundColorWithOpacity(eventType.hex_code, percentage), color: getTextColorBasedOnBackground(backgroundColorWithOpacity(eventType.hex_code, percentage)),
                 borderColor: eventType.hex_code}">

                <!-- Projektgruppen-Balken (wie im FullEventInCalendar) -->
                <div
                    v-if="shiftPlanSettings.display_project_groups && project?.isInGroup && project?.group && project?.group.length > 0 && !project?.isGroup"
                    class="w-full rounded-t-lg px-2 py-1 border-b border-black/15"
                    :style="{
                        backgroundColor: project.group[0].color ? project.group[0].color + '40' : 'transparent'
                    }"
                >
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span :class="[expandDays ? 'break-words' : 'truncate', 'block w-full font-semibold text-xs text-black']">
                            {{ project.group[0].name }}
                        </span>
                    </div>
                </div>

                <!-- Projektname-Balken mit Trennstrich (wie im FullEventInCalendar) -->
                <div
                    v-if="project?.id"
                    class="w-full px-2 py-1 border-b border-black/15"
                    :style="{
                        backgroundColor: project?.isGroup && project?.color ? project.color + '40' : 'transparent'
                    }"
                >
                    <div
                        class="flex items-center gap-1.5 min-w-0"
                        @mouseenter="showProjectNameTooltipHandler"
                        @mouseleave="hideProjectNameTooltip"
                    >
                        <!-- Projekt-Status Punkt (Anzeigeeinstellung "Projektstatus") -->
                        <div
                            v-if="shiftPlanSettings.project_status && project?.status"
                            class="shrink-0 flex-none size-3.5 min-w-3.5 min-h-3.5 rounded-full border"
                            :style="{ backgroundColor: project.status.color + '33', borderColor: project.status.color }"
                            v-tooltip.bottom="{ value: project.status.name, class: 'aw-tooltip' }"
                        ></div>
                        <a :href="route('projects.tab', {project: project?.id, projectTab: firstProjectShiftTabId})"
                           class="relative flex-1 min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2 transition ease-in-out duration-200">
                            <span ref="projectNameSpan" :class="[expandDays ? 'break-words' : 'truncate', 'block w-full font-semibold text-xs']">
                                {{ project?.name }}
                            </span>
                        </a>
                        <Teleport to="body">
                            <div
                                v-if="isProjectNameTruncated && showProjectNameTooltipFlag"
                                class="fixed z-[9999] pointer-events-none"
                                :style="{ top: projectNameTooltipPosition.top + 'px', left: projectNameTooltipPosition.left + 'px' }"
                            >
                                <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                    {{ project?.name }}
                                </div>
                            </div>
                        </Teleport>
                    </div>
                </div>

                <!-- Content-Bereich -->
                <div class="flex items-stretch gap-x-2 px-2 py-2">
                    <div class="p-1 rounded-lg w-1" :style="{backgroundColor: eventType.hex_code}" v-if="!shiftPlanSettings.high_contrast"></div>
                    <div :class="[expandDays ? '' : 'max-w-40 w-40', 'min-w-0']" :style="{borderColor: eventType.hex_code}">
                        <!-- Künstler*innen (Anzeigeeinstellung "Künstler*innen") -->
                        <div
                            v-if="shiftPlanSettings.project_artists && project?.artistNames"
                            :class="[expandDays ? 'break-words' : 'truncate', 'text-xs/5 font-bold']"
                            v-tooltip.bottom="{ value: project.artistNames, class: 'aw-tooltip' }"
                        >
                            {{ project.artistNames }}
                        </div>
                        <!-- Eventtyp-Abbreviation: Eventname + Termineigenschaften oben rechts (wie im FullEventInCalendar) -->
                        <div class="flex items-start justify-between gap-x-1">
                            <div
                                class="relative flex-1 min-w-0"
                                @mouseenter="showEventNameTooltipHandler"
                                @mouseleave="hideEventNameTooltip"
                            >
                                <div v-if="project?.id">
                                    <a :href="route('projects.tab', {project: project?.id, projectTab: firstProjectShiftTabId})" class="cursor-pointer hover:text-gray-500 transition-all duration-150 ease-in-out">
                                        <span ref="eventNameSpan" :class="[expandDays ? 'break-words' : 'truncate', 'block text-xs/4 font-semibold']">
                                            {{ eventType?.abbreviation }}: {{ event.eventName }}
                                        </span>
                                    </a>
                                </div>
                                <span v-else ref="eventNameSpan" :class="[expandDays ? 'break-words' : 'truncate', 'block text-xs/4 font-semibold']">
                                    {{ eventType?.abbreviation }}: {{ event?.eventName }}
                                </span>
                                <Teleport to="body">
                                    <div
                                        v-if="isEventNameTruncated && showEventNameTooltipFlag"
                                        class="fixed z-[9999] pointer-events-none"
                                        :style="{ top: eventNameTooltipPosition.top + 'px', left: eventNameTooltipPosition.left + 'px' }"
                                    >
                                        <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                            {{ eventType?.abbreviation }}: {{ event?.eventName }}
                                        </div>
                                    </div>
                                </Teleport>
                            </div>
                            <div v-if="event.eventProperties?.length" class="flex flex-wrap items-center justify-end gap-1 shrink-0 max-w-[45%]">
                                <div
                                    v-for="property in event.eventProperties"
                                    :key="'prop-' + property.id"
                                    v-tooltip.bottom="{ value: property.name, class: 'aw-tooltip' }"
                                >
                                    <PropertyIcon
                                        :name="property.icon"
                                        class="size-3.5 opacity-90"
                                    />
                                </div>
                            </div>
                        </div>
                        <div class="text-xs/5 mt-0.5">
                            <div v-if="event.allDay">
                                {{ $t('All day') }}
                            </div>
                            <div v-else-if="daysOfEvent.length === 1">
                                <span>{{ formattedDates?.startTime }} - {{ formattedDates?.endTime }}</span>
                            </div>
                            <div v-else>
                                {{ formattedDates?.start }} - {{ formattedDates?.end }}
                            </div>
                        </div>
                        <!-- Terminbeschreibung (Anzeigeeinstellung "Notizen einblenden") -->
                        <div
                            v-if="shiftPlanSettings.shift_notes && event.description"
                            class="text-xs mt-0.5 opacity-80"
                        >
                            <span
                                :class="[expandDays ? 'break-words' : 'truncate', 'block']"
                                v-tooltip.bottom="{ value: event.description, class: 'aw-tooltip' }"
                            >
                                {{ event.description }}
                            </span>
                        </div>
                        <!-- Projektleitung (Anzeigeeinstellung "Projektleitung") -->
                        <div
                            v-if="shiftPlanSettings.project_management && project?.leaders?.length"
                            class="mt-1 flex flex-wrap items-center gap-1"
                        >
                            <span v-if="showCreatorLeaderLabels" class="text-[10px] font-semibold opacity-80">{{ $t('PM:') }}</span>
                            <UserPopoverTooltip
                                v-for="user in project.leaders.slice(0, 3)"
                                :key="'leader-' + user.id"
                                :user="user"
                                lazy-load
                                width="5"
                                height="5"
                            />
                            <div
                                v-if="project.leaders.length > 3"
                                class="flex h-5 w-5 items-center justify-center rounded-full bg-black text-[11px] font-semibold text-white"
                            >
                                +{{ project.leaders.length - 3 }}
                            </div>
                        </div>
                        <!-- Terminersteller*in (Anzeigeeinstellung "Terminersteller*in") -->
                        <div
                            v-if="shiftPlanSettings.show_event_creator && event.created_by"
                            class="mt-1 flex flex-wrap items-center gap-1"
                        >
                            <span v-if="showCreatorLeaderLabels" class="text-[10px] font-semibold opacity-80">{{ $t('Creator:') }}</span>
                            <UserPopoverTooltip
                                :user="event.created_by"
                                lazy-load
                                width="5"
                                height="5"
                            />
                        </div>
                    </div>
                    <!-- Timeline Icon -->
                    <div
                        v-if="shiftPlanSettings.show_timeline"
                        class="ml-auto cursor-pointer self-center"
                        @click.stop="openTimelineModal"
                    >
                        <IconTimeline
                            class="size-4"
                            stroke-width="1.5"
                            :class="event.hasTimelines ? '' : 'text-gray-400'"
                            :style="event.hasTimelines ? {
                                color: getTextColorBasedOnBackground(backgroundColorWithOpacity(eventType.hex_code, percentage))
                            } : {}"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Modal -->
        <AddEditTimelineModal
            v-if="showTimelineModal"
            :event="event"
            :timelineToEdit="timelineData"
            @close="closeTimelineModal"
        />
    </div>
</template>

<script setup>

import { ref, computed, defineAsyncComponent } from "vue";
import axios from "axios";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import {usePage} from "@inertiajs/vue3";
import {IconTimeline} from "@tabler/icons-vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import {useShiftPlanLookups} from "@/Composeables/useShiftPlanLookups.js";
import {getDaysInRange, computeEventFormattedDates} from "@/Composeables/calendarDateUtils.js";

const AddEditTimelineModal = defineAsyncComponent({
    loader: () => import("@/Pages/Projects/Components/TimelineComponents/AddEditTimelineModal.vue"),
    delay: 200,
    timeout: 3000,
});

const { resolveEventType, resolveProject } = useShiftPlanLookups();

const shiftPlanSettings = computed(() => usePage().props.shift_plan_settings ?? usePage().props.auth.user.calendar_settings);
// Nur wenn Projektleitung UND Terminersteller*in aktiv sind, brauchen die
// Avatar-Reihen ein "PL:"/"Erstell:"-Label zur Unterscheidung.
const showCreatorLeaderLabels = computed(() =>
    shiftPlanSettings.value?.project_management && shiftPlanSettings.value?.show_event_creator
);
const expandDays = computed(() => shiftPlanSettings.value?.expand_days ?? false);
const {
    backgroundColorWithOpacity,
    getHighContrastPercent,
    getTextColorBasedOnBackground,
} = useColorHelper();
const percentage = computed(() => getHighContrastPercent(shiftPlanSettings.value));

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
    day: {
        type: Object,
        required: true,
    },
    firstProjectShiftTabId: {
        type: Number,
        required: true
    }
});

// Resolve normalized event data via lookups
const eventType = computed(() => props.event.eventType ?? resolveEventType(props.event.eventTypeId) ?? {});
const project = computed(() => props.event.project ?? resolveProject(props.event.projectId));
const daysOfEvent = computed(() => props.event.daysOfEvent ?? getDaysInRange(props.event.start, props.event.end));
const formattedDates = computed(() => props.event.formattedDates ?? computeEventFormattedDates(props.event.start, props.event.end));

const showTimelineModal = ref(false);
const timelineData = ref([]);

const openTimelineModal = async () => {
    try {
        const response = await axios.get(route('events.timelines', { event: props.event.id }));
        timelineData.value = response.data.timelines || [];
        showTimelineModal.value = true;
    } catch (error) {
        console.error('Error loading timelines:', error);
    }
};

const closeTimelineModal = () => {
    showTimelineModal.value = false;
    timelineData.value = [];
};

// Project name tooltip
const projectNameSpan = ref(null);
const isProjectNameTruncated = ref(false);
const showProjectNameTooltipFlag = ref(false);
const projectNameTooltipPosition = ref({ top: 0, left: 0 });

const showProjectNameTooltipHandler = (e) => {
    const el = projectNameSpan.value;
    if (!el) return;
    isProjectNameTruncated.value = el.scrollWidth > el.clientWidth;
    if (!isProjectNameTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    projectNameTooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showProjectNameTooltipFlag.value = true;
};

const hideProjectNameTooltip = () => {
    showProjectNameTooltipFlag.value = false;
};

// Event name tooltip
const eventNameSpan = ref(null);
const isEventNameTruncated = ref(false);
const showEventNameTooltipFlag = ref(false);
const eventNameTooltipPosition = ref({ top: 0, left: 0 });

const showEventNameTooltipHandler = (e) => {
    const el = eventNameSpan.value;
    if (!el) return;
    isEventNameTruncated.value = el.scrollWidth > el.clientWidth;
    if (!isEventNameTruncated.value) return;
    const rect = e.target.getBoundingClientRect();
    eventNameTooltipPosition.value = { top: rect.bottom + 4, left: rect.left };
    showEventNameTooltipFlag.value = true;
};

const hideEventNameTooltip = () => {
    showEventNameTooltipFlag.value = false;
};

const
    textColorWithDarken = (color, percent = 75) => {
        if (!color) return 'rgb(180, 180, 180)';
        return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
    };
</script>
