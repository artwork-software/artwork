<template>
    <div class="relative w-full">
        <ShiftHeader>

            <!-- topbar with date range selector -->
            <div class="card glassy p-4 w-full sticky top-0 z-40 !rounded-t-none">
                <div class="flex items-center px-5">
                    <date-picker-component :date-value-array="dateValue"/>
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
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-5">
                                <div class="card white p-5 text-xs">
                                    <div class="space-y-2" v-if="room.content[day.fullDay]?.events?.length > 0">
                                        <div v-for="event in room.content[day.fullDay]?.events" :key="event.id">
                                            <SingleEventInDailyShiftView :event="event" />
                                        </div>
                                    </div>
                                    <div v-else>
                                        <div class="text-gray-300 text-center">Keine Veranstaltungen für diesen Tag</div>
                                    </div>
                                    <div class="mt-5 border rounded-lg w-fit p-1 bg-gray-50 group hover:bg-gray-100 transition-all duration-150 ease-in-out cursor-pointer" @click="openNewEventModalWithBaseData(day.withoutFormat, room.roomId)">
                                        <component is="IconCalendarPlus" class="size-5 text-gray-500 group-hover:text-gray-700" />
                                    </div>
                                </div>
                                <div class="card white p-5 text-xs font-lexend">
                                    <div class="space-y-2" v-if="room.content[day.fullDay]?.shifts?.length > 0">
                                        <div v-for="shift in room.content[day.fullDay]?.shifts" :key="shift.id">
                                            <div class="grid grid-cols-1 md:grid-cols-2 mb-2 bg-gray-100 w-full rounded-lg">
                                                <div class="flex items-center gap-x-2">
                                                    <div class="bg-gray-500 py-1.5 px-2 rounded-l-lg text-white">{{ shift.start }} - {{ shift.end }}</div>
                                                    <div class="text-gray-700 font-semibold">{{ shift.craft.abbreviation }}: {{ shift.craft.name }}</div>
                                                </div>
                                                <div class="flex justify-between items-center w-full px-3">
                                                    <div class="flex items-center gap-x-2">
                                                        <div v-for="qualification in shift.shifts_qualifications">
                                                            <div class="text-gray-500 text-[10px] flex items-center gap-x-1 group hover:bg-gray-50 p-1 rounded-lg transition-all duration-150 ease-in-out cursor-pointer hover:text-artwork-buttons-create">
                                                                <component :is="findShiftQualification(qualification.shift_qualification_id)?.icon" class="size-3" />
                                                                <div>
                                                                    0/{{ qualification.value }}
                                                                </div>
                                                                {{ findShiftQualification(qualification.shift_qualification_id)?.name || 'Unbekannte Qualifikation' }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center justify-end px-3">
                                                        <component is="IconChevronDown" class="size-5 text-gray-500 hover:text-gray-700 cursor-pointer transition-all duration-150 ease-in-out" />
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                    <div v-else>
                                        <div class="text-gray-300 text-center">Keine Schichten für diesen Tag</div>
                                    </div>
                                    <div class="mt-5 border rounded-lg w-fit p-1 bg-gray-50 group hover:bg-gray-100 transition-all duration-150 ease-in-out cursor-pointer" @click="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)">
                                        <component is="IconCalendarUser" class="size-5 text-gray-500 group-hover:text-gray-700" />
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>


            <pre>
                {{ shiftPlan }}
            </pre>

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
import {usePage} from "@inertiajs/vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {can} from "laravel-permission-to-vuejs";

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
provide('event_properties', props.event_properties)
const findShiftQualification = (qualificationId) => {
    return props.shiftQualifications.find(q => q.id === qualificationId);
}

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

</script>

<style scoped>

</style>