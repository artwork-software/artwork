<template>
    <div class=" card glassy py-2 sticky top-0 z-50 ">
        <div class="flex justify-between items-center mt-2 mb-2 px-5">
            <div class="inline-flex items-center">
                <div v-if="!isCalendarUsingProjectTimePeriod" class="flex">
                    <date-picker-component v-if="dateValue" :dateValueArray="dateValue"
                                           :is_shift_plan="true"></date-picker-component>
                    <div class="flex items-center mx-4 gap-x-1 select-none">
                        <IconChevronLeftPipe stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"
                                             @click="previousTimeRange"/>
                        <IconChevronLeft stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"
                                         @click="scrollToPreviousDay"/>
                        <Menu as="div" class="relative inline-block text-left">
                            <div class="flex items-center">
                                <MenuButton class="">
                                    <IconCalendarMonth stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"
                                                       v-if="userGotoMode === 'month'"/>
                                    <IconCalendarWeek stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"
                                                      v-if="userGotoMode === 'week'"/>
                                    <IconCalendar stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"
                                                  v-if="userGotoMode === 'day'"/>
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
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Day')"
                                                    icon="IconCalendar"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('week')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Calendar week')"
                                                    icon="IconCalendarWeek"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('month')"
                                                 :class="[active ? 'bg-artwork-navigation-color/10 text-white' : 'text-white', 'block px-4 py-2 text-sm']">
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
                        <IconChevronRight stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"
                                          @click="scrollToNextDay"/>

                        <IconChevronRightPipe stroke-width="1.5" class="h-7 w-7 text-artwork-buttons-context cursor-pointer"
                                              @click="nextTimeRange"/>
                    </div>
                    <div class="items-center hidden">
                        <div class="flex items-center">
                            <button class="ml-2 text-black" @click="previousTimeRange">
                                <IconChevronLeft stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"/>
                            </button>
                            <button class="ml-2  text-black" @click="nextTimeRange">
                                <IconChevronRight stroke-width="1.5" class="h-5 w-5 text-artwork-buttons-context"/>
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else class="relative">
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

                <Switch
                    v-model="usePage().props.auth.user.calendar_settings.use_project_time_period"
                    @update:model-value="handleUseTimePeriodChange"
                    :class="[isCalendarUsingProjectTimePeriod ? 'bg-artwork-buttons-hover mr-2' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none ml-4 z-50']">
                    <span class="sr-only">Use project time period toggle</span>
                    <span
                        :class="[isCalendarUsingProjectTimePeriod ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                    <span
                        :class="[isCalendarUsingProjectTimePeriod ? 'opacity-0 duration-100 ease-out pointer-events-none' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                        aria-hidden="true">
                        <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconGeometry"
                                          :tooltip-text="$t('Project search')" stroke="1.5"/>
                    </span>
                    <span
                        :class="[isCalendarUsingProjectTimePeriod ? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out pointer-events-none', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']"
                        aria-hidden="true">
                        <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconGeometry"
                                          :tooltip-text="$t('Project search')" stroke="1.5"/>
                    </span>
                </span>
                </Switch>
            </div>
            <slot name="multiEditCalendar"/>

            <div class="flex items-center">
                <div class="flex items-center gap-x-3">
                    <slot name="moreButtons">

                    </slot>
                    <Menu as="div" class="relative inline-block items-center text-left">
                        <div class="flex items-center">
                            <MenuButton id="displaySettings">
                            <span class="items-center flex">
                                <button type="button"
                                        class="text-sm flex items-center my-auto text-primary font-semibold focus:outline-none transition">
                                    <ToolTipComponent
                                        direction="bottom"
                                        :tooltip-text="$t('Display Settings')"
                                        icon="IconSettings"
                                        icon-size="h-7 w-7"
                                    />
                                </button>
                            </span>
                            </MenuButton>
                        </div>
                        <transition
                            enter-active-class="transition duration-50 ease-out"
                            enter-from-class="transform scale-100 opacity-100"
                            enter-to-class="transform scale-100 opacity-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="transform scale-100 opacity-100"
                            leave-to-class="transform scale-95 opacity-0">
                            <MenuItems
                                class="w-80 absolute right-0 top-12 origin-top-right shadow-lg bg-artwork-navigation-background rounded-lg ring-1 ring-black p-2 text-white opacity-100 z-50">
                                <div class="w-76 p-6">
                                    <div class="flex items-center py-1">
                                        <input id="cb-high-contrast"
                                               v-model="userCalendarSettings.high_contrast"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-high-contrast"
                                               :class="userCalendarSettings.high_contrast ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('High contrast') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-project-artists"
                                               v-model="userCalendarSettings.show_qualifications"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-project-artists"
                                               :class="userCalendarSettings.show_qualifications ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Show qualifications') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-project-status"
                                               v-model="userCalendarSettings.shift_notes"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-project-status"
                                               :class="userCalendarSettings.shift_notes ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Show notes') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-expand-days" v-model="userCalendarSettings.expand_days"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-expand-days"
                                               :class="userCalendarSettings.expand_days ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Expand days') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-expand-days" v-model="userCalendarSettings.display_project_groups"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-expand-days"
                                               :class="userCalendarSettings.display_project_groups ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Show project group') }}
                                        </label>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <button class="text-sm mx-3 mb-4" @click="saveUserCalendarSettings">
                                        {{ $t('Save') }}
                                    </button>
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>
                    <ToolTipComponent v-if="can('can commit shifts') || hasAdminRole()" direction="bottom"
                                      :tooltip-text="$t('Lock all shifts')" icon="IconCalendarCheck" icon-size="h-7 w-7"
                                      @click="commitAllShifts()"/>
                    <ToolTipComponent direction="bottom" :tooltip-text="$t('History')" icon="IconHistory"
                                      icon-size="h-7 w-7" @click="openHistoryModal()"/>
                    <ToolTipComponent direction="bottom" :tooltip-text="$t('Full screen')" icon="IconArrowsDiagonal"
                                      icon-size="h-7 w-7" v-if="!isFullscreen" @click="enterFullscreenMode"/>
                    <ShiftPlanFilter
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :user_filters="user_filters"
                        :crafts="crafts"
                    />

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
        v-if="showShiftCommitDateSelectModal"
        @close="showShiftCommitDateSelectModal = false"

    />
</template>

<script setup>
import Button from "@/Jetstream/Button.vue";
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxLabel,
    ComboboxOption,
    ComboboxOptions,
    Menu,
    MenuButton,
    MenuItem,
    MenuItems,
    Switch,
    SwitchGroup,
    SwitchLabel
} from "@headlessui/vue";

import {
    IconChevronLeftPipe,
    IconChevronLeft,
    IconCalendarMonth,
    IconCalendarWeek,
    IconCalendar,
    IconChevronRight,
    IconChevronRightPipe,
    IconGeometry,
    IconSettings,
    IconCalendarCheck,
    IconHistory,
    IconArrowsDiagonal
} from "@tabler/icons-vue";

import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import ShiftPlanFilter from "@/Layouts/Components/ShiftPlanComponents/ShiftPlanFilter.vue";
import BaseFilterTag from "@/Layouts/Components/BaseFilterTag.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import {router, useForm, Link, usePage} from "@inertiajs/vue3";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {ref, computed, watch, nextTick} from 'vue';
import axios from 'axios';
import {usePermission} from "@/Composeables/Permission.js";
import ShiftCommitDateSelectModal from "@/Pages/Shifts/Components/ShiftCommitDateSelectModal.vue";

const {hasAdminRole, can} = usePermission(usePage().props);

const props = defineProps({
    dateValue: Array,
    isFullscreen: Boolean,
    filterOptions: Object,
    allShiftsCommitted: Boolean,
    personalFilters: Array,
    rooms: Array,
    user_filters: Object,
    crafts: Array,
    projectNameUsedForProjectTimePeriod: String,
    firstProjectShiftTabId: [Number, String]
});

const emit = defineEmits(['enterFullscreenMode', 'previousTimeRange', 'nextTimeRange', 'openHistoryModal', 'selectGoToNextMode', 'selectGoToPreviousMode']);

// Data properties
const showConfirmCommitModal = ref(false);
const showShiftCommitDateSelectModal = ref(false);
const scrollDays = ref(1);
const projectSearch = ref('');
const projectSearchResults = ref([]);
const userCalendarSettings = useForm({
    show_qualifications: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_qualifications : false,
    shift_notes: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.shift_notes : false,
    high_contrast: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.high_contrast : false,
    expand_days: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.expand_days : false,
    display_project_groups: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.display_project_groups : false,
});

// Computed properties
const activeFilters = computed(() => {
    let activeFiltersArray = [];
    props.filterOptions.rooms.forEach((room) => {
        if (props.user_filters.rooms?.includes(room.id)) {
            activeFiltersArray.push(room);
        }
    });

    props.filterOptions.eventTypes.forEach((eventType) => {
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
</script>

<style scoped>
</style>
