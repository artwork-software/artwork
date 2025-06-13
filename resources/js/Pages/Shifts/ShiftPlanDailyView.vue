<template>
    <div class="relative w-full">
        <ShiftHeader>

            <!-- topbar with date range selector -->
            <div class="card glassy p-4 bg-white/50 w-full sticky top-0 z-40 !rounded-t-none">
                <div class="flex items-center px-5 gap-x-5">
                    <date-picker-component :date-value-array="dateValue" :is_shift_plan="true"/>

                    <Switch @click="changeDailyViewMode" v-model="dailyViewMode" :class="[dailyViewMode ? 'bg-artwork-buttons-hover' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                        <span :class="[dailyViewMode ? 'translate-x-5' : 'translate-x-0', 'inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                            <span :class="[dailyViewMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-40', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                <ToolTipComponent
                                    icon="IconCalendarWeek"
                                    icon-size="h-4 w-4"
                                    :tooltip-text="$t('Daily view')"
                                    direction="bottom"
                                />
                            </span>
                            <span :class="[dailyViewMode ? 'opacity-100 duration-200 ease-in z-40' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                <ToolTipComponent
                                    icon="IconCalendarWeek"
                                    icon-size="h-4 w-4"
                                    :tooltip-text="$t('Daily view')"
                                    direction="bottom"
                                />
                            </span>
                        </span>
                    </Switch>
                </div>
            </div>


            <div v-for="day in days" :key="day.withoutFormat" class="flex flex-col w-full h-full relative">
                <!-- tages balken -->
                <div class="flex items-center w-full bg-artwork-navigation-background text-white sticky top-[72px] z-30">
                    <div class="px-16 font-lexend text-sm py-4">
                        {{ day.dayString }}, {{ day.fullDay }}
                    </div>
                </div>
                <div class="grid grid-cols-[3rem_1fr]" v-for="room in shiftPlan">
                    <div class="flex flex-col-reverse items-center justify-between bg-artwork-navigation-background text-white py-4">
                        <!-- Raumnamen von unten nach oben -->
                        <div :key="room.roomName" class="text-xs font-bold font-lexend -rotate-90 h-full flex items-center text-center justify-center py-4">
                            {{ room.roomName }}
                        </div>
                    </div>
                    <div class="flex items-stretch px-4 py-2">
                        <div class="card glassy p-4">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-5">
                                <div class="card white p-5 text-xs col-span-1">
                                    <div class="space-y-2" v-if="room.content[day.fullDay]?.events?.length > 0">
                                        <div v-for="event in room.content[day.fullDay]?.events" :key="event.id">
                                            <SingleEventInDailyShiftView :event="event" />
                                        </div>
                                    </div>
                                    <div v-else>
                                        <div class="text-gray-300 text-center">Keine Veranstaltungen für diesen Tag</div>
                                    </div>
                                    <div class="mt-5">
                                        <GlassyIconButton text="Add Event" icon="IconCalendarPlus" @click="openNewEventModalWithBaseData(day.withoutFormat, room.roomId)" />
                                    </div>
                                </div>
                                <div class="card white p-5 text-xs font-lexend col-span-2">
                                    <div class="space-y-2" v-if="room.content[day.fullDay]?.shifts?.length > 0">
                                        <div v-for="shift in room.content[day.fullDay]?.shifts" :key="shift.id">
                                            <SingleShiftInDailyShiftView :shift="shift" :shift-qualifications="shiftQualifications" :crafts="crafts"/>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <div class="text-gray-300 text-center">Keine Schichten für diesen Tag</div>
                                    </div>
                                    <div class="mt-5">
                                        <GlassyIconButton text="Add Shift" icon="IconCalendarUser" @click="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <AddShiftModal
                v-if="showAddShiftModal"
                :crafts="crafts"
                :event="null"
                :shift="shiftToEdit"
                :currentUserCrafts="usePage().props.currentUserCrafts"
                :buffer="null"
                :shift-qualifications="usePage().props.shiftQualifications"
                @closed="closeAddShiftModal"
                :shift-time-presets="usePage().props.shiftTimePresets"
                :room="roomForShiftAdd"
                :day="dayForShiftAdd"
                :shift-plan-modal="true"
                :edit="shiftToEdit !== null"
            />

            <EventComponent
                v-if="showEventComponent"
                :showHints="usePage().props.show_hints"
                :eventTypes="eventTypes"
                :rooms="rooms"
                :calendarProjectPeriod="usePage().props.auth.user.calendar_settings.use_project_time_period"
                :project="null"
                :event="eventToEdit"
                :wantedRoomId="wantedRoom"
                :isAdmin="can('artwork admin')"
                :roomCollisions="roomCollisions"
                :first_project_calendar_tab_id="first_project_calendar_tab_id"
                :requires-axios-requests="true"
                @closed="eventComponentClosed"
                :event-statuses="eventStatuses"
                :is-planning="isPlanning"
                :wanted-date="wantedDate"

            />
            
        </ShiftHeader>

    </div>
</template>
<script setup>

import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import SingleEventInDailyShiftView from "@/Pages/Shifts/DailyViewComponents/SingleEventInDailyShiftView.vue";
import {ref, provide} from "vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import {router, usePage} from "@inertiajs/vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {can} from "laravel-permission-to-vuejs";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import {Switch} from "@headlessui/vue";
import SingleShiftInDailyShiftView from "@/Pages/Shifts/DailyViewComponents/SingleShiftInDailyShiftView.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";

const props = defineProps({
    days: {
        type: Array,
        required: true,
        default: () => []
    },
    dateValue: {
        type: Array,
        required: true,
        default: () => []
    },
    shiftPlan: {
        type: Object,
        required: true,
        default: () => ({})
    },
    shiftQualifications: {
        type: Object,
        required: true,
        default: () => ({})
    },
    crafts: {
        type: Object,
        required: true,
        default: () => ({})
    },
    rooms: {
        type: Object,
        required: true
    },
    eventStatuses: {
        type: Object,
        required: true
    },
    eventTypes: {
        type: Object,
        required: true
    },
    event_properties: {
        type: Object,
        required: true
    },
    first_project_calendar_tab_id: {
        type: Number,
        required: true
    },
})

const shiftToEdit = ref(null);
const roomForShiftAdd = ref(null);
const dayForShiftAdd = ref(null);
const showAddShiftModal = ref(false);
const eventToEdit = ref(false);
const wantedRoom = ref(null);
const wantedDate = ref(null);
const showEventComponent = ref(false);
const isPlanning = ref(false);
const roomCollisions = ref([]);
const dailyViewMode = ref(usePage().props.auth.user.daily_view ?? false);
provide('event_properties', props.event_properties)
const openAddShiftForRoomAndDay = (day, roomId) => {
    shiftToEdit.value = null;
    roomForShiftAdd.value = roomId;
    dayForShiftAdd.value = day;
    showAddShiftModal.value = true;
}

const closeAddShiftModal = () => {
    showAddShiftModal.value = false;
    shiftToEdit.value = null;
    roomForShiftAdd.value = null;
    dayForShiftAdd.value = null;
}

const openNewEventModalWithBaseData = (day, roomId) => {
    eventToEdit.value = false
    wantedRoom.value = roomId;
    wantedDate.value = day;
    showEventComponent.value = true;
};

const eventComponentClosed = () => {
    showEventComponent.value = false;
    eventToEdit.value = false;
    wantedRoom.value = null;
    wantedDate.value = null;
};

const  changeDailyViewMode = () => {
    dailyViewMode.value = !dailyViewMode.value;
    router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
        daily_view: dailyViewMode.value
    }, {
        preserveScroll: false,
        preserveState: false
    })
};

</script>

<style scoped>

</style>