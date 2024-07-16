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
                    <div v-for="day in days" :key="day.full_day" :style="{ height: zoom_factor * 115 + 'px' }" class="flex gap-0.5" :class="day.is_weekend ? 'bg-userBg/30' : ''">
                        <SingleDayInCalendar :day="day" />
                        <div
                            v-for="room in calendarData"
                            :key="room.id"
                            :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: zoom_factor * 115 + 'px' }"
                            :class="[day.is_weekend ? '' : '', zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                            class="group/container"
                        >
                            <div class="py-0.5" v-for="event in room[day.full_day].events.data ?? room[day.full_day].events" :key="event.id">
                                <!-- Lazy-load events using useIntersectionObserver -->
                                <AsyncSingleEventInCalendar

                                    :event="event"
                                    :multi-edit="multiEdit"
                                    :font-size="textStyle.fontSize"
                                    :line-height="textStyle.lineHeight"
                                    :rooms="rooms"
                                />
                            </div>
                            <div class="hidden group-hover/container:block">
                                <div class="px-1 py-0.5 bg-artwork-navigation-background/30 rounded-lg text-xs text-center cursor-pointer"
                                     :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px'}">
                                    Klick hier um ein Event zu erstellen
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <AsyncMultiEditBar
        :class="multiEdit ? 'opacity-100' : 'opacity-0'"
        class="ease-in-out duration-300 transition-all"
        @closeMultiEdit="updateMultiEdit(false)"
        :count-of-selected-events="checkedEventsForMultiEditCount"
    />

    <MultiEditModal :checked-events="editEvents" v-if="showMultiEditModal" :rooms="rooms"
                    @closed="closeMultiEditModal"/>
</template>

<script setup>
import { computed, defineAsyncComponent, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";

const multiEdit = ref(false);
const isFullscreen = ref(false);
const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const checkedEventsForMultiEditCount = ref(0);
const showMultiEditModal = ref(false);
const editEvents = ref([]);

const props = defineProps({
    rooms: {
        type: Object,
        required: true,
    },
    days: {
        type: Array,
        required: true,
    },
    calendarData: {
        type: Object,
        required: true,
    },
    project: {
        type: Object,
        default: null,
        required: false,
    },
});

const AsyncMultiEditBar = defineAsyncComponent({
    loader: () => import('@/Components/Calendar/Elements/MultiEditBar.vue'),
    delay: 500,
});

const AsyncSingleEventInCalendar = defineAsyncComponent({
    loader: () => import('@/Components/Calendar/Elements/SingleEventInCalendar.vue'),
    delay: 100,
});

const AsyncFunctionBarCalendar = defineAsyncComponent(() =>
    import('@/Components/FunctionBars/FunctionBarCalendar.vue')
);

const AsyncCalendarHeader = defineAsyncComponent(() =>
    import('@/Components/Calendar/Elements/CalendarHeader.vue')
);

const updateMultiEdit = (value) => {
    multiEdit.value = value;
};

const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return {
        fontSize,
        lineHeight,
    };
});

const closeMultiEditModal = () => {
    removeCheckedState();
    showMultiEditModal.value = false;
};

const removeCheckedState = () => {
    props.calendarData.forEach((room) => {
        props.days.forEach((day) => {
            room[day.full_day].events.data.forEach((event) => {
                event.clicked = false;
            });
        });
    });
};

watch(multiEdit, (value) => {
    if (!value) {
        editEvents.value = [];
        removeCheckedState();
    }
});

watch(
    () => props.calendarData,
    (value) => {
        let count = 0;
        value.forEach((room) => {
            props.days.forEach((day) => {
                room[day.full_day].events.data.forEach((event) => {
                    if (event.clicked) {
                        count++;
                        if (!editEvents.value.includes(event.id)) {
                            editEvents.value.push(event.id);
                        }
                    } else {
                        editEvents.value = editEvents.value.filter((id) => id !== event.id);
                    }
                });
            });
        });
        checkedEventsForMultiEditCount.value = count;
    },
    { deep: true }
);
</script>


<style scoped>
.cell {
    overflow: overlay;
}
</style>




