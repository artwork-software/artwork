<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-1 py-1 rounded-lg"
                 :style="{backgroundColor: backgroundColorWithOpacity(event.eventTypeColor, usePage().props.high_contrast_percent), color: getTextColorBasedOnBackground(backgroundColorWithOpacity(event.eventTypeColor, usePage().props.high_contrast_percent))}">
                <a v-if="event.projectId" :href="route('projects.tab', {project: event.projectId, projectTab: firstProjectShiftTabId})" class="w-40 truncate cursor-pointer hover:text-gray-500 transition-all duration-150 ease-in-out">
                    {{ event.eventTypeAbbreviation }}: {{ event.eventName ?? event.projectName }}
                </a>
                <div v-else class="w-40 truncate">
                    {{ event.eventTypeAbbreviation }}: {{ event.eventName ?? event.projectName }}
                </div>
                <div class="text-xs">
                    <div v-if="event.allDay">
                        {{ $t('All day') }}
                    </div>
                    <div v-else-if="event.days_of_event.length === 1">
                        {{ event.timesWithoutDates.start }} - {{ event.timesWithoutDates.end }}
                    </div>
                    <div v-else>
                        {{ event.formatted_dates.start }} - {{ event.formatted_dates.end }}
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
