<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white max-h-screen" :class="isFullscreen ? 'overflow-y-auto' : ''">
        <div class="-my-5 -mx-5 sticky top-0 z-40">
            <AsyncFunctionBarCalendar
                :multi-edit="multiEdit"
                @update-multi-edit="updateMultiEdit"
                :rooms="rooms"
            />

            <div class="flex justify-center w-full bg-white" :class="filteredEvents?.length ? 'mt-5' : ''" v-if="filteredEvents?.length > 0">
                <div class="flex errorText items-center cursor-pointer mb-2"
                     @click="showEventsWithoutRoomComponent = true"
                     v-if="filteredEvents?.length > 0"
                >
                    <IconAlertTriangle class="h-6  mr-2"/>
                    {{ filteredEvents?.length === 1 ? $t('{0} Event without room!', [filteredEvents?.length]) : $t('{0} Events without room!', [filteredEvents?.length]) }}
                </div>
            </div>
        </div>

        <div class="pl-14 -mx-5 my-5 overflow-auto h-[90vh]">
            <div :class="project ? 'bg-lightBackgroundGray' : 'bg-white'" class="px-5">
                <!-- Calendar Header -->
                <AsyncCalendarHeader
                    class=""
                    :rooms="rooms"
                />

                <!-- Calendar Body -->
                <div class="divide-y divide-gray-200 divide-dashed" ref="calendarToCalculate">
                    <div v-for="day in days" :key="day.full_day" :style="{ height: zoom_factor * 115 + 'px' }" class="flex gap-0.5 day-container" :class="day.is_weekend ? 'bg-userBg/30' : ''" :data-day="day.full_day">
                        <SingleDayInCalendar :day="day" />
                        <div
                            v-for="room in calendarData"
                            :key="room.id"
                            :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', height: zoom_factor * 115 + 'px' }"
                            :class="[day.is_weekend ? '' : '', zoom_factor > 0.4 ? 'cell' : 'overflow-hidden']"
                            class="group/container"
                        >
                            <template v-if="currentDaysInView.has(day.full_day)">
                                <div class="py-0.5" v-for="event in room[day.full_day].events.data" :key="event.id">
                                    <AsyncSingleEventInCalendar
                                        :event="event"
                                        :multi-edit="multiEdit"
                                        :font-size="textStyle.fontSize"
                                        :line-height="textStyle.lineHeight"
                                        :rooms="rooms"
                                        :has-admin-role="hasAdminRole()"
                                        :width="zoom_factor * 204"
                                    />
                                </div>
                            </template>
                            <div class="hidden group-hover/container:block" v-if="addInCalendarButton">
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


    <div class="fixed bottom-0 w-full h-28 bg-artwork-navigation-background/30 z-100 pointer-events-none" v-if="multiEdit">
        <div class="flex items-center justify-center h-full gap-4">
            <div>
                <FormButton :disabled="checkedEventsForMultiEditCount === 0" :text="checkedEventsForMultiEditCount + ' Termin(e) verschieben'" class="transition-all duration-300 ease-in-out pointer-events-auto"/>
            </div>
            <div>
                <FormButton class="bg-artwork-messages-error hover:bg-artwork-messages-error/70 transition-all duration-300 ease-in-out pointer-events-auto" @click="openDeleteSelectedEventsModal = true" :disabled="checkedEventsForMultiEditCount === 0" :text="checkedEventsForMultiEditCount + ' ' + $t('Delete events')"/>
            </div>
        </div>

    </div>


    <ConfirmDeleteModal
        v-if="openDeleteSelectedEventsModal"
        @closed="openDeleteSelectedEventsModal = false"
        @delete="deleteSelectedEvents"
        :title="$t('Delete assignments')"
        :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"/>

    <MultiEditModal :checked-events="editEvents" v-if="showMultiEditModal" :rooms="rooms"
                    @closed="closeMultiEditModal"/>


    <events-without-room-component
        v-if="showEventsWithoutRoomComponent"
        @closed="showEventsWithoutRoomComponent = false"
        :showHints="usePage().props.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :eventsWithoutRoom="filteredEvents"
        :isAdmin="hasAdminRole()"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
    />
</template>
<script setup>
import {computed, defineAsyncComponent, inject, onMounted, ref, watch} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import SingleDayInCalendar from "@/Components/Calendar/Elements/SingleDayInCalendar.vue";
import MultiEditModal from "@/Layouts/Components/MultiEditModal.vue";
import {usePermissions} from "@/Composeables/usePermissions.js";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {IconAlertTriangle} from "@tabler/icons-vue";
import EventsWithoutRoomComponent from "@/Layouts/Components/EventsWithoutRoomComponent.vue";

const { hasAdminRole } = usePermissions(usePage().props);

const first_project_calendar_tab_id = inject('first_project_calendar_tab_id');
const eventTypes = inject('eventTypes');

const multiEdit = ref(false);
const isFullscreen = ref(false);
const zoom_factor = ref(usePage().props.user.zoom_factor ?? 1);
const checkedEventsForMultiEditCount = ref(0);
const showMultiEditModal = ref(false);
const editEvents = ref([]);
const addInCalendarButton = ref(false);
const openDeleteSelectedEventsModal = ref(false);
const showEventsWithoutRoomComponent = ref(false);

const currentDaysInView = ref(new Set());

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
    eventsWithoutRoom: {
        type: Object,
        required: false,
    },
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


const filteredEvents = computed(() => {
    return props.eventsWithoutRoom?.filter((event) => {
        let createdBy = event.created_by;
        let projectLeaders = event.projectLeaders;

        if (projectLeaders && projectLeaders.length > 0) {
            if (createdBy.id === usePage().props.user.id || projectLeaders.some((leader) => leader.id === usePage().props.user.id)) {
                return true;
            }
        } else if (createdBy.id === usePage().props.user.id) {
            return true;
        }

        return false;
    });
})

const closeMultiEditModal = () => {
    removeCheckedState();
    showMultiEditModal.value = false;
};

const removeCheckedState = () => {
    props.calendarData.forEach((room) => {
        props.days.forEach((day) => {
            room[day.full_day].events.data?.forEach((event) => {
                event.clicked = false;
            });
        });
    });
};

// TODO: Add Partial Reload of Calendar
const deleteSelectedEvents = () => {
    router.post(route('multi-edit.delete'), {
        events: editEvents.value
    }, {
        onSuccess: () => {
            openDeleteSelectedEventsModal.value = false
            multiEdit.value = false;
        }
    })
}

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
                room[day.full_day].events.data?.forEach((event) => {
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

onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            const day = entry.target.dataset.day;
            if (entry.isIntersecting) {
                currentDaysInView.value.add(day);
            } else {
                currentDaysInView.value.delete(day);
            }
        });
    });

    const dayContainers = document.querySelectorAll('.day-container');
    dayContainers.forEach((container) => {
        observer.observe(container);
    });
});
</script>

<style scoped>
.cell {
    overflow: overlay;
}
</style>




