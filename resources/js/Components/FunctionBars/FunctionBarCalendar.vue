<template>
    <div class="py-4 px-5 bg-white border-b border-zinc-200 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-y-2">
            <div class="flex items-center gap-2">
                <div v-if="!project && !isCalendarUsingProjectTimePeriod" class="flex flex-row items-center">
                    <DateRangeControl
                        v-if="dateValue"
                        :date-value-array="dateValue"
                        mode="calendar"
                        :extra-params="{ isPlanning: isPlanning, isDailyView: dailyView }"
                        :show-today="false"
                    />
                    <div class="flex gap-x-1 mx-1">
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

                    <BaseMenu tooltip-direction="bottom" show-custom-icon :icon="IconReorder" v-if="!atAGlance && startAndEndDateInDifferentMonths" class="mx-1" translation-key="Jump to month" has-no-offset>
                        <BaseMenuItem :icon="IconCalendarRepeat" white-menu-background without-translation v-for="month in months" :title="month.month + ' ' + month.year" @click="jumpToDayOfMonth(month.first_day_in_period)"/>
                    </BaseMenu>
                </div>
                <!-- w-72, damit das Label "Projekt oder Künstler*in suchen" nicht abgeschnitten wird -->
                <div v-else-if="!project" class="relative w-72">
                    <BaseInput
                        id="calendarProjectSearch"
                        v-model="projectSearch"
                        :no-margin-top="true"
                        ref="projectSearchInput"
                        label="Search project or artist"
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
                            <div v-if="project.artists" class="text-secondary text-xs font-normal">
                                {{ $t('Artist') }}: {{ project.artists }}
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="!project && isCalendarUsingProjectTimePeriod && getTimePeriodProjectId() > 0" class=" text-sm">
                    {{ $t('Project')}}:
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
                    v-model="activeCalSettings.use_project_time_period"
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

                    <!-- Kompaktmodus-Hinweis: bei < 80 % öffnen Termine ihr Modal per Klick,
                         das Drei-Punkte-Menü auf der Kachel entfällt -->
                    <div
                        v-if="isCompactZoom && !atAGlance"
                        class="ui-button !bg-blue-50 !border-blue-200/80 !text-blue-700 text-xs !cursor-help"
                    >
                        <ToolTipWithTextComponent
                            direction="bottom"
                            :text="$t('Compact')"
                            :icon="IconInfoCircle"
                            icon-size="size-4"
                            tooltip-width="w-72"
                            :tooltip-text="$t('Below 80% zoom the compact mode is active: clicking an event opens its editing or detail view directly. The menu for further actions is not available in compact mode.')"
                        />
                    </div>

                    <!-- Zoom-Schnellauswahl: Klick auf die %-Anzeige öffnet die Stufenliste -->
                    <Menu as="div" class="relative" v-if="!atAGlance">
                        <MenuButton class="ui-button text-xs" :title="$t('Zoom')">
                            {{ zoomPercent }}%
                            <component :is="IconChevronDown" class="size-3.5" />
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
                                <!-- Panel-Optik identisch zu BaseMenu -->
                                <div class="w-52 rounded-xl border border-gray-200 bg-white p-1.5 shadow-xl ring-1 ring-black/5">
                                    <BaseMenuItem
                                        white-menu-background
                                        without-translation
                                        :icon="IconCalendarMonth"
                                        :title="$t('Month view') + ' (40%)'"
                                        @click="selectMonthView"
                                    />
                                    <div class="my-1 border-t border-gray-100"></div>
                                    <BaseMenuItem
                                        v-for="step in zoomSteps"
                                        :key="step"
                                        white-menu-background
                                        without-translation
                                        :icon="step === zoomFactor ? IconCheck : IconPercentage"
                                        :title="Math.round(step * 100) + '%' + (step === 1 ? ' – ' + $t('Standard') : '')"
                                        @click="setZoomFactor(step)"
                                    />
                                </div>
                            </MenuItems>
                        </transition>
                    </Menu>

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
                        :filter-type="isPlanning ? (dailyView ? 'planning_daily_filter' : 'planning_filter') : (dailyView ? 'calendar_daily_filter' : 'calendar_filter')"
                    />

                    <FunctionBarSetting :is-planning="isPlanning" :is-daily-view="dailyView" />

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
                        :filter-type="isPlanning ? (dailyView ? 'planning_daily_filter' : 'planning_filter') : (dailyView ? 'calendar_daily_filter' : 'calendar_filter')"
                    />

                    <FunctionBarSetting :is-planning="isPlanning" :is-daily-view="dailyView" />

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
                            :icon="IconCalendarMonth"
                            white-menu-background
                            without-translation
                            :title="$t('Month view') + ' (40%)'"
                            @click="selectMonthView"
                        />
                        <template v-if="!atAGlance">
                            <BaseMenuItem
                                v-for="step in zoomSteps"
                                :key="step"
                                white-menu-background
                                without-translation
                                :title="Math.round(step * 100) + '%' + (step === zoomFactor ? ' ✓' : '')"
                                @click="setZoomFactor(step)"
                            />
                        </template>
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
                      exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT,
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
import DateRangeControl from "@/Artwork/DateRange/DateRangeControl.vue";
import {computed, defineAsyncComponent, inject, nextTick, ref, unref, watch} from "vue";
import {
    IconChevronLeft,
    IconChevronRight,
    IconChevronDown,
    IconCheck,
    IconPercentage,
    IconInfoCircle,
    IconCalendar,
    IconCalendarWeek,
    IconCalendarMonth,
    IconGeometry, IconCalendarRepeat, IconList, IconArrowsDiagonal, IconCalendarStar,
    IconFileExport, IconCirclePlus, IconReorder
} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import GeneralCalendarAboSettingModal from "@/Pages/Events/Components/GeneralCalendarAboSettingModal.vue";
import {Switch, Menu, MenuButton, MenuItems} from "@headlessui/vue";
import {useCalendarZoom} from "@/Composeables/useCalendarZoom.js";
import MultiEditSwitch from "@/Components/Calendar/Elements/MultiEditSwitch.vue";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ToolTipWithTextComponent from "@/Components/ToolTips/ToolTipWithTextComponent.vue";
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
    'searchingForProject',
    'jumpToDayOfMonth',
]);
const showCalendarAboSettingModal = ref(false);
const atAGlance = ref(usePage().props.auth.user.at_a_glance ?? false);
// Zentraler reaktiver Zoom (debounced persistiert, kein Full-Reload mehr)
const {
    zoomFactor,
    zoomPercent,
    zoomSteps,
    setZoomFactor,
    requestMonthView,
    isCompact: isCompactZoom,
} = useCalendarZoom();
const selectMonthView = () => requestMonthView();
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
            project_id: projectId,
            is_daily_view: props.dailyView
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
    // Aktive Kalender-Filter mitgeben, damit sie im Export-Modal vorausgewählt sind
    const activeUserFilters = unref(user_filters) ?? null;

    cfg[exportTabEnums.PDF_CALENDAR_EXPORT] = {
        project: props.project,
        user_filters: activeUserFilters
    };

    cfg[exportTabEnums.PDF_MONTHLY_CALENDAR_EXPORT] = {
        project: props.project,
        user_filters: activeUserFilters
    };

    cfg[exportTabEnums.EXCEL_EVENT_LIST_EXPORT] = {
        project: props.project,
        show_artists: (usePage().props.createSettings?.show_artists ?? false) ||
            (usePage().props.show_artists ?? false),
        user_filters: activeUserFilters
    };

    cfg[exportTabEnums.EXCEL_CALENDAR_EXPORT] = {
        project: props.project,
        user_filters: activeUserFilters
    };

    return cfg;
};

const handleUseTimePeriodChange = (enabled) => {
    if (!enabled && getTimePeriodProjectId() > 0) {
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

const dailyViewMode = ref(usePage().props.auth.user.calendar_daily_view ?? false);
const enableVerification = ref(false);
const activeCalSettings = computed(() => {
    if (props.dailyView) {
        return usePage().props.daily_view_calendar_settings ?? usePage().props.auth.user.calendar_settings;
    }
    return usePage().props.auth.user.calendar_settings;
});

const isCalendarUsingProjectTimePeriod = computed(() => {
    return activeCalSettings.value?.use_project_time_period;
});

const startAndEndDateInDifferentMonths = computed(() => {
    if (!dateValue || !dateValue[0] || !dateValue[1]) {
        return false;
    }
    const startDate = new Date(dateValue[0]);
    const endDate = new Date(dateValue[1]);
    return startDate.getMonth() !== endDate.getMonth() || startDate.getFullYear() !== endDate.getFullYear();
});

const getTimePeriodProjectId = () => {
    return activeCalSettings.value?.time_period_project_id;
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
        daily_view: dailyViewMode.value,
        context: 'calendar'
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
const updateTimes = () => {
    router.patch(route('update.user.calendar.filter.dates', usePage().props.auth.user.id), {
        start_date: dateValueCopy.value[0],
        end_date: dateValueCopy.value[1],
        isPlanning: props.isPlanning,
        isDailyView: props.dailyView
    }, {
        preserveScroll: false,
        preserveState: false
    });
}



const jumpToDayOfMonth = (day) => {
    emits('jumpToDayOfMonth', day);
}

// Shortcut functions for the two icons
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
            daily_view: false,
            context: 'calendar'
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

watch(() => activeCalSettings.value?.use_project_time_period, (newValue) => {
    if (newValue) {
        nextTick(() => {
            document.getElementById('calendarProjectSearch')?.focus();
        });
    }
});
</script>
