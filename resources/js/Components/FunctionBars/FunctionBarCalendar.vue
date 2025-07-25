<template>
    <div class="py-4 px-7 card glassy">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div v-if="!project && !isCalendarUsingProjectTimePeriod" class="flex flex-row items-center">
                    <date-picker-component v-if="dateValue" :dateValueArray="dateValue" :is_shift_plan="false"/>
                    <div class="flex items-center">
                        <button v-if="!dailyView" class="ml-2 text-black previousTimeRange" @click="previousTimeRange">
                            <IconChevronLeft class="h-5 w-5 text-primary"/>
                        </button>
                        <button v-else class="ml-2 text-black previousTimeRange" @click="previousDay">
                            <IconChevronLeft class="h-5 w-5 text-primary"/>
                        </button>
                        <button v-if="!dailyView" class="ml-2 text-black nextTimeRange" @click="nextTimeRange">
                            <IconChevronRight class="h-5 w-5 text-primary"/>
                        </button>
                        <button v-else class="ml-2 text-black nextTimeRange" @click="nextDay">
                            <IconChevronRight class="h-5 w-5 text-primary"/>
                        </button>

                    </div>
                    <BaseMenu tooltip-direction="bottom" show-custom-icon icon="IconReorder" v-if="!atAGlance" class="mx-2" translation-key="Jump to month" has-no-offset>
                        <BaseMenuItem icon="IconCalendarRepeat" white-menu-background without-translation v-for="month in months" :title="month.month + ' ' + month.year" @click="jumpToDayOfMonth(month.first_day_in_period)"/>
                    </BaseMenu>
                </div>

                <div v-else-if="!project" class="relative">
                    <BaseInput
                        id="calendarProjectSearch"
                        v-model="projectSearch"
                        :no-margin-top="true"
                        :is-small="true"
                        ref="projectSearchInput"
                        is-small
                        label="Search project"
                        class="w-9 h-9"
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
                <Switch v-if="!project"
                        v-model="usePage().props.auth.user.calendar_settings.use_project_time_period"
                        @update:model-value="handleUseTimePeriodChange"
                        :class="[isCalendarUsingProjectTimePeriod ? 'bg-artwork-buttons-hover mr-2' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                    <span class="sr-only">Use project time period toggle</span>
                    <span :class="[isCalendarUsingProjectTimePeriod ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                        <span :class="[isCalendarUsingProjectTimePeriod ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-40', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                            <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconGeometry" :tooltip-text="$t('Project search')" stroke="1.5"/>
                        </span>
                        <span :class="[isCalendarUsingProjectTimePeriod ? 'opacity-100 duration-200 ease-in z-40' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                            <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconGeometry" :tooltip-text="$t('Project search')" stroke="1.5"/>
                        </span>
                    </span>
                </Switch>

                <Switch @click="changeDailyViewMode()" v-model="dailyViewMode"
                        :class="[dailyViewMode ? 'bg-artwork-buttons-hover mr-2' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                    <span class="sr-only">Use setting</span>
                    <span :class="[dailyViewMode ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                <span :class="[dailyViewMode ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-40', 'absolute flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconCalendarWeek" :tooltip-text="$t('Daily view')" stroke="1.5"/>
                                </span>
                                <span :class="[dailyViewMode ? 'opacity-100 duration-200 ease-in z-40' : 'opacity-0 duration-100 ease-out', 'absolute flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconCalendarMonth" :tooltip-text="$t('Daily view')" stroke="1.5"/>
                                </span>
                            </span>
                </Switch>
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
                        <Switch @click="changeAtAGlance()" v-if="!roomMode" v-model="atAGlance"
                                :class="[atAGlance ? 'bg-artwork-buttons-hover mr-2' : 'bg-gray-200', 'relative inline-flex items-center h-5 w-10 flex-shrink-0 cursor-pointer rounded-full transition-colors duration-200 ease-in-out focus:outline-none focus:ring-none']">
                            <span class="sr-only">Use setting</span>
                            <span :class="[atAGlance ? 'translate-x-5' : 'translate-x-0', 'relative inline-block h-6 w-6 border border-gray-300 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                                <span :class="[atAGlance ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in z-40', 'absolute flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconList" :tooltip-text="$t('At a glance')" stroke="1.5"/>
                                </span>
                                <span :class="[atAGlance ? 'opacity-100 duration-200 ease-in z-40' : 'opacity-0 duration-100 ease-out', 'absolute flex h-full w-full items-center justify-center transition-opacity']" aria-hidden="true">
                                    <ToolTipComponent icon-size="w-4 h-4" direction="bottom" icon="IconList" :tooltip-text="$t('At a glance')" stroke="1.5"/>
                                </span>
                            </span>
                        </Switch>
                    </div>
                </div>
                <div class="flex items-center gap-x-2">

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Zoom in')"
                        icon="IconZoomIn"
                        icon-size="h-7 w-7"
                        :disabled="zoom_factor >= 1.4"
                        @click="incrementZoomFactor"
                        v-if="!atAGlance"
                    />
                    <p class="xsDark">
                        {{ zoom_factor * 100 }}%
                    </p>

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Zoom out')"
                        icon="IconZoomOut"
                        icon-size="h-7 w-7"
                        :disabled="zoom_factor <= 0.2"
                        @click="decrementZoomFactor"
                        v-if="!atAGlance"
                    />

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Full screen')"
                        icon="IconArrowsDiagonal"
                        icon-size="h-7 w-7"
                        @click="$emit('openFullscreenMode')"
                        v-if="!atAGlance && !isFullscreen"
                    />

                    <!--<IndividualCalendarFilterComponent
                        class=""
                        :filter-options="filterOptions"
                        :personal-filters="personalFilters"
                        :at-a-glance="atAGlance"
                        :type="project ? 'project' : 'individual'"
                        :user_filters="user_filters"
                        :extern-updated="externUpdate"/>-->

                    <ToolTipComponent
                        icon="IconFilter"
                        icon-size="h-7 w-7"
                        direction="bottom"
                        :tooltip-text="$t('Filter')"
                        @click="showCalendarFilterModal = true"
                    />

                    <Menu as="div" class="relative inline-block items-center text-left">
                        <div class="flex items-center">
                            <MenuButton id="displaySettings">
                            <span class="items-center flex">
                                <button type="button"
                                        class="text-sm flex items-center my-auto text-primary focus:outline-none transition">
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
                                               v-model="userCalendarSettings.project_artists"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-project-artists"
                                               :class="userCalendarSettings.project_artists ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Artists') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-project-status"
                                               v-model="userCalendarSettings.project_status"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-project-status"
                                               :class="userCalendarSettings.project_status ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Project Status') }}
                                        </label>
                                    </div>
                                    <!-- hidden on purpose: @todo: to clarify -->
                                    <div class="hidden items-center py-1">
                                        <input id="cb-options"
                                               v-model="userCalendarSettings.options"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-options"
                                               :class="userCalendarSettings.options ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Option prioritization') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-project-management"
                                               v-model="userCalendarSettings.project_management"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-project-management"
                                               :class="userCalendarSettings.project_management ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Project managers') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-repeating-events"
                                               v-model="userCalendarSettings.repeating_events"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-repeating-events"
                                               :class="userCalendarSettings.repeating_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Repeat event') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1" v-if="canAny(['can manage workers', 'can plan shifts']) || hasAdminRole()">
                                        <input id="cb-work-shifts"
                                               v-model="userCalendarSettings.work_shifts"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-work-shifts"
                                               :class="userCalendarSettings.work_shifts ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Shifts') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-description"
                                               v-model="userCalendarSettings.description"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-description"
                                               :class="userCalendarSettings.description ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Description') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-event-name"
                                               v-model="userCalendarSettings.event_name"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-event-name"
                                               :class="userCalendarSettings.event_name ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Event name') }}
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
                                    <div class="flex items-center py-1" v-if="usePage().props.event_status_module">
                                        <input id="cb-use-event-status-color"
                                               v-model="userCalendarSettings.use_event_status_color"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-use-event-status-color"
                                               :class="userCalendarSettings.use_event_status_color ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Use event status colour') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-hide-occupied-rooms"
                                               v-model="userCalendarSettings.hide_unoccupied_rooms"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-hide-occupied-rooms"
                                               :class="userCalendarSettings.hide_unoccupied_rooms ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Hide unoccupied rooms') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1">
                                        <input id="cb-display-project-groups"
                                               v-model="userCalendarSettings.display_project_groups"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-display-project-groups"
                                               :class="userCalendarSettings.display_project_groups ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Show project group') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1" v-if="isPlanning">
                                        <input id="cb-show-unplanned-events"
                                               v-model="userCalendarSettings.show_unplanned_events"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-show-unplanned-events"
                                               :class="userCalendarSettings.show_unplanned_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Show fixed events') }}
                                        </label>
                                    </div>
                                    <div class="flex items-center py-1" v-if="!isPlanning">
                                        <input id="cb-show-planned-events"
                                               v-model="userCalendarSettings.show_planned_events"
                                               type="checkbox"
                                               class="input-checklist"/>
                                        <label for="cb-show-planned-events"
                                               :class="userCalendarSettings.show_planned_events ? 'text-secondaryHover subpixel-antialiased' : 'text-secondary'"
                                               class="ml-4 my-auto text-secondary cursor-pointer">
                                            {{ $t('Show planned events') }}
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
                    <div v-if="!project">
                        <div @click="showCalendarAboSettingModal = true"
                             class="flex items-center gap-x-1 text-sm group cursor-pointer">
                            <ToolTipComponent
                                direction="bottom"
                                :tooltip-text="$t('Subscribe to calendar')"
                                icon="IconCalendarStar"
                                icon-size="h-7 w-7"
                            />
                        </div>
                    </div>
                    <div @click="showExportModal = true">
                        <ToolTipComponent
                            direction="left"
                            :tooltip-text="$t('Export calendar')"
                            icon="IconFileExport"
                            icon-size="h-7 w-7"
                        />
                    </div>

                    <ToolTipComponent
                        direction="bottom"
                        :tooltip-text="$t('Add Event')"
                        icon="IconCirclePlus"
                        icon-size="h-7 w-7"
                        @click="$emit('wantsToAddNewEvent');"
                    />
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

    <CalendarFilterModal
        v-if="showCalendarFilterModal"
        @close="showCalendarFilterModal = false"
        :filter-options="filterOptions"
        :personal-filters="personalFilters"
        :user_filters="user_filters"
    />
</template>

<script setup>
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import {computed, inject, nextTick, ref, watch} from "vue";
import {IconChevronLeft, IconChevronRight} from "@tabler/icons-vue";
import Button from "@/Jetstream/Button.vue";
import GeneralCalendarAboSettingModal from "@/Pages/Events/Components/GeneralCalendarAboSettingModal.vue";
import PlusButton from "@/Layouts/Components/General/Buttons/PlusButton.vue";
import {Menu, MenuButton, MenuItems, Switch} from "@headlessui/vue";
import MultiEditSwitch from "@/Components/Calendar/Elements/MultiEditSwitch.vue";
import {Link, router, useForm, usePage} from "@inertiajs/vue3";
import {usePermission} from "@/Composeables/Permission.js";
import IndividualCalendarFilterComponent from "@/Layouts/Components/IndividualCalendarFilterComponent.vue";
import CalendarAboInfoModal from "@/Pages/Shifts/Components/CalendarAboInfoModal.vue";
import Input from "@/Jetstream/Input.vue";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import CalendarFilterModal from "@/Pages/Calendar/Components/CalendarFilterModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import GlassyIconButton from "@/Artwork/Buttons/GlassyIconButton.vue";

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
const externUpdate = ref(false);
const showCalendarAboInfoModal = ref(false);
const showCalendarFilterModal = ref(false);
const projectSearchInput = ref(null);
const userCalendarSettings = useForm({
    project_status: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.project_status : false,
    project_artists: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.project_artists : false,
    options: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.options : false,
    project_management: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.project_management : false,
    repeating_events: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.repeating_events : false,
    work_shifts: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.work_shifts : false,
    description: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.description : false,
    event_name: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.event_name : false,
    high_contrast: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.high_contrast : false,
    expand_days: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.expand_days : false,
    use_event_status_color: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.use_event_status_color : false,
    hide_unoccupied_rooms: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.hide_unoccupied_rooms : false,
    display_project_groups: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.display_project_groups : false,
    show_unplanned_events: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_unplanned_events : false,
    show_planned_events: usePage().props.auth.user.calendar_settings ? usePage().props.auth.user.calendar_settings.show_planned_events : false,
});


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
    dailyViewMode.value = !dailyViewMode.value;
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
        at_a_glance: !atAGlance.value
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
    }, {
        preserveScroll: false,
        preserveState: false
    });
}

const saveUserCalendarSettings = () => {
    let valuesToReload = [];
    let preserveState = true

    if (userCalendarSettings.project_management) {
        valuesToReload.push('leaders');
    }

    if (userCalendarSettings.project_status) {
        valuesToReload.push('status');
    }

    if (userCalendarSettings.hide_unoccupied_rooms || !userCalendarSettings.hide_unoccupied_rooms) {
        valuesToReload.push('rooms');
        valuesToReload.push('calendar');
        valuesToReload.push('calendarData');
        preserveState = false;
    }

    userCalendarSettings.patch(route('user.calendar_settings.update', {user: usePage().props.auth.user.id}), {
        preserveScroll: true,
        preserveState: preserveState,
        onSuccess: () => {
            if (valuesToReload.length > 0) {
                router.reload({
                    only: valuesToReload
                });
            }
        }
    })
    document.getElementById('displaySettings').click();
}

const jumpToDayOfMonth = (day) => {
    emits('jumpToDayOfMonth', day);
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
