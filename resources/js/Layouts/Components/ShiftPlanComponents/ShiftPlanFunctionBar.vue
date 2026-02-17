<template>
    <div class="bg-white border-b border-zinc-200 shadow-sm py-2 sticky top-0 z-50 ">
        <div class="flex justify-between items-center mt-2 mb-2 px-5">
            <div class="inline-flex items-center">
                <div v-if="!isCalendarUsingProjectTimePeriod" class="flex">
                    <!-- Date Shortcuts - 3 vertical icons -->
                    <DatePickerComponent v-if="dateValue" :dateValueArray="dateValue"
                                           :is_shift_plan="true"></DatePickerComponent>
                    <div class="flex gap-x-1 mx-2">
                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="$t('Today')"
                            icon="IconCalendar"
                            icon-size="h-5 w-5"
                            @click="jumpToToday"
                            classesButton="ui-button"
                        />
                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="$t('Current week')"
                            icon="IconCalendarWeek"
                            icon-size="h-5 w-5"
                            @click="jumpToCurrentWeek"
                            classesButton="ui-button"
                        />
                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="$t('Current month')"
                            icon="IconCalendarMonth"
                            icon-size="h-5 w-5"
                            @click="jumpToCurrentMonth"
                            classesButton="ui-button"
                        />
                    </div>
                    <div class="flex items-center mx-4 gap-x-1 select-none">
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="$t('Previous time range')"
                            icon="IconChevronLeftPipe"
                            icon-size="h-7 w-7"
                            @click="previousTimeRange"
                        />
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="scrollBackTooltip"
                            icon="IconChevronLeft"
                            icon-size="h-7 w-7"
                            @click="scrollToPreviousDay"
                        />
                        <Menu as="div" class="relative inline-block text-left">
                            <div class="flex items-center">
                                <MenuButton class="">
                                    <ToolTipComponent
                                        direction="bottom"
                                        :tooltip-text="$t('Change scroll mode')"
                                        :icon="userGotoMode === 'month' ? 'IconCalendarMonth' : (userGotoMode === 'week' ? 'IconCalendarWeek' : 'IconCalendar')"
                                        icon-size="h-5 w-5"
                                    />
                                </MenuButton>
                            </div>

                            <transition enter-active-class="transition-enter-active"
                                        enter-from-class="transition-enter-from"
                                        enter-to-class="transition-enter-to"
                                        leave-active-class="transition-leave-active"
                                        leave-from-class="transition-leave-from"
                                        leave-to-class="transition-leave-to">
                                <MenuItems
                                    class="absolute right-0 z-50 mt-2 w-fit origin-top-right rounded-md bg-artwork-navigation-background shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('day')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Day')"
                                                    icon="IconCalendar"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('week')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Calendar week')"
                                                    icon="IconCalendarWeek"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('month')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Month')"
                                                    icon="IconCalendarMonth"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                    </div>
                                </MenuItems>
                            </transition>
                        </Menu>
                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="scrollForwardTooltip"
                            icon="IconChevronRight"
                            icon-size="h-7 w-7"
                            @click="scrollToNextDay"
                        />

                        <ToolTipComponent
                            direction="bottom"
                            :tooltip-text="$t('Next time range')"
                            icon="IconChevronRightPipe"
                            icon-size="h-7 w-7"
                            @click="nextTimeRange"
                        />
                    </div>
                    <div class="items-center hidden">
                        <div class="flex items-center">
                            <button class="ml-2 text-black" @click="previousTimeRange">
                                <PropertyIcon name="IconChevronLeft" stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"/>
                            </button>
                            <button class="ml-2  text-black" @click="nextTimeRange">
                                <PropertyIcon name="IconChevronRight" stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"/>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="relative mr-2">
                    <BaseInput
                        id="shiftPlanProjectSearch"
                        v-model="projectSearch"
                        :no-margin-top="true"
                        :is-small="true"
                        ref="projectSearchInput"
                        is-small
                        label="Search project"
                    />
                    <div v-if="projectSearchResults.length > 0"
                         class="absolute translate-y-1 bg-primary truncate sm:text-sm min-w-48 rounded-lg z-50">
                        <div v-for="(project, index) in projectSearchResults"
                             :key="index"
                             @click="toggleProjectTimePeriodAndRedirect(project.id, true)"
                             class="p-4 xsWhiteBold border-l-4 hover:border-l-success border-l-primary cursor-pointer">
                            {{ project.name }}
                        </div>
                    </div>
                </div>

                <div v-if="isCalendarUsingProjectTimePeriod && getTimePeriodProjectId() > 0" class="text-sm ml-4">
                    {{ $t('Project period') }}:
                    <Link
                        :href="route('projects.tab', {projectTab: firstProjectShiftTabId, project: getTimePeriodProjectId()})"
                        class="font-bold">
                        {{ projectNameUsedForProjectTimePeriod }}
                    </Link>
                    <template v-if="dateValue[0] && dateValue[1]">
                        &nbsp;- {{ formatDateStringToGermanFormat(dateValue[0]) }} -
                        {{ formatDateStringToGermanFormat(dateValue[1]) }}
                    </template>
                </div>

                <div class=" mr-2">
                    <SwitchIconTooltip
                        v-model="usePage().props.auth.user.calendar_settings.use_project_time_period"
                        :tooltip-text="$t('Project search')"
                        size="md"
                        @change="handleUseTimePeriodChange"
                        icon="IconGeometry"
                    />
                </div>

            </div>
            <slot name="multiEditCalendar"/>

            <div class="flex items-center">
                <div class="flex items-center gap-x-3">
                    <slot name="moreButtons">

                    </slot>
                    <!--<ToolTipComponent direction="bottom" :tooltip-text="$t('Display Settings')" icon="IconSettings" icon-size="h-7 w-7"
                                      @click="showCalendarSettingsModal = true"/>-->

                    <FunctionBarSetting :is-planning="false" is-in-shift-plan />

                    <!--<ToolTipComponent  direction="bottom"
                                       :tooltip-text="$t('Filter')"
                                       icon="IconFilter"
                                       icon-size="h-7 w-7"
                                      @click="showCalendarFilterModal = true"/>-->

                    <FunctionBarFilter
                        :user_filters="user_filters"
                        :personal-filters="personalFilters"
                        :filter-options="filterOptions"
                        :crafts="crafts"
                        filter-type="shift_filter"
                    />

                    <ToolTipComponent v-if="can('can commit shifts') || hasAdminRole()" direction="bottom"
                                      :tooltip-text="$t('Lock all shifts')" icon="IconCalendarCheck" icon-size="h-5 w-5" classes-button="ui-button"
                                      @click="commitAllShifts()"/>

                    <ToolTipComponent direction="bottom" :tooltip-text="$t('History')" icon="IconHistory"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="openHistoryModal()"/>
                    <ToolTipComponent direction="bottom" :tooltip-text="isFullscreen ? $t('Exit full screen') : $t('Full screen')"
                                      :icon="isFullscreen ? 'IconArrowsDiagonalMinimize' : 'IconArrowsDiagonal'"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="enterFullscreenMode"/>

                    <ToolTipComponent direction="bottom" :tooltip-text="$t('Subscribe to shift calendar')" icon="IconCalendarStar"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="showCalendarAboSettingModal = true"/>
                    <!--<ShiftPlanFilter
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :user_filters="user_filters"
                        :crafts="crafts"
                    />-->
                </div>
            </div>
        </div>
    </div>
    <div class="mb-1 ml-4 flex items-center w-full">
        <BaseFilterTag v-for="activeFilter in activeFilters" :filter="activeFilter" @removeFilter="removeFilter"/>
    </div>
    <ConfirmDeleteModal
        v-if="showConfirmCommitModal"
        @closed="showConfirmCommitModal = false"
        @delete="commitAllShifts"
        :title="$t('Fixed Shiftplan')"
        :description="$t('Are you sure you want to set the shift plan?')"
        :button="$t('Fixing')"
    />

    <ShiftCommitDateSelectModal
        :date-array="dateValue"
        :crafts="crafts"
        v-if="showShiftCommitDateSelectModal"
        @close="showShiftCommitDateSelectModal = false"

    />

    <CalendarSettingsModal
        v-if="showCalendarSettingsModal"
        @close="showCalendarSettingsModal = false"
        :is-planning="false"
        in-shift-plan
    />

    <CalendarAboSettingModal v-if="showCalendarAboSettingModal" @close="closeCalendarAboSettingModal" :crafts="crafts"/>
    <CalendarAboInfoModal v-if="showCalendarAboInfoModal" @close="showCalendarAboInfoModal = false" is_shift_calendar_abo />

</template>

<script setup>
import Button from "@/Jetstream/Button.vue";
import {
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
} from "@headlessui/vue";

import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router, useForm, Link, usePage} from "@inertiajs/vue3";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {ref, computed, watch, nextTick, defineAsyncComponent} from 'vue';
import {useI18n} from "vue-i18n";
const {t: $t} = useI18n();
import axios from 'axios';
import {usePermission} from "@/Composeables/Permission.js";
import ShiftCommitDateSelectModal from "@/Pages/Shifts/Components/ShiftCommitDateSelectModal.vue";
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import FunctionBarSetting from "@/Artwork/Filter/FunctionBarSetting.vue";
import CalendarAboSettingModal from "@/Pages/Shifts/Components/CalendarAboSettingModal.vue";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
const {hasAdminRole, can} = usePermission(usePage().props);

const DatePickerComponent = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/DatePickerComponent.vue'),
    delay: 200,
    timeout: 3000,
})

const props = defineProps({
    dateValue: Array,
    isFullscreen: Boolean,
    filterOptions: Object,
    allShiftsCommitted: Boolean,
    personalFilters: Array,
    rooms: Object,
    user_filters: Object,
    crafts: Array,
    projectNameUsedForProjectTimePeriod: String,
    firstProjectShiftTabId: [Number, String],
    eventTypes: Array
});

const emit = defineEmits(['enterFullscreenMode', 'previousTimeRange', 'nextTimeRange', 'openHistoryModal', 'selectGoToNextMode', 'selectGoToPreviousMode']);

// Data properties
const showConfirmCommitModal = ref(false);
const showShiftCommitDateSelectModal = ref(false);
const showCalendarSettingsModal = ref(false);
const showCalendarAboInfoModal = ref(false);
const showCalendarAboSettingModal = ref(false);
const projectSearch = ref('');
const projectSearchResults = ref([]);
const userCalendarSettings = useForm({
    show_qualifications: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_qualifications : false,
    shift_notes: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.shift_notes : false,
    high_contrast: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.high_contrast : false,
    expand_days: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.expand_days : false,
    display_project_groups: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.display_project_groups : false,
});

const CalendarSettingsModal = defineAsyncComponent({
    loader: () => import('@/Artwork/Modals/CalendarSettingsModal.vue'),
    delay: 200,
    timeout: 3000,
})

// Computed properties
const activeFilters = computed(() => {
    let activeFiltersArray = [];
    props.filterOptions.room_ids.forEach((room) => {
        if (props.user_filters.rooms?.includes(room.id)) {
            activeFiltersArray.push(room);
        }
    });

    props.filterOptions.event_type_ids.forEach((eventType) => {
        if (props.user_filters.event_types?.includes(eventType.id)) {
            activeFiltersArray.push(eventType);
        }
    });
    return activeFiltersArray;
});

const isCalendarUsingProjectTimePeriod = computed(() => {
    return usePage().props.auth.user.calendar_settings.use_project_time_period;
});

const userGotoMode = computed(() => {
    return usePage().props.auth.user.goto_mode;
});

const scrollBackTooltip = computed(() => {
    const mode = userGotoMode.value;
    if (mode === 'day') return $t('Scroll back by day');
    if (mode === 'week') return $t('Scroll back by week');
    if (mode === 'month') return $t('Scroll back by month');
    return $t('Scroll back by day');
});

const scrollForwardTooltip = computed(() => {
    const mode = userGotoMode.value;
    if (mode === 'day') return $t('Scroll forward by day');
    if (mode === 'week') return $t('Scroll forward by week');
    if (mode === 'month') return $t('Scroll forward by month');
    return $t('Scroll forward by day');
});

// Methods
const saveUserCalendarSettings = () => {
    userCalendarSettings.patch(route('user.calendar_settings.update', {user: usePage().props.auth.user.id}), {
        preserveScroll: true
    });
    document.getElementById('displaySettings').click();
};

const getTimePeriodProjectId = () => {
    return usePage().props.auth.user.calendar_settings.time_period_project_id;
};

const toggleProjectTimePeriodAndRedirect = (projectId, enabled) => {
    router.patch(
        route('user.calendar_settings.toggle_calendar_settings_use_project_period_shift_plan'),
        {
            use_project_time_period: enabled,
            project_id: projectId
        },
        {
            preserveState: false
        }
    );
};

const handleUseTimePeriodChange = (enabled) => {
    if (!enabled && !isCalendarUsingProjectTimePeriod.value && getTimePeriodProjectId() > 0) {
        toggleProjectTimePeriodAndRedirect(0, false);
    }
};

const formatDateStringToGermanFormat = (dateString) => {
    let parts = dateString.split('-');
    return parts[2] + '.' + parts[1] + '.' + parts[0];
};

const changeUserSelectedGoTo = (type) => {
    axios.patch(route('user.calendar.go.to.stepper', {user: usePage().props.auth.user.id}), {
        goto_mode: type,
    }).then(() => {
        usePage().props.auth.user.goto_mode = type;
    });
};

const removeFilter = (filter) => {
    if (filter.value === 'rooms') {
        props.user_filters.rooms.splice(props.user_filters.rooms.indexOf(filter.id), 1);
        updateFilterValue('rooms', props.user_filters.rooms.length > 0 ? props.user_filters.rooms : null);
    }
    if (filter.value === 'event_types') {
        props.user_filters.event_types.splice(props.user_filters.event_types.indexOf(filter.id), 1);
        updateFilterValue('event_types', props.user_filters.event_types.length > 0 ? props.user_filters.event_types : null);
    }
};

const updateFilterValue = (key, value) => {
    router.patch(route('user.shift.calendar.filter.single.value.update', {user: usePage().props.auth.user.id}), {
        key: key,
        value: value
    }, {
        preserveScroll: true,
        preserveState: false
    });
};

const scrollToNextDay = () => {
    emit('selectGoToNextMode');
};

const scrollToPreviousDay = () => {
    emit('selectGoToPreviousMode');
};

const enterFullscreenMode = () => {
    emit('enterFullscreenMode');
};

const previousTimeRange = () => {
    emit('previousTimeRange');
};

const nextTimeRange = () => {
    emit('nextTimeRange');
};

const filtersChanged = (activeFilters) => {
    // This method was not used in the original component
};

const openHistoryModal = () => {
    emit('openHistoryModal');
};

// Daily view mode management
const dailyViewMode = ref(usePage().props.auth.user.daily_view ?? false);

const changeDailyViewMode = (newValue) => {
    dailyViewMode.value = newValue;
    router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
        daily_view: dailyViewMode.value
    }, {
        preserveScroll: false,
        preserveState: false
    });
};

// Shortcut functions for the three icons (adapted from FunctionBarCalendar)
const jumpToToday = () => {
    const today = new Date().toISOString().slice(0, 10);

    // Switch to daily mode if not already in daily mode
    if (!dailyViewMode.value) {
        changeDailyViewMode(true);
        // Update dates after mode change
        setTimeout(() => {
            router.patch(route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id), {
                start_date: today,
                end_date: today,
            }, {
                preserveScroll: true,
                preserveState: false
            });
        }, 100);
    } else {
        // If already in daily mode, just update the dates
        router.patch(route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id), {
            start_date: today,
            end_date: today,
        }, {
            preserveScroll: true,
            preserveState: false
        });
    }
};

const jumpToCurrentWeek = () => {
    const today = new Date();
    const currentWeekStart = new Date(today);
    const currentWeekEnd = new Date(today);

    // Calculate start of week (Monday)
    const dayOfWeek = today.getDay();
    const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1; // Sunday is 0, Monday is 1
    currentWeekStart.setDate(today.getDate() - daysToMonday);

    // Calculate end of week (Sunday)
    const daysToSunday = dayOfWeek === 0 ? 0 : 7 - dayOfWeek;
    currentWeekEnd.setDate(today.getDate() + daysToSunday);

    router.patch(route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id), {
        start_date: currentWeekStart.toISOString().slice(0, 10),
        end_date: currentWeekEnd.toISOString().slice(0, 10),
    }, {
        preserveScroll: true,
        preserveState: false
    });
};

const jumpToCurrentMonth = () => {
    const today = new Date();
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0);

    // Switch to normal mode (not daily mode) if in daily mode (month is longer than 7 days)
    if (dailyViewMode.value) {
        changeDailyViewMode(false);
        // Update dates after mode change
        setTimeout(() => {
            router.patch(route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id), {
                start_date: monthStart.toISOString().slice(0, 10),
                end_date: monthEnd.toISOString().slice(0, 10),
            }, {
                preserveScroll: true,
                preserveState: false
            });
        }, 100);
    } else {
        // If already in normal mode, just update the dates
        router.patch(route('update.user.shift.calendar.filter.dates', usePage().props.auth.user.id), {
            start_date: monthStart.toISOString().slice(0, 10),
            end_date: monthEnd.toISOString().slice(0, 10),
        }, {
            preserveScroll: true,
            preserveState: false
        });
    }
};

const commitAllShifts = () => {
    /*
    let filteredEvents = [];

    // Loop through each room in the shiftPlan array
    props.rooms.forEach(room => {
        // Loop through each day in the room object
        Object.values(room).forEach(day => {
            // Check if day has an 'events' property, and it has a 'data' property
            if (day.events) {
                // Add the events to the allEvents array
                filteredEvents = filteredEvents.concat(day.events);
            }
        });
    });

    router.post('/shifts/commit', {events: filteredEvents}, {
        onSuccess: () => {
            showConfirmCommitModal.value = false;
        },
        preserveScroll: true,
        preserveState: true,
    });
     */

    showShiftCommitDateSelectModal.value = true;
};

// Watchers
watch(projectSearch, (searchValue) => {
    if (searchValue.length === 0) {
        projectSearchResults.value = [];
        return;
    }
    axios.get(
        route('projects.search'),
        {
            params: {query: searchValue}
        }
    ).then(
        (response) => {
            projectSearchResults.value = response.data;
        }
    );
});

watch(() => usePage().props.auth.user.calendar_settings.use_project_time_period, (newValue) => {
    if (newValue) {
        nextTick(() => {
            document.getElementById('shiftPlanProjectSearch')?.focus();
        });
    }
});

const closeCalendarAboSettingModal = (bool) => {
    showCalendarAboSettingModal.value = false;
    if(bool){
        showCalendarAboInfoModal.value = true;
    }
}
</script>

<style scoped>
</style>
