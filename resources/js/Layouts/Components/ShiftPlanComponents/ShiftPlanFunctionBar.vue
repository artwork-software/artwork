<template>
    <div class="bg-white border-b border-border-subtle shadow-sm py-2 sticky top-0 z-50 ">
        <div class="flex justify-between items-center mt-2 mb-2 px-5">
            <div class="inline-flex items-center">
                <div v-if="!isCalendarUsingProjectTimePeriod" class="flex">
                    <DateRangeControl
                        v-if="dateValue"
                        :date-value-array="dateValue"
                        mode="shift-plan"
                        :extra-params="{ isDailyView: isDailyView }"
                        :show-today="false"
                    />
                    <div class="flex gap-x-1 mx-2">
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
                                    class="absolute right-0 z-50 mt-2 w-fit origin-top-right rounded-md bg-surface-inverse shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                    <div class="py-1">
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('day')"
                                                 :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Day')"
                                                    icon="IconCalendar"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('week')"
                                                 :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-white', 'block px-4 py-2 text-sm']">
                                                <ToolTipComponent
                                                    direction="right"
                                                    :tooltip-text="$t('Jump around') + ' ' + $t('Calendar week')"
                                                    icon="IconCalendarWeek"
                                                    icon-size="h-5 w-5 text-white"/>
                                            </div>
                                        </MenuItem>
                                        <MenuItem v-slot="{ active }">
                                            <div @click="changeUserSelectedGoTo('month')"
                                                 :class="[active ? 'bg-text-inverse/10 text-accent-700' : 'text-white', 'block px-4 py-2 text-sm']">
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
                        label="Search project or artist"
                    />
                    <div v-if="projectSearchResults.length > 0"
                         class="absolute translate-y-1 bg-surface-inverse truncate sm:text-sm min-w-48 rounded-lg z-50">
                        <div v-for="(project, index) in projectSearchResults"
                             :key="index"
                             @click="toggleProjectTimePeriodAndRedirect(project.id, true)"
                             class="p-4 text-sm/5 font-bold text-white border-l-4 hover:border-l-success border-l-surface-inverse cursor-pointer flex flex-col">
                            <div>{{ project.name }}</div>
                            <div v-if="project.first_event_date && project.last_event_date" class="text-text-subtle text-xs font-normal">
                                {{ $t('Project period') }}: {{ project.first_event_date.split(' ')[0] }} - {{ project.last_event_date.split(' ')[0] }}
                            </div>
                            <div v-if="project.artists" class="text-text-subtle text-xs font-normal">
                                {{ $t('Artist') }}: {{ project.artists }}
                            </div>
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
                        v-model="activeSettings.use_project_time_period"
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

                    <!-- Kompaktmodus-Hinweis: unter 100 % zeigen Schichtkarten nur Zeit·Gewerk·Besetzung,
                         Zuweisen per Drag & Drop braucht 100 % (Klick öffnet weiterhin das Schicht-Modal) -->
                    <div
                        v-if="isCompactShiftZoom && !isDailyView"
                        class="ui-button !bg-accent-50 !border-accent-200/80 !text-accent-700 text-xs !cursor-help"
                    >
                        <ToolTipWithTextComponent
                            direction="bottom"
                            :text="$t('Compact')"
                            :icon="IconInfoCircle"
                            icon-size="size-4"
                            tooltip-width="w-72"
                            :tooltip-text="$t('Below 100% zoom, shift cards show only time, craft and staffing. Click a card to open it — for drag & drop assignment zoom back to 100%.')"
                        />
                    </div>

                    <!-- Zoom-Schnellauswahl: Tagesspaltenbreite (mehr Tage auf einen Blick) -->
                    <Menu v-if="!isDailyView" as="div" class="relative">
                        <MenuButton class="ui-button text-xs" :title="$t('Zoom')">
                            {{ shiftZoomPercent }}%
                            <PropertyIcon name="IconChevronDown" class="size-3.5" />
                        </MenuButton>
                        <transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <MenuItems class="absolute right-0 z-50 mt-2 origin-top-right focus:outline-none">
                                <div class="w-56 rounded-xl border border-border-subtle bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                                    <BaseMenuItem
                                        v-for="step in shiftZoomSteps"
                                        :key="step"
                                        white-menu-background
                                        without-translation
                                        :icon="step === shiftZoomFactor ? 'IconCheck' : 'IconPercentage'"
                                        :title="Math.round(step * 100) + '%' + (step === 1 ? ' – ' + $t('Standard') : ' – ' + $t('more days at a glance'))"
                                        @click="setShiftZoomFactor(step)"
                                    />
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>

                    <!--<ToolTipComponent direction="bottom" :tooltip-text="$t('Display Settings')" icon="IconSettings" icon-size="h-7 w-7"
                                      @click="showCalendarSettingsModal = true"/>-->

                    <FunctionBarSetting :is-planning="false" is-in-shift-plan :is-daily-view="isDailyView" />

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
                        :filter-type="isDailyView ? 'shift_daily_filter' : 'shift_filter'"
                    />

                    <ToolTipComponent v-if="can('can commit shifts') || hasAdminRole()" direction="bottom"
                                      :tooltip-text="commitShiftsTooltip" icon="IconCalendarCheck" icon-size="h-5 w-5" classes-button="ui-button"
                                      @click="commitAllShifts()"/>

                    <ToolTipComponent direction="bottom" :tooltip-text="$t('History')" icon="IconHistory"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="openHistoryModal()"/>
                    <ToolTipComponent direction="bottom" :tooltip-text="$t('Export')" icon="IconFileExport"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="showShiftPlanExportModal = true"/>
                    <ToolTipComponent direction="bottom" :tooltip-text="isFullscreen ? $t('Exit full screen') : $t('Full screen')"
                                      :icon="isFullscreen ? 'IconArrowsDiagonalMinimize' : 'IconArrowsDiagonal'"
                                      icon-size="h-5 w-5" classes-button="ui-button" @click="enterFullscreenMode"/>

                    <ToolTipComponent v-if="can('can subscribe shift calendar') || hasAdminRole()" direction="bottom" :tooltip-text="$t('Subscribe to shift calendar')" icon="IconCalendarStar"
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

    <ExportModal
        v-if="showShiftPlanExportModal"
        @close="showShiftPlanExportModal = false"
        :enums="shiftPlanExportTabs"
        :configuration="shiftPlanExportConfiguration"
    />
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
import {router, Link, usePage} from "@inertiajs/vue3";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import DateRangeControl from "@/Artwork/DateRange/DateRangeControl.vue";
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
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ToolTipWithTextComponent from "@/Components/ToolTips/ToolTipWithTextComponent.vue";
import {IconInfoCircle} from "@tabler/icons-vue";
import {useShiftPlanZoom} from "@/Composeables/useShiftPlanZoom.js";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
const {hasAdminRole, can} = usePermission(usePage().props);

const exportTabEnums = useExportTabEnums();

const ExportModal = defineAsyncComponent({
    loader: () => import('@/Layouts/Components/Export/Modals/ExportModal.vue'),
    delay: 200,
    timeout: 3000,
});

// Schichtplan-Spaltenzoom (reaktiv, debounced persistiert)
const {
    zoomFactor: shiftZoomFactor,
    zoomPercent: shiftZoomPercent,
    zoomSteps: shiftZoomSteps,
    setZoomFactor: setShiftZoomFactor,
    isCompact: isCompactShiftZoom,
} = useShiftPlanZoom();

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
    eventTypes: Array,
    isDailyView: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['enterFullscreenMode', 'openHistoryModal', 'selectGoToNextMode', 'selectGoToPreviousMode']);

// Data properties
const showConfirmCommitModal = ref(false);
const showShiftCommitDateSelectModal = ref(false);
const showCalendarSettingsModal = ref(false);
const showCalendarAboInfoModal = ref(false);
const showCalendarAboSettingModal = ref(false);
const showShiftPlanExportModal = ref(false);
const projectSearch = ref('');
const projectSearchResults = ref([]);
const activeSettings = computed(() => {
    if (props.isDailyView) {
        return usePage().props.shift_plan_daily_settings ?? usePage().props.shift_plan_settings;
    }
    return usePage().props.shift_plan_settings;
});

// Configuration handed to the export modal.
const shiftPlanExportTabs = computed(() => {
    const tabs = [exportTabEnums.PDF_SHIFT_PLAN_EXPORT, exportTabEnums.EXCEL_WORK_TIME_OVERVIEW_EXPORT];
    // Gewerke-Verteilung enthält namentliche Stunden — Backend-Route verlangt dieselbe Permission
    if (can('can view shift worker hours') || hasAdminRole()) {
        tabs.push(exportTabEnums.EXCEL_CRAFT_DISTRIBUTION_EXPORT);
    }
    return tabs;
});

const shiftPlanExportConfiguration = computed(() => {
    const projectId = usePage().props.projectId ?? null;
    const settings = activeSettings.value;
    const useProjectMode = !!settings?.use_project_time_period && !!settings?.time_period_project_id;
    return {
        [exportTabEnums.PDF_SHIFT_PLAN_EXPORT]: {
            startDate: props.dateValue?.[0] ?? null,
            endDate: props.dateValue?.[1] ?? null,
            projectId: projectId,
            isInProjectView: !!projectId,
            isDailyView: props.isDailyView,
            projectName: (projectId || useProjectMode) ? props.projectNameUsedForProjectTimePeriod : null,
            // In project mode, shifts/events belonging to this project are highlighted in the PDF.
            highlightProjectId: useProjectMode ? settings.time_period_project_id : null,
            // Selectable filter dimensions for the export dialog …
            filterOptions: {
                rooms: (props.filterOptions?.room_ids ?? []).map(({id, name}) => ({id, name})),
                areas: (props.filterOptions?.area_ids ?? []).map(({id, name}) => ({id, name})),
                eventTypes: (props.filterOptions?.event_type_ids ?? []).map(({id, name}) => ({id, name})),
                crafts: props.crafts.map(({id, name}) => ({id, name})),
            },
            // … prefilled with the filters currently active in the shift plan.
            activeFilters: {
                room_ids: props.user_filters?.room_ids ?? [],
                area_ids: props.user_filters?.area_ids ?? [],
                event_type_ids: props.user_filters?.event_type_ids ?? [],
                craft_ids: props.user_filters?.craft_ids ?? [],
            },
            // Flat craft config for the personnel (worker matrix) export mode.
            craftIds: props.user_filters?.craft_ids ?? [],
            crafts: props.crafts.map(({id, name}) => ({id, name})),
        },
        [exportTabEnums.EXCEL_WORK_TIME_OVERVIEW_EXPORT]: {
            crafts: props.crafts.map(({id, name}) => ({id, name})),
        },
        [exportTabEnums.EXCEL_CRAFT_DISTRIBUTION_EXPORT]: {
            crafts: props.crafts.map(({id, name, universally_applicable}) => ({id, name, universally_applicable})),
        },
    };
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
    return activeSettings.value?.use_project_time_period;
});

const userGotoMode = computed(() => {
    return usePage().props.auth.user.goto_mode;
});

const commitShiftsTooltip = computed(() => {
    return usePage().props.shiftCommitWorkflow
        ? $t('Request shift plan for approval')
        : $t('Commit shift plan');
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
const getTimePeriodProjectId = () => {
    return activeSettings.value?.time_period_project_id;
};

const toggleProjectTimePeriodAndRedirect = (projectId, enabled) => {
    router.patch(
        route('user.calendar_settings.toggle_calendar_settings_use_project_period_shift_plan'),
        {
            use_project_time_period: enabled,
            project_id: projectId,
            is_daily_view: props.isDailyView
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

const filtersChanged = (activeFilters) => {
    // This method was not used in the original component
};

const openHistoryModal = () => {
    emit('openHistoryModal');
};

// Daily view mode management
const dailyViewMode = ref(usePage().props.auth.user.shift_plan_daily_view ?? false);

const changeDailyViewMode = (newValue) => {
    dailyViewMode.value = newValue;
    router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
        daily_view: dailyViewMode.value,
        context: 'shift_plan'
    }, {
        preserveScroll: false,
        preserveState: false
    });
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
        isDailyView: props.isDailyView,
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
                isDailyView: false,
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
            isDailyView: props.isDailyView,
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

watch(() => activeSettings.value?.use_project_time_period, (newValue) => {
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
