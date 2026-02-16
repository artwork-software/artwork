<template>
    <div>
        <div>
            <div class="text-secondary-hover rounded-lg flex flex-col"
                 :class="[usePage().props.auth.user.calendar_settings.time_period_project_id === event?.project?.id && usePage().props.auth.user.calendar_settings.use_project_time_period ? 'border-[3px] border-dashed !border-pink-500' : '']"
                 :style="{backgroundColor: backgroundColorWithOpacity(event.eventType.hex_code, usePage().props.high_contrast_percent), color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.eventType.hex_code, usePage().props.high_contrast_percent)),
                 borderColor: event.eventType.hex_code}">

                <!-- Projektgruppen-Balken (wie im FullEventInCalendar) -->
                <div
                    v-if="usePage().props.auth.user.calendar_settings.display_project_groups && event.project?.isInGroup && event.project?.group && event.project?.group.length > 0 && !event.project?.isGroup"
                    class="w-full rounded-t-lg px-2 py-1 border-b border-black/15"
                    :style="{
                        backgroundColor: event.project.group[0].color ? event.project.group[0].color + '40' : 'transparent'
                    }"
                >
                    <div class="flex items-center gap-1.5 min-w-0">
                        <span class="block w-full truncate font-semibold text-xs text-black">
                            {{ event.project.group[0].name }}
                        </span>
                    </div>
                </div>

                <!-- Projektname-Balken mit Trennstrich (wie im FullEventInCalendar) -->
                <div
                    v-if="event?.project?.id"
                    class="w-full px-2 py-1 border-b border-black/15"
                    :style="{
                        backgroundColor: event.project?.isGroup && event.project?.color ? event.project.color + '40' : 'transparent'
                    }"
                >
                    <div
                        class="flex items-center gap-1.5 min-w-0"
                        @mouseenter="showProjectNameTooltipHandler"
                        @mouseleave="hideProjectNameTooltip"
                    >
                        <a :href="route('projects.tab', {project: event.project?.id, projectTab: firstProjectShiftTabId})"
                           class="relative flex-1 min-w-0 hover:text-artwork-buttons-hover hover:underline underline-offset-2 transition ease-in-out duration-200">
                            <span ref="projectNameSpan" class="block w-full truncate font-semibold text-xs">
                                {{ event.project?.name }}
                            </span>
                        </a>
                        <Teleport to="body">
                            <div
                                v-if="isProjectNameTruncated && showProjectNameTooltipFlag"
                                class="fixed z-[9999] pointer-events-none"
                                :style="{ top: projectNameTooltipPosition.top + 'px', left: projectNameTooltipPosition.left + 'px' }"
                            >
                                <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                    {{ event.project?.name }}
                                </div>
                            </div>
                        </Teleport>
                    </div>
                </div>

                <!-- Content-Bereich -->
                <div class="flex items-stretch gap-x-2 px-2 py-2">
                    <div class="p-1 rounded-lg w-1" :style="{backgroundColor: event.eventType.hex_code}" v-if="!usePage().props.auth.user.calendar_settings.high_contrast"></div>
                    <div class="max-w-40 w-40 min-w-0" :style="{borderColor: event.eventType.hex_code}">
                        <!-- Eventtyp-Abbreviation: Eventname -->
                        <div
                            class="relative"
                            @mouseenter="showEventNameTooltipHandler"
                            @mouseleave="hideEventNameTooltip"
                        >
                            <div v-if="event?.project?.id">
                                <a :href="route('projects.tab', {project: event.project?.id, projectTab: firstProjectShiftTabId})" class="cursor-pointer hover:text-gray-500 transition-all duration-150 ease-in-out">
                                    <span ref="eventNameSpan" class="block w-40 max-w-40 truncate text-xs/4 font-semibold">
                                        {{ event.eventType?.abbreviation }}: {{ event.eventName }}
                                    </span>
                                </a>
                            </div>
                            <span v-else ref="eventNameSpan" class="block truncate text-xs/4 font-semibold">
                                {{ event?.eventType?.abbreviation }}: {{ event?.eventName }}
                            </span>
                            <Teleport to="body">
                                <div
                                    v-if="isEventNameTruncated && showEventNameTooltipFlag"
                                    class="fixed z-[9999] pointer-events-none"
                                    :style="{ top: eventNameTooltipPosition.top + 'px', left: eventNameTooltipPosition.left + 'px' }"
                                >
                                    <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                        {{ event?.eventType?.abbreviation }}: {{ event?.eventName }}
                                    </div>
                                </div>
                            </Teleport>
                        </div>
                        <div class="text-xs/5 mt-0.5">
                            <div v-if="event.allDay">
                                {{ $t('All day') }}
                            </div>
                            <div v-else-if="event.daysOfEvent.length === 1">
                                <span>{{ event?.formattedDates?.startTime }} - {{ event?.formattedDates?.endTime }}</span>
                            </div>
                            <div v-else>
                                {{ event.formattedDates?.start }} - {{ event.formattedDates?.end }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

import { ref } from "vue";
import {useColorHelper} from "@/Composeables/UseColorHelper.js";
import {usePage} from "@inertiajs/vue3";
const percentage = usePage().props.high_contrast_percent;
const {
    backgroundColorWithOpacity,
    getTextColorBasedOnBackground,
} = useColorHelper();

defineProps({
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
