<template>
    <div class="py-4 px-7 bg-white border-b border-zinc-200 shadow-sm">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div v-if="!project && !isCalendarUsingProjectTimePeriod" class="flex flex-row items-center">
                    <!-- Date Shortcuts - 3 vertical icons -->
                    <div class="flex items-center ">
                        <ToolTipComponent
                            v-if="!dailyView"
                            direction="bottom"
                            :tooltip-text="$t('Time range back')"
                            :icon="IconChevronLeft"
                            icon-size="h-5 w-5 text-primary"
                            @click="previousTimeRange"
                            classesButton="ui-button"
                        />
                        <ToolTipComponent
                            v-else
                            direction="bottom"
                            :tooltip-text="$t('Time range back')"
                            :icon="IconChevronLeft"
                            icon-size="h-5 w-5 text-primary"
                            @click="previousDay"
                            classesButton="ui-button"
                        />
                    </div>
                    <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="false" :is_planning="isPlanning"/>
                    <div class="flex items-center">
                        <ToolTipComponent
                            v-if="!dailyView"
                            direction="bottom"
                            :tooltip-text="$t('Time range forward')"
                            :icon="IconChevronRight"
                            icon-size="h-5 w-5 text-primary"
                            @click="nextTimeRange"
                            classesButton="ui-button"
                        />
                        <ToolTipComponent
                            v-else
                            direction="bottom"
                            :tooltip-text="$t('Time range forward')"
                            :icon="IconChevronRight"
                            icon-size="h-5 w-5 text-primary"
                            @click="nextDay"
                            classesButton="ui-button"
                        />

                    </div>
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

                    <BaseMenu tooltip-direction="bottom" show-custom-icon :icon="IconReorder" v-if="!atAGlance" class="mx-2" translation-key="Jump to month" has-no-offset>
                        <BaseMenuItem :icon="IconCalendarRepeat" white-menu-background without-translation v-for="month in months" :title="month.month + ' ' + month.year" @click="jumpToDayOfMonth(month.first_day_in_period)"/>
                    </BaseMenu>
                </div>
                <div v-else-if="!project" class="relative">
                    <BaseInput
                        id="calendarProjectSearch"
                        v-model="projectSearch"
                        :no-margin-top="true"
                        ref="projectSearchInput"
                        label="Search project"
                        is-small
                    />
                    <div v-if="projectSearchResults.length > 0"
                         class="absolute translate-y-1 bg-primary truncate sm:text-sm min-w-64 rounded-lg z-50">
                        <div v-for="(project, index) in projectSearchResults"
                             :key="index"
                             @click="toggleProjectTimePeriodAndRedirect(project.id, true)"
                             class="p-4 xsWhiteBold border-l-4 hover:border-l-success border-l-primary cursor-pointer flex flex-col">
                            <div>{{ project.name }}</div>
                            <div v-if="project.first_event_date && project.last_event_date" class="text-secondary text-xs font-normal">
                                {{ $t('Project period') }}: {{ project.first_event_date.split(' ')[0] }} - {{ project.last_event_date.split(' ')[0] }}
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!project && isCalendarUsingProjectTimePeriod && getTimePeriodProjectId() > 0" class=" text-sm">
                    {{ $t('Project period')}}:
                    <Link :href="route('projects.tab', {projectTab: first_project_tab_id, project: getTimePeriodProjectId()})"
                          class="font-bold">
                        {{ projectNameUsedForProjectTimePeriod }}
                    </Link>
                    <template v-if="dateValue[0] && dateValue[1]">
                        &nbsp;- {{ formatDateStringToGermanFormat(dateValue[0]) }} - {{ formatDateStringToGermanFormat(dateValue[1]) }}
                    </template>
                </div>
                <SwitchIconTooltip
                    v-if="!project"
                    v-model="usePage().props.auth.user.calendar_settings.use_project_time_period"
                    :tooltip-text="$t('Project search')"
                    size="md"
                    @change="handleUseTimePeriodChange"
                    :icon="IconGeometry"
                />

                <SwitchIconTooltip
                    v-model="dailyViewMode"
                    :tooltip-text="$t('Daily view')"
                    size="md"
                    @change="changeDailyViewMode"
                    :icon="IconCalendarWeek"
                />
            </div>

            <div v-if="isPlanning">
                <div class="font-lexend text-xs font-bold text-red-500 select-none pointer-events-none">
                    {{ $t('Attention! You are in the planning calendar')}}
                </div>
            </div>

            <div class="flex items-center gap-x-2">
                <div class="flex items-center">
                    <div class="flex items-center gap-x-2">
                        <slot name="buttonsRight"></slot>
                        <MultiEditSwitch :multi-edit="multiEdit"
                                         :room-mode="roomMode"
                                         @update:multi-edit="UpdateMultiEditEmits"/>
                        <SwitchIconTooltip
                            v-if="!roomMode"
                            v-model="atAGlance"
                            :roomMode="roomMode"
                            :tooltip-text="$t('At a glance')"
                            size="md"
                            @change="changeAtAGlance"
                        />
                    </div>
                </div>
                <div class="hidden 2xl:flex items-center gap-x-2">

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Zoom in')"
                        :icon="IconZoomIn"
                        icon-size="h-5 w-5"
                        :disabled="zoom_factor >= 1.4"
                        @click="incrementZoomFactor"
                        v-if="!atAGlance"
                        classesButton="ui-button"
                    />
                    <p class="xsDark ui-button !bg-gray-50 text-xs">
                        {{ zoom_factor * 100 }}%
                    </p>

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Zoom out')"
                        :icon="IconZoomOut"
                        icon-size="h-5 w-5"
                        :disabled="zoom_factor <= 0.2"
                        @click="decrementZoomFactor"
                        v-if="!atAGlance"
                        classesButton="ui-button"
                    />

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Full screen')"
                        :icon="IconArrowsDiagonal"
                        icon-size="h-5 w-5"
                        @click="$emit('openFullscreenMode')"
                        v-if="!atAGlance && !isFullscreen"
                        classesButton="ui-button"
                    />

                    <!--<IndividualCalendarFilterComponent
                        class=""
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :at-a-glance="atAGlance"
                        :type="project ? 'project' : 'individual'"
                        :user_filters="user_filters"
                        :extern-updated="externUpdate"/>-->

                    <!--<ToolTipComponent
                        icon="IconFilter"
                        icon-size="h-7 w-7"
                        direction="bottom"
                        :tooltip-text="$t('Filter')"
                        @click="showCalendarFilterModal = true"
                    />-->

                    <FunctionBarFilter
                        :user_filters="user_filters"
                        :personal-filters="personalFilters"
                        :filter-options="filterOptions"
                        :filter-type="isPlanning ? 'planning_filter' : 'calendar_filter'"
                    />

                    <FunctionBarSetting :is-planning="isPlanning" />

                    <!--<ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Display Settings')"
                        icon="IconSettings"
                        icon-size="h-7 w-7"
                        @click="showCalendarSettingsModal = true"
                    />-->

                    <div v-if="!project">
                        <div @click="showCalendarAboSettingModal = true"
                             class="flex items-center gap-x-1 text-sm group cursor-pointer">
                            <ToolTipComponent
                                direction="bottom"
                                :tooltip-text="$t('Subscribe to calendar')"
                                :icon="IconCalendarStar"
                                icon-size="h-5 w-5"
                                classesButton="ui-button"
                            />
                        </div>
                    </div>
                    <div @click="showExportModal = true">
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="$t('Export calendar')"
                            :icon="IconFileExport"
                            icon-size="h-5 w-5"
                            classesButton="ui-button"
                        />
                    </div>

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Add Event')"
                        :icon="IconCirclePlus"
                        icon-size="h-5 w-5 text-blue-500"
                        @click="$emit('wantsToAddNewEvent');"
                        classesButton="ui-button-add"
                    />
                </div>

                <!-- Mobile Menu for small screens -->
                <div class="2xl:hidden flex items-center gap-x-2">
                    <FunctionBarFilter
                        :user_filters="user_filters"
                        :personal-filters="personalFilters"
                        :filter-options="filterOptions"
                        :filter-type="isPlanning ? 'planning_filter' : 'calendar_filter'"
                    />

                    <FunctionBarSetting :is-planning="isPlanning" />

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Add Event')"
                        :icon="IconCirclePlus"
                        icon-size="h-5 w-5 text-blue-500"
                        @click="$emit('wantsToAddNewEvent');"
                        classesButton="ui-button-add"
                    />

                    <BaseMenu class="pt-2" tooltip-direction="bottom" show-custom-icon :icon="IconList" translation-key="More options" has-no-offset>
                        <BaseMenuItem
                            v-if="!atAGlance"
                            :icon="IconZoomIn"
                            white-menu-background
                            :title="$t('Zoom in')"
                            :disabled="zoom_factor >= 1.4"
                            @click="incrementZoomFactor"
                        />
                        <BaseMenuItem
                            white-menu-background
                            without-translation
                            :title="zoom_factor * 100 + '%'"
                        />
                        <BaseMenuItem
                            v-if="!atAGlance"
                            :icon="IconZoomOut"
                            white-menu-background
                            :title="$t('Zoom out')"
                            :disabled="zoom_factor <= 0.2"
                            @click="decrementZoomFactor"
                        />
                        <BaseMenuItem
                            v-if="!atAGlance && !isFullscreen"
                            :icon="IconArrowsDiagonal"
                            white-menu-background
                            :title="$t('Full screen')"
                            @click="$emit('openFullscreenMode')"
                        />
                        <BaseMenuItem
                            v-if="!project"
                            :icon="IconCalendarStar"
                            white-menu-background
                            :title="$t('Subscribe to calendar')"
                            @click="showCalendarAboSettingModal = true"
                        />
                        <BaseMenuItem
                            :icon="IconFileExport"
                            white-menu-background
                            :title="$t('Export calendar')"
                            @click="showExportModal = true"
                        />
                    </BaseMenu>
                </div>
            </div>
        </div>
    </div>
    <export-modal v-if="showExportModal"
                  @close="showExportModal = false"
                  :enums="[
                      exportTabEnums.PDF_CALENDAR_EXPORT,
                      exportTabEnums.EXCEL_EVENT_LIST_EXPORT,
                      exportTabEnums.EXCEL_CALENDAR_EXPORT
                  ]"
                  :configuration="getExportModalConfiguration()"/>
    <GeneralCalendarAboSettingModal
        :event-types="eventTypes"
        :rooms="rooms"
        :areas="areas"
        v-if="showCalendarAboSettingModal"
        @close="closeCalendarAboSettingModal"/>
    <CalendarAboInfoModal v-if="showCalendarAboInfoModal" @close="showCalendarAboInfoModal = false" />



</template>

<script setup>
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import {computed, defineAsyncComponent, inject, nextTick, ref, watch} from "vue";
import {
    IconChevronLeft,
    IconChevronRight,
    IconCalendar,
    IconCalendarWeek,
    IconCalendarMonth,
    IconGeometry, IconCalendarRepeat, IconList, IconZoomIn, IconZoomOut, IconArrowsDiagonal, IconCalendarStar,
    IconFileExport, IconCirclePlus, IconReorder
} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import GeneralCalendarAboSettingModal from "@/Pages/Events/Components/GeneralCalendarAboSettingModal.vue";
import {Switch} from "@headlessui/vue";
import MultiEditSwitch from "@/Components/Calendar/Elements/MultiEditSwitch.vue";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import CalendarFilterModal from "@/Pages/Calendar/Components/CalendarFilterModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import FunctionBarSetting from "@/Artwork/Filter/FunctionBarSetting.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";

const eventTypes = inject('eventTypes');
const rooms = inject('rooms');
const areas = inject('areas');
const dateValue = inject('dateValue');
const first_project_tab_id = inject('first_project_tab_id');
const filterOptions = inject('filterOptions');
const personalFilters = inject('personalFilters');
const user_filters = inject('user_filters');
const emits = defineEmits([
    'updateMultiEdit',
    'openFullscreenMode',
    'wantsToAddNewEvent',
    'previousDay',
    'nextDay',
    'searchingForProject',
    'jumpToDayOfMonth',
]);
const showCalendarAboSettingModal = ref(false);
const atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);
const zoom_factor = ref(usePage().props.auth.user.zoom_factor ?? 1);
const dateValueCopy = ref(dateValue ?? []);
const showExportModal = ref(false);
const roomCollisions = ref([]);
const showCalendarAboInfoModal = ref(false);
const showCalendarFilterModal = ref(false);
const showCalendarSettingsModal = ref(false);
const projectSearchInput = ref(null);



const projectSearch = ref('');
const projectSearchResults = ref([]);
const toggleProjectTimePeriodAndRedirect = (projectId, enabled) => {
    router.patch(
        route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
        {
            use_project_time_period: enabled,
            project_id: projectId
        },
        {
            preserveState: false
        }
    );
};

const CalendarSettingsModal = defineAsyncComponent({
    loader: () => import('@/Artwork/Modals/CalendarSettingsModal.vue'),
    delay: 200,
    timeout: 3000,
})

const exportTabEnums = useExportTabEnums();
const getExportModalConfiguration = () => {
    const cfg = {};

    cfg[exportTabEnums.PDF_CALENDAR_EXPORT] = {
        project: props.project
    };

    cfg[exportTabEnums.EXCEL_EVENT_LIST_EXPORT] = {
        project: props.project,
        show_artists: (usePage().props.createSettings?.show_artists ?? false) ||
            (usePage().props.show_artists ?? false),
    };

    cfg[exportTabEnums.EXCEL_CALENDAR_EXPORT] = {
        project: props.project
    };

    return cfg;
};

const handleUseTimePeriodChange = (enabled) => {
    if (!enabled && isCalendarUsingProjectTimePeriod && getTimePeriodProjectId() > 0) {
        toggleProjectTimePeriodAndRedirect(0, false);
    }
};

const {hasAdminRole, canAny} = usePermission(usePage().props);

const months = inject('months');

const props = defineProps({
    project: {
        type: Object,
        default: null
    },
    multiEdit: {
        type: Boolean,
        default: false
    },
    roomMode: {
        type: Boolean,
        default: false
    },
    rooms: {
        type: Object,
        required: true
    },
    isFullscreen: {
        type: Boolean,
        required: false,
        default: false
    },
    dailyView: {
        type: Boolean,
        required: false,
        default: false
    },
    projectNameUsedForProjectTimePeriod: {
        type: String,
        required: false,
        default: ''
    },
    isPlanning: {
        type: Boolean,
        required: false,
        default: false
    }
})

const dailyViewMode = ref(usePage().props.auth.user.daily_view ?? false);
const enableVerification = ref(false);
const isCalendarUsingProjectTimePeriod = computed(() => {
    return usePage().props.auth.user.calendar_settings.use_project_time_period;
});

const getTimePeriodProjectId = () => {
    return usePage().props.auth.user.calendar_settings.time_period_project_id;
}

const formatDateStringToGermanFormat = (dateString) => {
    let parts = dateString.split('-');

    return parts[2] + '.' + parts[1] + '.' + parts[0];
}

const closeCalendarAboSettingModal = (bool) => {
    showCalendarAboSettingModal.value = false;
    if(bool){
        showCalendarAboInfoModal.value = true;
    }
}

const changeDailyViewMode = () => {
    router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
        daily_view: dailyViewMode.value
    }, {
        preserveScroll: false,
        preserveState: false
    })
}

const UpdateMultiEditEmits = (value, isPlanning = false) => {
    emits('updateMultiEdit', value, isPlanning)
}

const changeAtAGlance = () => {
    router.patch(route('user.update.at_a_glance', usePage().props.auth.user.id), {
        at_a_glance: atAGlance.value
    }, {
        preserveState: false,
        preserveScroll: true
    })
}
const incrementZoomFactor = () => {
    if (zoom_factor.value < 1.4) {
        zoom_factor.value = Math.round((zoom_factor.value + 0.2) * 10) / 10;
        updateZoomFactorInUser();
    }
}
const decrementZoomFactor = () => {
    if (zoom_factor.value > 0.4) {
        zoom_factor.value = Math.round((zoom_factor.value - 0.2) * 10) / 10;
        updateZoomFactorInUser();
    }
}
const updateZoomFactorInUser = () => {
    router.patch(route('user.update.zoom_factor', {user: usePage().props.auth.user.id}), {
        zoom_factor: zoom_factor.value
    }, {
        preserveScroll: true,
        preserveState: false
    })
}
const calculateDateDifference = () => {
    const date1 = new Date(dateValueCopy.value[0]);
    const date2 = new Date(dateValueCopy.value[1]);
    const timeDifference = date2.getTime() - date1.getTime();
    return timeDifference / (1000 * 3600 * 24);
}
const previousTimeRange = () => {
    const dayDifference = calculateDateDifference();
    dateValueCopy.value[1] = getPreviousDay(dateValueCopy.value[0]);
    const newDate = new Date(dateValueCopy.value[1]);
    newDate.setDate(newDate.getDate() - dayDifference);
    dateValueCopy.value[0] = newDate.toISOString().slice(0, 10);
    updateTimes();
}
const nextTimeRange = () => {
    const dayDifference = calculateDateDifference();
    dateValueCopy.value[0] = getNextDay(dateValueCopy.value[1]);
    const newDate = new Date(dateValueCopy.value[1]);
    newDate.setDate(newDate.getDate() + dayDifference + 1);
    dateValueCopy.value[1] = newDate.toISOString().slice(0, 10);
    updateTimes();
}
const getNextDay = (dateString) => {
    const date = new Date(dateString);
    date.setDate(date.getDate() + 1);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

const previousDay = () => {
    emits('previousDay')
}

const nextDay = () => {
    emits('nextDay')
}

const getPreviousDay = (dateString) => {
    const date = new Date(dateString);
    date.setDate(date.getDate() - 1);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
const updateTimes = () => {
    router.patch(route('update.user.calendar.filter.dates', usePage().props.auth.user.id), {
        start_date: dateValueCopy.value[0],
        end_date: dateValueCopy.value[1],
        isPlanning: props.isPlanning
    }, {
        preserveScroll: false,
        preserveState: false
    });
}



const jumpToDayOfMonth = (day) => {
    emits('jumpToDayOfMonth', day);
}

// Shortcut functions for the three icons
const jumpToToday = () => {
    const today = new Date().toISOString().slice(0, 10);

    // Switch to daily mode if not already in daily mode
    if (!dailyViewMode.value) {
        dailyViewMode.value = true;
        router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
            daily_view: true
        }, {
            preserveScroll: false,
            preserveState: false,
            onSuccess: () => {
                // Set dates and update only after the mode change is completed
                dateValueCopy.value[0] = today;
                dateValueCopy.value[1] = today;
                updateTimes();
            }
        });
    } else {
        // If already in daily mode, set dates and update
        dateValueCopy.value[0] = today;
        dateValueCopy.value[1] = today;
        updateTimes();
    }
}

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

    dateValueCopy.value[0] = currentWeekStart.toISOString().slice(0, 10);
    dateValueCopy.value[1] = currentWeekEnd.toISOString().slice(0, 10);
    updateTimes();
}

const jumpToCurrentMonth = () => {
    const today = new Date();

    // Use UTC methods to avoid timezone conversion issues
    const monthStart = new Date(Date.UTC(today.getFullYear(), today.getMonth(), 1));
    const monthEnd = new Date(Date.UTC(today.getFullYear(), today.getMonth() + 1, 0));

    const startDateString = monthStart.toISOString().slice(0, 10);
    const endDateString = monthEnd.toISOString().slice(0, 10);

    // Switch to normal mode (not daily mode) if in daily mode
    if (dailyViewMode.value) {
        dailyViewMode.value = false;
        router.patch(route('user.update.daily_view', usePage().props.auth.user.id), {
            daily_view: false
        }, {
            preserveScroll: false,
            preserveState: false,
            onSuccess: () => {
                // Set dates and update only after the mode change is completed
                dateValueCopy.value[0] = startDateString;
                dateValueCopy.value[1] = endDateString;
                updateTimes();
            }
        });
    } else {
        // If already in normal mode, set dates and update
        dateValueCopy.value[0] = startDateString;
        dateValueCopy.value[1] = endDateString;
        updateTimes();
    }
}


watch(() => projectSearch.value, (searchValue) => {
    if (searchValue.length === 0) {
        projectSearchResults.value = [];
        emits.call(this, 'searchingForProject', false);
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
            emits.call(this, 'searchingForProject', true);
        }
    );
});

// watch on usePage().props.auth.user.calendar_settings.use_project_time_period
watch(() => usePage().props.auth.user.calendar_settings.use_project_time_period, (newValue) => {
    // if to focus on input field
    if (newValue) {
        nextTick(() => {
            document.getElementById('calendarProjectSearch').focus();
        });
    }
});
</script>
