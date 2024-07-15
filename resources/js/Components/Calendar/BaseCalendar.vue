<template>
    <div id="myCalendar" class="bg-white" :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class="-my-5 -mx-5 sticky top-0 z-40">
            <AsyncFunctionBarCalendar
                :multi-edit="multiEdit"
                @update-multi-edit="updateMultiEdit"
            />
        </div>

        <div class="pl-14 -mx-5 my-5 bg-gray-200">
            <div :class="project ? 'bg-lightBackgroundGray' : 'bg-white'" class="px-5">
                <!-- Calendar Header -->
                <AsyncCalendarHeader
                    :rooms="rooms"
                />

                <!-- Calendar Body -->
                <div class="divide-y divide-gray-200 divide-dashed">
                    <div v-for="day in days" :key="day.full_day" :style="{ height: zoom_factor * 115 + 'px' }" class="flex" :class="day.is_weekend ? 'bg-userBg/30' : ''">
                        <SingleDayInCalendar :day="day" />
                        <div v-for="room in calendarData" :key="room.id" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: zoom_factor * 115 + 'px' }" :class="[day.is_weekend ? '' : '', zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']">
                            <div class="py-0.5" v-for="event in room[day.full_day].events.data ?? room[day.full_day].events" :key="event.id">
                                <AsyncSingleEventInCalendar
                                    :event="event"
                                    :multi-edit="multiEdit"
                                    :font-size="textStyle.fontSize"
                                    :line-height="textStyle.lineHeight"
                                    :rooms="rooms"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <AsyncMultiEditBar
        :class="multiEdit ? 'opacity-100' : 'opacity-0'"
        class="ease-in-out duration-500 transition-all"
        @closeMultiEdit="updateMultiEdit(false)"
        :count-of-selected-events="checkedEventsForMultiEditCount"
    />

</template>

<script setup>

import FunctionBarCalendar from "@/Components/FunctionBars/FunctionBarCalendar.vue";
import {computed, defineAsyncComponent, ref, watch} from "vue";
import {usePage} from "@inertiajs/vue3";
import CalendarHeader from "@/Components/Calendar/Elements/CalendarHeader.vue";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";

const multiEdit = ref(false)
const isFullscreen = ref(false)
const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const checkedEventsForMultiEditCount = ref(0)

const props = defineProps({
    rooms: {
        type: Object,
        required: true
    },
    days: {
        type: Array,
        required: true
    },
    calendarData: {
        type: Object,
        required: true
    },
    project: {
        type: Object,
        default: null,
        required: false
    }
})

const AsyncMultiEditBar = defineAsyncComponent(() =>
    import('@/Components/Calendar/Elements/MultiEditBar.vue')
)

const AsyncSingleEventInCalendar = defineAsyncComponent(() =>
    import('@/Components/Calendar/Elements/SingleEventInCalendar.vue')
)

const AsyncFunctionBarCalendar = defineAsyncComponent(() =>
    import('@/Components/FunctionBars/FunctionBarCalendar.vue')
)

const AsyncCalendarHeader = defineAsyncComponent(() =>
    import('@/Components/Calendar/Elements/CalendarHeader.vue')
)

const updateMultiEdit = (value) => {
    multiEdit.value = value
}

const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return {
        fontSize,
        lineHeight,
    };
})

// watch for multiEdit. if false remove clicked Status form Events
watch(multiEdit, (value) => {
    if (!value) {
        props.calendarData.forEach(room => {
            props.days.forEach(day => {
                room[day.full_day].events.data.forEach(event => {
                    event.clicked = false
                })
            })
        })
    }
})

// watch for count of selected Events
watch(() => props.calendarData, (value) => {
    let count = 0
    value.forEach(room => {
        props.days.forEach(day => {
            room[day.full_day].events.data.forEach(event => {
                if (event.clicked) {
                    count++
                }
            })
        })
    })
    checkedEventsForMultiEditCount.value = count
}, {deep: true})

</script>

<style scoped>
.cell {
    overflow: overlay;
}
</style>




