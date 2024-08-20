<template>
    <div :style="{ height: zoom_factor * 115 + 'px', width: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px', minWidth: zoom_factor === 0.2 ? '50px' : zoom_factor * 90 + 'px' }" :class="isFullscreen ? 'stickyDaysNoMarginLeft' : 'stickyDays'" class="bg-userBg text-secondary text-right">
        <div :style="textStyle" class="mt-3 mr-2">
            <div>
                {{ zoom_factor >= 0.8 ? day.day_string : '' }}
            </div>
            <div>
                {{ zoom_factor >= 0.8 ? day.full_day : day.short_day }}
            </div>
            <div v-if="day.is_monday" class="text-[10px] font-normal ml-2">(KW{{ day.week_number }})</div>
        </div>
    </div>
</template>

<script setup>

import {computed, ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    day: {
        type: Object,
        required: true
    },
    isFullscreen: {
        type: Boolean,
        required: false,
        default: false
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
    align-self: flex-start;
    position: -webkit-sticky;
    left: 4rem;
    z-index: 22;
    background-color: #EDEDEC;
}

.stickyDaysNoMarginleft {
    position: sticky;
    align-self: flex-start;
    position: -webkit-sticky;
    left: 0;
    z-index: 22;
    background-color: #EDEDEC;
}

</style>
