<template>
    <div>
        <div>
            <div class="text-secondaryHover xsWhiteBold px-1 py-1 rounded-lg"
                 :style="{backgroundColor: backgroundColorWithOpacity(event.event_type?.hex_code), color: textColorWithDarken(event.event_type?.hex_code)}">
                <div class="w-40 truncate">
                    {{ event.event_type.abbreviation }}: {{ event?.eventName ?? event?.project?.name }}
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
defineProps({
    event: {
        type: Object,
        required: true,
    },
    day: {
        type: Object,
        required: true,
    },
});

const backgroundColorWithOpacity = (color, percent = 15) => {
        if (!color) return `rgb(255, 255, 255, ${percent}%)`;
        return `rgb(${parseInt(color.slice(-6, -4), 16)}, ${parseInt(color.slice(-4, -2), 16)}, ${parseInt(color.slice(-2), 16)}, ${percent}%)`;
    },
    textColorWithDarken = (color, percent = 75) => {
        if (!color) return 'rgb(180, 180, 180)';
        return `rgb(${parseInt(color.slice(-6, -4), 16) - percent}, ${parseInt(color.slice(-4, -2), 16) - percent}, ${parseInt(color.slice(-2), 16) - percent})`;
    };
</script>
