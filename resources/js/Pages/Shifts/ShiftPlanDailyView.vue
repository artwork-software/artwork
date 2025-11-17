<template>
    <div class="relative w-full">
        <ShiftHeader>
            <transition name="fade" appear>
                <div class="pointer-events-none fixed z-100 inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8" v-show="showCalendarWarning.length > 0">
                    <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                        <component :is="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true" />
                        <p class="text-sm/6 text-white">
                            {{ showCalendarWarning }}
                        </p>
                        <button type="button" class="-m-1.5 flex-none p-1.5">
                            <span class="sr-only">{{ $t('Dismiss') }}</span>
                            <component :is="IconX" class="size-5 text-white" aria-hidden="true" @click="showCalendarWarning = ''" />
                        </button>
                    </div>
                </div>
            </transition>
            <!-- topbar with date range selector -->
            <div class="card glassy p-4 bg-white/50 w-full sticky top-0 z-40 !rounded-t-none">
                <div class="flex items-center pr-5 gap-x-5 justify-between">
                    <div class="flex items-center gap-x-4">
                        <!-- Date Shortcuts - 3 vertical icons -->
                        <date-picker-component :date-value-array="dateValue" :is_shift_plan="true"/>

                        <div class="flex gap-x-1 mx-2">
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="$t('Today')"
                                :icon="IconCalendar"
                                icon-size="h-5 w-5"
                                @click="jumpToToday"
                                classesButton="ui-button"
                            />
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="$t('Current week')"
                                :icon="IconCalendarWeek"
                                icon-size="h-5 w-5"
                                @click="jumpToCurrentWeek"
                                classesButton="ui-button"
                            />
                            <ToolTipComponent
                                direction="right"
                                :tooltip-text="$t('Current month')"
                                :icon="IconCalendarMonth"
                                icon-size="h-5 w-5"
                                @click="jumpToCurrentMonth"
                                classesButton="ui-button"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-x-5 ">
                        <SwitchIconTooltip
                            v-model="dailyViewMode"
                            :tooltip-text="$t('Daily view')"
                            size="md"
                            @change="changeDailyViewMode"
                            :icon="IconCalendarWeek"
                        />
                        <!--<ShiftPlanFilter
                            :filter-options="filterOptions"
                            :personal-filters="personalFilters"
                            :user_filters="user_filters"
                            :crafts="crafts"
                        />-->
                        <FunctionBarFilter
                            :user_filters="user_filters"
                            :personal-filters="personalFilters"
                            :filter-options="filterOptions"
                            :crafts="crafts"
                            filter-type="shift_filter"
                        />

                        <FunctionBarSetting :is-planning="false" is-in-shift-plan />
                    </div>
                </div>
            </div>


            <div v-for="day in days" :key="day.withoutFormat" class="flex flex-col w-full h-full relative ml-1">
                <!-- tages balken -->
                <div v-if="!day.isExtraRow">
                    <div class="flex items-center justify-center w-full bg-artwork-navigation-background text-white sticky ml-1 top-[72px] z-30">
                        <div class="px-16 font-lexend text-sm font-bold py-4">
                            {{ day.dayString }}, {{ day.fullDay }}
                        </div>
                    </div>
                    <div class="grid grid-cols-[3rem_1fr] ml-1" v-for="room in shiftPlanCopy">
                        <div class="flex flex-col-reverse items-center justify-between bg-artwork-navigation-background text-white py-4 border-t-2 border-dashed">
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
                                                <SingleEventInDailyShiftView
                                                    :event="event"
                                                    :eventTypes="eventTypes"
                                                    :rooms="rooms"
                                                    :first_project_calendar_tab_id="first_project_calendar_tab_id"
                                                    :event-statuses="eventStatuses"
                                                />
                                            </div>
                                        </div>
                                        <div v-else>
                                            <div class="text-gray-300 text-center">{{ $t('No events for this day') }}</div>
                                        </div>
                                        <div class="mt-5">
                                            <BaseUIButton :label="$t('Add Event')" is-add-button :icon="IconCalendarPlus" @click="openNewEventModalWithBaseData(day.withoutFormat, room.roomId)" />
                                        </div>
                                    </div>
                                    <div class="card white p-5 text-xs font-lexend col-span-2">
                                        <div class="space-y-2" v-if="room.content[day.fullDay]?.shifts?.length > 0">
                                            <div v-for="shift in filterShiftsByCraft(room.content[day.fullDay]?.shifts)" :key="shift.id">
                                                <SingleShiftInDailyShiftView :shift="shift" :shift-qualifications="shiftQualifications" :first_project_calendar_tab_id="first_project_calendar_tab_id" :crafts="crafts"/>
                                            </div>
                                        </div>
                                        <div v-else>
                                            <div class="text-gray-300 text-center">{{ $t('No shifts for this day') }}</div>
                                        </div>
                                        <div class="mt-5">
                                            <BaseUIButton :label="$t('Add Shift')" is-add-button :icon="IconCalendarUser" @click="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)" />
                                        </div>
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
<script setup lang="ts">
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import SingleEventInDailyShiftView from "@/Pages/Shifts/DailyViewComponents/SingleEventInDailyShiftView.vue";
import { ref, provide, onMounted, watch } from "vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import { router, usePage } from "@inertiajs/vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import { can } from "laravel-permission-to-vuejs";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import SingleShiftInDailyShiftView from "@/Pages/Shifts/DailyViewComponents/SingleShiftInDailyShiftView.vue";
import {
    IconAlertSquareRounded,
    IconCalendar,
    IconCalendarWeek,
    IconCalendarMonth,
    IconX,
    IconCalendarPlus,
    IconCalendarUser
} from "@tabler/icons-vue";
import { useShiftCalendarListener } from "@/Composeables/Listener/useShiftCalendarListener.js";
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import FunctionBarSetting from "@/Artwork/Filter/FunctionBarSetting.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import axios from "axios";

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
        type: [Object, Array],
        required: true,
        default: () => ({})
    },
    shiftQualifications: {
        type: [Object, Array],
        required: true,
        default: () => ({})
    },
    crafts: {
        type: [Object, Array],
        required: true,
        default: () => ({})
    },
    rooms: {
        type: [Object, Array],
        required: true
    },
    eventStatuses: {
        type: [Object, Array],
        required: true
    },
    eventTypes: {
        type: [Object, Array],
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
    filterOptions: {
        type: Object,
        required: true,
        default: () => ({})
    },
    personalFilters: {
        type: Object,
        required: true,
        default: () => ({})
    },
    user_filters: {
        type: Object,
        required: true,
        default: () => ({})
    },
    calendarWarningText: {
        type: String,
        required: false,
        default: ''
    }
})

/**
 * Lokale State-Refs – damit wir per API nachladen können
 */
const showCalendarWarning = ref(props.calendarWarningText)

// Tage lokal spiegeln, damit wir sie überschreiben können
const days = ref<Array<any>>(props.days ?? [])

// shiftPlanCopy: intern verwendete Version des Schichtplans
const shiftPlanCopy = ref<any[]>(
    Array.isArray(props.shiftPlan)
        ? props.shiftPlan
        : Object.values(props.shiftPlan ?? {})
)

const shiftToEdit = ref(null);
const roomForShiftAdd = ref<number | null>(null);
const dayForShiftAdd = ref<string | null>(null);
const showAddShiftModal = ref(false);
const eventToEdit = ref<any | boolean>(false);
const wantedRoom = ref<number | null>(null);
const wantedDate = ref<string | null>(null);
const showEventComponent = ref(false);
const isPlanning = ref(false);
const roomCollisions = ref<any[]>([]);
const dailyViewMode = ref<boolean>(usePage().props.auth.user.daily_view ?? false);

provide('event_properties', props.event_properties)

/**
 * Initiales Laden des Tages-Schichtplans über API, wenn Props leer sind
 */
const initializeDailyShiftPlan = async () => {
    const hasInitialDays = Array.isArray(props.days) && props.days.length > 0
    const hasInitialShiftPlan =
        props.shiftPlan &&
        (Array.isArray(props.shiftPlan)
            ? props.shiftPlan.length > 0
            : Object.keys(props.shiftPlan).length > 0)

    if (!hasInitialDays || !hasInitialShiftPlan) {
        const { data } = await axios.get(route('shift.plan.all'), {
            params: {
                start_date: props.dateValue[0],
                end_date: props.dateValue[1],
                // falls du hier projektabhängig filtern willst, kannst du das anpassen:
                projectId: usePage().props.currentProject?.id ?? null
            }
        })

        days.value = data.days ?? []
        shiftPlanCopy.value = Array.isArray(data.shiftPlan)
            ? data.shiftPlan
            : Object.values(data.shiftPlan ?? {})
    } else {
        // Props in lokale Refs spiegeln
        days.value = [...(props.days ?? [])]
        shiftPlanCopy.value = Array.isArray(props.shiftPlan)
            ? [...props.shiftPlan]
            : Object.values(props.shiftPlan ?? {})
    }
}

/**
 * Reaktiv bleiben, wenn Inertia neue Days/ShiftPlan via Props liefert
 */
watch(
    () => props.days,
    (v) => {
        days.value = v ?? []
    },
    { deep: true }
)

watch(
    () => props.shiftPlan,
    (v) => {
        shiftPlanCopy.value = Array.isArray(v)
            ? [...v]
            : Object.values(v ?? {})
    },
    { deep: true }
)

/**
 * Modale / Aktionen
 */
const openAddShiftForRoomAndDay = (day: string, roomId: number) => {
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

const openNewEventModalWithBaseData = (day: string, roomId: number) => {
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

/**
 * Daily View Mode
 */
const changeDailyViewMode = () => {
    router.patch(
        route('user.update.daily_view', usePage().props.auth.user.id),
        { daily_view: dailyViewMode.value },
        {
            preserveScroll: false,
            preserveState: false
        }
    )
};

const filterShiftsByCraft = (shifts: any[]) => {
    if (!shifts) return [];

    if (props.user_filters.craft_ids && props.user_filters.craft_ids.length > 0) {
        return shifts.filter(shift => {
            return props.user_filters.craft_ids.includes(shift.craft?.id);
        });
    }

    return shifts;
};

const changeDailyViewModeValue = (newValue: boolean) => {
    dailyViewMode.value = newValue;
    router.patch(
        route('user.update.daily_view', usePage().props.auth.user.id),
        { daily_view: dailyViewMode.value },
        {
            preserveScroll: false,
            preserveState: false
        }
    );
};

/**
 * Shortcuts (Heute / aktuelle Woche / Monat)
 */
const jumpToToday = () => {
    const today = new Date().toISOString().slice(0, 10);

    if (!dailyViewMode.value) {
        changeDailyViewModeValue(true);
        setTimeout(() => {
            router.patch(
                route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id),
                { start_date: today, end_date: today },
                { preserveScroll: true, preserveState: false }
            );
        }, 100);
    } else {
        router.patch(
            route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id),
            { start_date: today, end_date: today },
            { preserveScroll: true, preserveState: false }
        );
    }
};

const jumpToCurrentWeek = () => {
    const today = new Date();
    const currentWeekStart = new Date(today);
    const currentWeekEnd = new Date(today);

    const dayOfWeek = today.getDay();
    const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1;
    currentWeekStart.setDate(today.getDate() - daysToMonday);

    const daysToSunday = dayOfWeek === 0 ? 0 : 7 - dayOfWeek;
    currentWeekEnd.setDate(today.getDate() + daysToSunday);

    router.patch(
        route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id),
        {
            start_date: currentWeekStart.toISOString().slice(0, 10),
            end_date: currentWeekEnd.toISOString().slice(0, 10),
        },
        {
            preserveScroll: true,
            preserveState: false
        }
    );
};

const jumpToCurrentMonth = () => {
    const today = new Date();
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    if (dailyViewMode.value) {
        changeDailyViewModeValue(false);
        setTimeout(() => {
            router.patch(
                route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id),
                {
                    start_date: monthStart.toISOString().slice(0, 10),
                    end_date: monthEnd.toISOString().slice(0, 10),
                },
                {
                    preserveScroll: true,
                    preserveState: false
                }
            );
        }, 100);
    } else {
        router.patch(
            route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id),
            {
                start_date: monthStart.toISOString().slice(0, 10),
                end_date: monthEnd.toISOString().slice(0, 10),
            },
            {
                preserveScroll: true,
                preserveState: false
            }
        );
    }
};

onMounted(async () => {
    setTimeout(() => {
        showCalendarWarning.value = ''
    }, 5000)

    // Schichtplan & Tage initial laden (falls leer)
    await initializeDailyShiftPlan()

    // Listener initialisieren – bekommt die ref auf shiftPlanCopy
    const ShiftCalendarListener = useShiftCalendarListener(shiftPlanCopy);
    ShiftCalendarListener.init();
})
</script>


<style scoped>

</style>
