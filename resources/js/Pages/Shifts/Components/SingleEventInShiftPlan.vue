<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-2 py-1 rounded-lg"
                 :class="[usePage().props.auth.user.calendar_settings.time_period_project_id === event?.project?.id && usePage().props.auth.user.calendar_settings.use_project_time_period ? 'border-[3px] border-dashed !border-pink-500' : '']"
                 :style="{backgroundColor: backgroundColorWithOpacity(event.eventType.hex_code, usePage().props.high_contrast_percent), color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.eventType.hex_code, usePage().props.high_contrast_percent)),
                 borderColor: event.eventType.hex_code}">
                <div class="px-1.5 py-1 border-l-4 max-w-40 w-40" :style="{borderColor: event.eventType.hex_code}">
                    <a v-if="event?.project?.id" :href="route('projects.tab', {project: event.project.id, projectTab: firstProjectShiftTabId})" class="cursor-pointer hover:text-gray-500 transition-all duration-150 ease-in-out">
                        <div class="w-40 max-w-40 truncate ">
                            {{ event.eventType.abbreviation }}: {{ event.eventName ?? event.project.name }}
                        </div>
                    </a>
                    <div v-else class="truncate">
                        {{ event?.eventType?.abbreviation }}: {{ event?.eventName ?? event?.project?.name }}
                    </div>
                    <div class="text-xs">
                        <div v-if="event.allDay">
                            {{ $t('All day') }}
                        </div>
                        <div v-else-if="event.daysOfEvent.length === 1">
                            <span >{{ event?.formattedDates?.startTime }} - {{ event?.formattedDates?.endTime }}</span>
                        </div>
                        <div v-else>
                            {{ event.formattedDates.start }} - {{ event.formattedDates.end }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>


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

const
    textColorWithDarken = (color, percent = 75) => {
        if (!color) return 'rgb(180, 180, 180)';
        return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
    };
</script>
