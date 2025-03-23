<template>
    <div :style="{ height: usePage().props.user.calendar_settings.expand_days ? '' : zoom_factor * 115 + 'px', width: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px', minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px' }" :class="[isFullscreen ? 'stickyDaysNoMarginLeft' : 'stickyDays', hour ? '!bg-gray-200' : '']" class=" text-calendarText text-right bg-gray-200">
        <div :style="textStyle" class="mt-3 mr-2" v-if="day">
            <div>
                {{ zoom_factor >= 0.8 ? day.dayString : '' }}
            </div>
            <div>
                {{ zoom_factor >= 0.8 ? day.fullDayDisplay : day.shortDay }}
            </div>
            <div v-if="day.isMonday" class="text-[10px] font-normal ml-2">(KW{{ day.weekNumber }})</div>
            <HolidayToolTip v-if="day?.holidays?.length > 0" class="mt-2">
                <div class="space-y-1 divide-dashed divide-gray-500 divide-y">
                    <div v-for="holiday in day.holidays" class="pt-1">
                        <div :style="{ color: holiday.color}">
                            <div>{{ holiday.name }}</div>
                            <div v-if="holiday.subdivisions.length > 0">
                                {{ holiday.subdivisions.map((person) => person).join(', ') }}
                            </div>
                            <div v-else>
                                {{ $t('Germany-wide') }}
                            </div>
                        </div>
                    </div>
                </div>
            </HolidayToolTip>
        </div>
        <div :style="textStyle" class="mt-3 mr-2" v-else>
            <div class="" :class="zoom_factor < 0.6 ? 'xxsDark' : 'xsDark'">
                {{ hour }}
            </div>
        </div>
    </div>
</template>

<script setup>

import {computed, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";

const props = defineProps({
    day: {
        type: Object,
        required: false,
        default: null
    },
    isFullscreen: {
        type: Boolean,
        required: false,
        default: false
    },
    hour: {
        type: String,
        required: false,
        default: null
    }
})

const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);

const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return {
        fontSize,
        lineHeight,
    };
})



</script>

<style scoped>
.stickyDays {
    position: sticky;
    position: -webkit-sticky;
    left: 4rem;
    z-index: 22;
}

.stickyDaysNoMarginleft {
    position: sticky;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
}

</style>
