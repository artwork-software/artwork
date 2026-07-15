<template>
    <div class="relative w-full">
        <component :is="props.project ? 'div' : ShiftHeader">
            <transition name="fade" appear>
                <div
                    class="pointer-events-none fixed z-[100] inset-x-0 top-5 sm:flex sm:justify-center sm:px-6 sm:pb-5 lg:px-8"
                    v-show="showCalendarWarning.length > 0"
                >
                    <div class="pointer-events-auto flex items-center justify-between gap-x-6 bg-gray-900 px-6 py-2.5 sm:rounded-xl sm:py-3 sm:pl-4 sm:pr-3.5">
                        <component :is="IconAlertSquareRounded" class="size-5 text-yellow-400" aria-hidden="true" />
                        <p class="text-sm/6 text-white">
                            {{ showCalendarWarning }}
                        </p>
                        <button
                            type="button"
                            class="-m-1.5 flex-none p-1.5"
                            @click="showCalendarWarning = ''"
                        >
                            <span class="sr-only">{{ $t('Dismiss') }}</span>
                            <component :is="IconX" class="size-5 text-white" aria-hidden="true" />
                        </button>
                    </div>
                </div>
            </transition>

            <!-- topbar -->
            <div :class="topBarContainerClass" :style="topBarStyle" ref="topBarEl">
                <div class="flex items-center pr-5 gap-x-5 justify-between">
                    <div class="flex items-center gap-x-4">
                        <div v-if="props.project" class="ml-1 text-sm font-lexend font-semibold text-gray-700">
                            {{ $t('Projektzeitraum') }} {{ formatDate(projectStart) }} - {{ formatDate(projectEnd) }}
                        </div>

                        <template v-else>
                            <DateRangeControl
                                :date-value-array="props.dateValue"
                                mode="shift-plan"
                                :extra-params="{ isDailyView: true }"
                                :on-today-override="jumpToToday"
                            />

                            <div class="flex gap-x-1 mx-2">
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
                        </template>
                    </div>

                    <div class="flex items-center gap-x-5">
                        <BaseUIButton
                            v-if="isInProjectView && (can('can plan shifts') || is('artwork admin'))"
                            :label="$t('Add Shift')"
                            :icon="IconCalendarUser"
                            is-small
                            @click="openAddShiftForRoomAndDay(null, null)"
                        />

                        <SwitchIconTooltip
                            v-if="!props.project"
                            v-model="dailyViewMode"
                            :tooltip-text="$t('Daily view')"
                            size="md"
                            @change="changeDailyViewMode"
                            :icon="IconCalendarWeek"
                        />

                        <FunctionBarFilter
                            :user_filters="user_filtersResolved"
                            :personal-filters="personalFiltersResolved"
                            :filter-options="filterOptionsResolved"
                            :crafts="craftsResolved"
                            :filter-type="props.isInProjectView ? 'project_shift_filter' : 'shift_daily_filter'"
                        />

                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="$t('Export Shift Plan as PDF')"
                            :icon="IconFileExport"
                            icon-size="h-5 w-5"
                            @click="openExportDailyProjectShiftPlanModal = true"
                            v-if="isInProjectView"
                            classesButton="ui-button"
                        />

                        <ToolTipComponent
                            direction="right"
                            :tooltip-text="$t('Export Shift personnel plan as xlsx')"
                            :icon="IconFileTypeXls"
                            icon-size="h-5 w-5"
                            @click="downloadShiftPersonnelPlanXLSX()"
                            v-if="isInProjectView"
                            classesButton="ui-button"
                        />

                        <FunctionBarSetting :is-planning="false" is-in-shift-plan :is-daily-view="true" />
                    </div>
                </div>
            </div>

            <!-- Zugewiesene Personen (Projektzuordnungen; nur Projektansicht, per Anzeigeeinstellung) -->
            <section
                v-if="showProjectAssignments && hasAnyProjectAssignments"
                class="mx-1 mt-3 rounded-xl border border-zinc-200 bg-white px-4 py-3"
            >
                <h3 class="text-xs font-semibold tracking-wide text-zinc-500 uppercase mb-3">
                    {{ $t('Assigned persons') }}
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <h4 class="text-[11px] font-medium text-zinc-400 mb-1.5">{{ $t('Entire project period') }}</h4>
                        <div class="space-y-1.5">
                            <div
                                v-for="group in assignmentOverviewGroups.fullPeriod"
                                :key="group.group_id"
                                class="flex items-center gap-2"
                            >
                                <UserPopoverTooltip
                                    v-if="group.worker.type === 0"
                                    :user="group.worker"
                                    lazy-load
                                    height="7"
                                    width="7"
                                />
                                <img
                                    v-else-if="group.worker.profile_photo_url"
                                    :src="group.worker.profile_photo_url"
                                    :alt="group.worker.name"
                                    class="h-7 w-7 rounded-full object-cover"
                                />
                                <div class="min-w-0">
                                    <div class="text-xs text-zinc-800 truncate">{{ group.worker.name }}</div>
                                    <div class="text-[10px] text-zinc-400">
                                        {{ formatAssignmentDate(group.series_start) }} - {{ formatAssignmentDate(group.series_end) }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="!assignmentOverviewGroups.fullPeriod.length" class="text-[11px] text-zinc-400 italic">
                                {{ $t('None') }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[11px] font-medium text-zinc-400 mb-1.5">{{ $t('Single days') }}</h4>
                        <div class="space-y-1.5">
                            <div
                                v-for="group in assignmentOverviewGroups.singleDays"
                                :key="group.group_id"
                                class="flex items-center gap-2"
                            >
                                <UserPopoverTooltip
                                    v-if="group.worker.type === 0"
                                    :user="group.worker"
                                    lazy-load
                                    height="7"
                                    width="7"
                                />
                                <img
                                    v-else-if="group.worker.profile_photo_url"
                                    :src="group.worker.profile_photo_url"
                                    :alt="group.worker.name"
                                    class="h-7 w-7 rounded-full object-cover"
                                />
                                <div class="min-w-0">
                                    <div class="text-xs text-zinc-800 truncate">{{ group.worker.name }}</div>
                                    <div class="text-[10px] text-zinc-400 truncate" :title="group.dates.map(formatAssignmentDate).join(', ')">
                                        {{ group.dates.map(formatAssignmentDate).join(', ') }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="!assignmentOverviewGroups.singleDays.length" class="text-[11px] text-zinc-400 italic">
                                {{ $t('None') }}
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="text-[11px] font-medium text-zinc-400 mb-1.5 italic">{{ $t('Wishes') }}</h4>
                        <div class="space-y-1.5">
                            <div
                                v-for="group in assignmentOverviewGroups.wishes"
                                :key="group.group_id"
                                class="flex items-center gap-2"
                            >
                                <span class="rounded-full border-2 border-dashed border-emerald-400 p-[1px] shrink-0">
                                    <UserPopoverTooltip
                                        v-if="group.worker.type === 0"
                                        :user="group.worker"
                                        lazy-load
                                        height="6"
                                        width="6"
                                    />
                                    <img
                                        v-else-if="group.worker.profile_photo_url"
                                        :src="group.worker.profile_photo_url"
                                        :alt="group.worker.name"
                                        class="h-6 w-6 rounded-full object-cover"
                                    />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs text-zinc-800 italic truncate">{{ group.worker.name }}</div>
                                    <div class="text-[10px] text-zinc-400 truncate" :title="group.dates.map(formatAssignmentDate).join(', ')">
                                        <template v-if="group.is_full_period">
                                            {{ formatAssignmentDate(group.series_start) }} - {{ formatAssignmentDate(group.series_end) }}
                                        </template>
                                        <template v-else>
                                            {{ group.dates.map(formatAssignmentDate).join(', ') }}
                                        </template>
                                    </div>
                                </div>
                                <button
                                    v-if="can('can plan shifts') || is('artwork admin')"
                                    type="button"
                                    class="shrink-0 inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-white px-2 py-0.5 text-[10px] text-emerald-700 hover:border-emerald-400 transition-colors"
                                    :title="$t('Accept wish as binding assignment')"
                                    @click="acceptProjectWish(group)"
                                >
                                    {{ $t('Accept') }}
                                </button>
                            </div>
                            <div v-if="!assignmentOverviewGroups.wishes.length" class="text-[11px] text-zinc-400 italic">
                                {{ $t('None') }}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <div
                v-for="day in daysToRender"
                :key="day.withoutFormat"
                :ref="el => setDayRef(day.withoutFormat, el)"
                :style="!visibleDays.has(day.withoutFormat)
                    ? { minHeight: (dayHeights.get(day.withoutFormat) ?? estimateDayHeight(day)) + 'px' }
                    : undefined"
                class="flex flex-col w-full h-full relative ml-1"
                :class="props.isInProjectView ? 'mt-5' : ''"
            >
                <div v-if="!day.isExtraRow">
                    <!-- Day Header: always render (lightweight) -->
                    <div
                        class="flex items-center w-full bg-artwork-navigation-background text-white sticky ml-1 z-30"
                        :style="dayHeaderStyle"
                    >
                        <div class="flex items-center justify-between w-full gap-x-4 px-4">
                            <div class="flex items-center justify-start min-w-0 flex-1">
                                <BaseUIButton
                                    v-if="isDayWithoutRooms(day.fullDay)"
                                    :label="$t('Add Event')"
                                    :icon="IconCalendarPlus"
                                    is-small
                                    class="!bg-white/10 !text-white hover:!bg-white/20"
                                    @click="openNewEventModalWithBaseData(day.withoutFormat, null)"
                                />

                                <!-- Wünsche für diesen Tag: links vom Datum, gestrichelt-grüner Ring -->
                                <div
                                    v-if="showProjectAssignments && dayAssignmentAvatars(day, 'wish').length"
                                    class="ml-auto flex items-center gap-1 pr-2"
                                >
                                    <span
                                        v-for="assignment in dayAssignmentAvatars(day, 'wish').slice(0, 5)"
                                        :key="`wish-${assignment.id}`"
                                        class="rounded-full border-2 border-dashed border-emerald-400 p-[1px]"
                                        :title="`${assignment.worker.name} (${$t('Wish')})`"
                                    >
                                        <UserPopoverTooltip
                                            v-if="assignment.worker.type === 0"
                                            :user="assignment.worker"
                                            lazy-load
                                            height="6"
                                            width="6"
                                        />
                                        <img
                                            v-else-if="assignment.worker.profile_photo_url"
                                            :src="assignment.worker.profile_photo_url"
                                            :alt="assignment.worker.name"
                                            class="h-6 w-6 rounded-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-[9px]"
                                        >{{ assignment.worker.name?.slice(0, 2) }}</span>
                                    </span>
                                    <span
                                        v-if="dayAssignmentAvatars(day, 'wish').length > 5"
                                        class="text-[10px] text-white/70"
                                        :title="dayAssignmentAvatars(day, 'wish').slice(5).map(a => a.worker.name).join(', ')"
                                    >+{{ dayAssignmentAvatars(day, 'wish').length - 5 }}</span>
                                </div>
                            </div>

                            <div class="font-lexend text-sm font-bold py-4 text-center shrink-0 px-4 flex items-center gap-2">
                                {{ day.dayString }}, {{ day.fullDay }}
                                <div v-if="day.holidays?.length" class="shrink-0">
                                    <HolidayToolTip icon-color-class="text-white">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded-full
                                               bg-white/10 hover:bg-white/15
                                               border border-white/10
                                               px-2 py-0.5 text-[10px] font-medium"
                                        >
                                            <PropertyIcon name="IconSparkles" class="h-3 w-3 opacity-90" />
                                            <span class="tabular-nums">{{ day.holidays.length }}</span>
                                        </button>

                                        <div class="space-y-1 divide-y divide-dashed divide-white/20">
                                            <div
                                                v-for="holiday in day.holidays"
                                                :key="holiday.date || holiday.name"
                                                class="pt-1 text-[11px]"
                                            >
                                                <div :style="{ color: holiday.color }" class="font-semibold">
                                                    {{ holiday.name }}
                                                </div>
                                                <div class="text-white/80 text-[10px]" v-if="holiday.subdivisions?.length">
                                                    {{ holiday.subdivisions.map((p:any) => p).join(', ') }}
                                                </div>
                                                <div class="text-white/70 text-[10px]" v-else>
                                                    {{ $t('Germany-wide') }}
                                                </div>
                                            </div>
                                        </div>
                                    </HolidayToolTip>
                                </div>
                            </div>

                            <div class="flex items-center justify-end min-w-0 flex-1">
                                <!-- Verbindlich Zugeordnete: rechts vom Datum -->
                                <div
                                    v-if="showProjectAssignments && dayAssignmentAvatars(day, 'binding').length"
                                    class="mr-auto flex items-center gap-1 pl-2"
                                >
                                    <span
                                        v-for="assignment in dayAssignmentAvatars(day, 'binding').slice(0, 5)"
                                        :key="`binding-${assignment.id}`"
                                        :title="assignment.worker.name"
                                    >
                                        <UserPopoverTooltip
                                            v-if="assignment.worker.type === 0"
                                            :user="assignment.worker"
                                            lazy-load
                                            height="6"
                                            width="6"
                                        />
                                        <img
                                            v-else-if="assignment.worker.profile_photo_url"
                                            :src="assignment.worker.profile_photo_url"
                                            :alt="assignment.worker.name"
                                            class="h-6 w-6 rounded-full object-cover"
                                        />
                                        <span
                                            v-else
                                            class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-[9px]"
                                        >{{ assignment.worker.name?.slice(0, 2) }}</span>
                                    </span>
                                    <span
                                        v-if="dayAssignmentAvatars(day, 'binding').length > 5"
                                        class="text-[10px] text-white/70"
                                        :title="dayAssignmentAvatars(day, 'binding').slice(5).map(a => a.worker.name).join(', ')"
                                    >+{{ dayAssignmentAvatars(day, 'binding').length - 5 }}</span>
                                </div>

                                <BaseUIButton
                                    v-if="isDayWithoutRooms(day.fullDay) && (can('can plan shifts') || is('artwork admin'))"
                                    :label="$t('Add Shift')"
                                    :icon="IconCalendarUser"
                                    is-small
                                    class="!bg-white/10 !text-white hover:!bg-white/20"
                                    @click="openAddShiftForRoomAndDay(day.withoutFormat, null)"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Rooms Grid: only render when day is visible or nearby (P1 lazy rendering) -->
                    <template v-if="visibleDays.has(day.withoutFormat)">
                        <div
                            class="grid grid-cols-[3rem_1fr] ml-1"
                            v-for="room in getRoomsToRender(day)"
                            :key="room.roomId ?? room.id ?? room.roomName"
                        >
                            <div
                                :ref="el => setRoomContainerRef(roomDayKey(day.fullDay, room), el)"
                                class="flex flex-col-reverse items-center justify-between bg-artwork-navigation-background text-white py-4 border-t-2 border-dashed"
                            >
                                <div class="relative group text-xs font-bold font-lexend -rotate-90 h-full flex items-center text-center justify-center py-4 overflow-visible">
                                    <span
                                        :ref="el => setRoomNameRef(roomDayKey(day.fullDay, room), el)"
                                        class="inline-block flex-none truncate"
                                        :style="{ maxWidth: getRoomNameMaxWidth(roomDayKey(day.fullDay, room)) }"
                                    >{{ room.roomName }}</span>
                                    <div v-if="isRoomNameTruncated(roomDayKey(day.fullDay, room))" class="absolute hidden group-hover:block top-40 ml-22 z-9999 rotate-90">
                                        <div class="rounded-lg bg-artwork-navigation-background px-4 py-0.5 text-[14px] text-white whitespace-nowrap">
                                            {{ room.roomName }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-stretch px-4 py-2">
                                <div class="p-4 w-full relative">
                                    <DailyRoomSplitTimeline
                                        :day="day.fullDay"
                                        :events="getEventsForRoomDay(room, day.fullDay)"
                                        :shifts="getFilteredShiftsForRoomDay(room, day.fullDay)"
                                        :event-types="eventTypesResolved"
                                        :rooms="roomsArray"
                                        :first_project_calendar_tab_id="first_project_calendar_tab_idResolved"
                                        :event-statuses="eventStatusesResolved"
                                        :crafts="craftsResolved"
                                        :shift-qualifications="shiftQualificationsArray"
                                        :px-per-min="0.5"
                                        :gap-threshold-min="90"
                                        @addEvent="openNewEventModalWithBaseData(day.withoutFormat, room.roomId)"
                                        @addShift="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)"
                                        @addShiftByPresetOrGroup="openAddShiftByPresetOrGroup(day, room)"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <AddShiftModal
                v-if="showAddShiftModal"
                :crafts="craftsResolved"
                :event="null"
                :shift="shiftToEdit"
                :currentUserCrafts="pageProps.currentUserCrafts"
                :buffer="null"
                :shift-qualifications="pageProps.shiftQualifications"
                :shift-groups="pageProps.shiftGroups"
                :global-qualifications="pageProps.globalQualifications"
                @closed="closeAddShiftModal"
                :shift-time-presets="pageProps.shiftTimePresets"
                :rooms="roomsArray"
                :room="roomForShiftAdd"
                :day="dayForShiftAdd"
                :shift-plan-modal="true"
                :edit="shiftToEdit !== null"
                :project="props.project"
            />

            <AddShiftsByPresetsAndGroupsModal
                v-if="showAddShiftByPresetOrGroupModal"
                :single-shift-presets="singleShiftPresetsResolved"
                :preset-groups="shiftGroupPresetsResolved"
                :day="dayForPreset"
                :room="roomForPreset"
                :projects="projectsResolved"
                :initial-project-id="initialProjectIdResolved"
                :initial-project="props.project ?? page.props?.currentProject ?? null"
                @close="showAddShiftByPresetOrGroupModal = false"
            />

            <EventComponent
                v-if="showEventComponent"
                :showHints="pageProps.show_hints"
                :eventTypes="eventTypesResolved"
                :rooms="roomsArray"
                :calendarProjectPeriod="(pageProps.shift_plan_daily_settings ?? pageProps.shift_plan_settings ?? pageProps.auth.user.calendar_settings)?.use_project_time_period"
                :project="props.project"
                :event="eventToEdit"
                :wantedRoomId="wantedRoom"
                :isAdmin="hasAdminRole()"
                :roomCollisions="roomCollisions"
                :first_project_calendar_tab_id="first_project_calendar_tab_idResolved"
                :requires-axios-requests="true"
                @closed="eventComponentClosed"
                :event-statuses="eventStatusesResolved"
                :is-planning="isPlanning"
                :wanted-date="wantedDate"
            />

            <ExportDailyProjectShiftPlanModal
                v-if="openExportDailyProjectShiftPlanModal"
                @close="openExportDailyProjectShiftPlanModal = false"
                :project="props.project"
            />
        </component>
    </div>
</template>

<script setup lang="ts">
import ShiftHeader from "@/Pages/Shifts/ShiftHeader.vue";
import DateRangeControl from "@/Artwork/DateRange/DateRangeControl.vue";
import { ref, provide, onMounted, onUnmounted, onBeforeUnmount, watch, computed, nextTick, shallowRef, triggerRef } from "vue";
import AddShiftModal from "@/Pages/Projects/Components/AddShiftModal.vue";
import { router, usePage } from "@inertiajs/vue3";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {
    IconAlertSquareRounded,
    IconCalendar,
    IconCalendarWeek,
    IconCalendarMonth,
    IconChevronLeft,
    IconChevronRight,
    IconX, IconFileExport,
    IconCalendarPlus,
    IconCalendarUser, IconFileTypeXls,
} from "@tabler/icons-vue";
import { useShiftCalendarListener } from "@/Composeables/Listener/useShiftCalendarListener.js";
import { provideShiftPlanLookups } from "@/Composeables/useShiftPlanLookups.js";
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import FunctionBarSetting from "@/Artwork/Filter/FunctionBarSetting.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import axios from "axios";
import { enrichDays } from "@/Composeables/calendarDateUtils.js";
import DailyRoomSplitTimeline from "@/Pages/Shifts/DailyViewComponents/DailyRoomSplitTimeline.vue";
import dayjs from "dayjs";
import {can, is} from "laravel-permission-to-vuejs";
import ExportDailyProjectShiftPlanModal from "@/Pages/Projects/Components/ExportDailyProjectShiftPlanModal.vue";
import HolidayToolTip from "@/Components/ToolTips/HolidayToolTip.vue";
import PropertyIcon from "@/Artwork/Icon/PropertyIcon.vue";
import AddShiftsByPresetsAndGroupsModal from "@/Pages/Shifts/Components/AddShiftsByPresetsAndGroupsModal.vue";
import UserPopoverTooltip from "@/Layouts/Components/UserPopoverTooltip.vue";
import { formatAssignmentDate } from "@/Composeables/UseProjectDayAssignments.js";

type AnyRoom = any
type AnyEvent = any

const page = usePage()
const pageProps = computed(() => page.props)

const { mergeLookups } = provideShiftPlanLookups()


const props = defineProps({
    project: { type: Object as any, required: false, default: null },
    days: { type: Array, required: false, default: () => [] },
    dateValue: { type: Array, required: false, default: () => [] },
    shiftPlan: { type: [Object, Array], required: false, default: () => ({}) },

    shiftQualifications: { type: [Object, Array], required: false, default: () => ({}) },
    crafts: { type: [Object, Array], required: false, default: () => ({}) },
    rooms: { type: [Object, Array], required: false, default: () => ([]) },
    eventStatuses: { type: [Object, Array], required: false, default: () => ([]) },
    eventTypes: { type: [Object, Array], required: false, default: () => ([]) },

    shiftGroupPresets: { type: [Object, Array], required: false, default: () => ([]) },
    singleShiftPresets: { type: [Object, Array], required: false, default: () => ([]) },
    projects: { type: [Object, Array], required: false, default: () => ([]) },
    projectId: { type: [Number, String, null], required: false, default: null },

    event_properties: { type: Object, required: false, default: () => ({}) },
    first_project_calendar_tab_id: { type: Number, required: false, default: 0 },
    filterOptions: { type: Object, required: false, default: () => ({}) },
    personalFilters: { type: Object, required: false, default: () => ({}) },
    user_filters: { type: Object, required: false, default: () => ({}) },

    calendarWarningText: { type: String, required: false, default: "" },
    stickyOffsetTopPx: { type: Number, required: false, default: 0 },
    isInProjectView: { type: Boolean, required: false, default: false },
})

const hasAdminRole = () => is("artwork admin")

// --- Projektzuordnungen (Zugewiesene Personen + Wünsche; nur Projektansicht) ---
const projectDayAssignments = ref<any[]>([])

const showProjectAssignments = computed(() => {
    if (!props.isInProjectView || !props.project?.id) return false
    const settings = page.props.shift_plan_daily_settings
        ?? page.props.shift_plan_settings
        ?? (page.props.auth as any)?.user?.calendar_settings
    // Default AN: fehlender Wert (Alt-Settings ohne Spalte) zählt als aktiviert
    return (settings as any)?.show_project_assignments !== false
})

const hasAnyProjectAssignments = computed(() => projectDayAssignments.value.length > 0)

const loadProjectDayAssignments = async () => {
    if (!props.isInProjectView || !props.project?.id) return
    try {
        const { data } = await axios.get(route('projects.day-assignments', { project: props.project.id }))
        projectDayAssignments.value = data.assignments ?? []
    } catch {
        projectDayAssignments.value = []
    }
}

const assignmentsByDate = computed(() => {
    const map = new Map<string, any[]>()
    for (const assignment of projectDayAssignments.value) {
        if (!map.has(assignment.date)) map.set(assignment.date, [])
        map.get(assignment.date)!.push(assignment)
    }
    return map
})

const dayAssignmentAvatars = (day: any, kind: 'binding' | 'wish') => {
    const list = assignmentsByDate.value.get(day.withoutFormat) ?? []
    return list.filter((assignment: any) => assignment.type === kind)
}

/** Overview-Gruppen: ein Eintrag pro Anlage-Vorgang (group_id) */
const assignmentOverviewGroups = computed(() => {
    const byGroup = new Map<string, any>()
    for (const assignment of projectDayAssignments.value) {
        if (!byGroup.has(assignment.group_id)) {
            byGroup.set(assignment.group_id, { ...assignment, dates: [] })
        }
        byGroup.get(assignment.group_id).dates.push(assignment.date)
    }
    const groups = [...byGroup.values()]
    return {
        fullPeriod: groups.filter(g => g.type === 'binding' && g.is_full_period),
        singleDays: groups.filter(g => g.type === 'binding' && !g.is_full_period),
        wishes: groups.filter(g => g.type === 'wish'),
    }
})

const acceptProjectWish = async (group: any) => {
    await axios.patch(route('project-day-assignments.accept-wish', { projectDayAssignment: group.id }))
    await loadProjectDayAssignments()
}


const shiftQualificationsResolved = computed(() => {
    const v: any = props.shiftQualifications
    if (Array.isArray(v)) return v
    if (v && Object.keys(v).length) return Object.values(v)
    const fromPage: any = page.props.shiftQualifications ?? {}
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage)
})

// Crafts loaded asynchronously from API
const craftsLoaded = ref<any[]>([])
// G4a: Cache crafts source reference to avoid re-sorting identical arrays
let _lastCraftsSource: any[] | null = null
let _lastCraftsSorted: any[] = []

const craftsResolved = computed(() => {
    let result: any[]
    if (craftsLoaded.value?.length) {
        result = craftsLoaded.value
    } else {
        const v: any = props.crafts
        if (Array.isArray(v)) result = v
        else if (v && Object.keys(v).length) result = Object.values(v)
        else {
            const fromPage: any = page.props.crafts ?? {}
            result = Array.isArray(fromPage) ? fromPage : Object.values(fromPage)
        }
    }
    if (result === _lastCraftsSource) return _lastCraftsSorted
    _lastCraftsSource = result
    _lastCraftsSorted = result.slice().sort((a: any, b: any) => (a.position ?? 0) - (b.position ?? 0))
    return _lastCraftsSorted
})

const roomsResolved = computed(() => {
    const v: any = props.rooms
    if (Array.isArray(v)) return v
    if (v && Object.keys(v).length) return Object.values(v)
    const fromPage: any = page.props.rooms ?? []
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage ?? {})
})

const eventStatusesResolved = computed(() => {
    const v: any = props.eventStatuses
    if (Array.isArray(v) && v.length) return v
    const fromPage: any = page.props.eventStatuses ?? []
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage ?? {})
})

const eventTypesResolved = computed(() => {
    const v: any = props.eventTypes
    if (Array.isArray(v) && v.length) return v
    const fromPage: any = page.props.eventTypes ?? []
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage ?? {})
})

const first_project_calendar_tab_idResolved = computed(() => {
    return props.first_project_calendar_tab_id || page.props.first_project_calendar_tab_id || 0
})

const filterOptionsResolved = computed(() => props.filterOptions && Object.keys(props.filterOptions).length ? props.filterOptions : (page.props.filterOptions ?? {}))
const personalFiltersResolved = computed(() => props.personalFilters && Object.keys(props.personalFilters).length ? props.personalFilters : (page.props.personalFilters ?? {}))
const user_filtersResolved = computed(() => props.user_filters && Object.keys(props.user_filters).length ? props.user_filters : (page.props.user_filters ?? {}))

provide("event_properties",
    (props.event_properties && Object.keys(props.event_properties).length)
        ? props.event_properties
        : (page.props.event_properties ?? {})
)


/**
 * Lokaler State
 */
const showCalendarWarning = ref(props.calendarWarningText)

// Extra rows (KW-Trennzeilen der Wochenansicht) werden in der Tagesansicht nicht gerendert,
// haben aber kein withoutFormat/fullDay: ihr Lazy-Loading-Platzhalter (minHeight) würde nie
// aufgelöst (Observer-Key undefined) und erzeugt dauerhaften Leerraum → herausfiltern
const withoutExtraRows = (days: any[]) => (days ?? []).filter((d: any) => !d?.isExtraRow)

const daysLocal = shallowRef<any[]>(withoutExtraRows(props.days as any[]))

// G4: Stable empty array to avoid creating new references
const EMPTY_ARRAY: readonly any[] = Object.freeze([])

// shiftPlanCopy — shallowRef because the WebSocket listener mutates nested data in-place.
// We use eventsVersion to trigger rebuilds of the events index only when events change.
const shiftPlanCopy = shallowRef<any[]>(
    Array.isArray(props.shiftPlan) ? props.shiftPlan : Object.values(props.shiftPlan ?? {})
)
const eventsVersion = ref(0)

const shiftToEdit = ref(null)
const roomForShiftAdd = ref<number | null>(null)
const dayForShiftAdd = ref<string | null>(null)
const showAddShiftModal = ref(false)
const openExportDailyProjectShiftPlanModal = ref(false)

const dayForPreset = ref<any | null>(null)
const roomForPreset = ref<any | null>(null)
const showAddShiftByPresetOrGroupModal = ref(false)

const singleShiftPresetsLocal = ref<any[]>([])
const shiftGroupPresetsLocal = ref<any[]>([])

const singleShiftPresetsResolved = computed(() => {
    if (singleShiftPresetsLocal.value.length) return singleShiftPresetsLocal.value
    const v: any = props.singleShiftPresets
    if (Array.isArray(v) && v.length) return v
    if (v && Object.keys(v).length) return Object.values(v)
    const fromPage: any = page.props.singleShiftPresets ?? []
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage ?? {})
})

const shiftGroupPresetsResolved = computed(() => {
    if (shiftGroupPresetsLocal.value.length) return shiftGroupPresetsLocal.value
    const v: any = props.shiftGroupPresets
    if (Array.isArray(v) && v.length) return v
    if (v && Object.keys(v).length) return Object.values(v)
    const fromPage: any = page.props.shiftGroupPresets ?? []
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage ?? {})
})

const projectsResolved = computed(() => {
    const v: any = props.projects
    if (Array.isArray(v) && v.length) return v
    if (v && Object.keys(v).length) return Object.values(v)
    const fromPage: any = page.props.projects ?? []
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage ?? {})
})

const initialProjectIdResolved = computed(() => {
    return (
        (props.project as any)?.id ??
        props.projectId ??
        (page.props.projectId ?? null) ??
        ((page.props as any)?.currentProject?.id ?? null)
    )
})

const eventToEdit = ref<any | boolean>(false)
const wantedRoom = ref<number | null>(null)
const wantedDate = ref<string | null>(null)
const showEventComponent = ref(false)

const isPlanning = ref(false)
const roomCollisions = ref<any[]>([])
const dailyViewMode = ref<boolean>(page.props.auth.user.shift_plan_daily_view ?? false)

// ── P1: Lazy Day-Section Rendering via IntersectionObserver ──
const visibleDays = ref(new Set<string>())
const dayHeights = new Map<string, number>()

// G5: Estimated height for never-rendered days so they occupy realistic space
const ESTIMATED_ROOM_HEIGHT_PX = 200
const DAY_HEADER_HEIGHT_PX = 60

function estimateDayHeight(day: any): number {
    const roomCount = roomsForDayMap.value.get(day.fullDay)?.length
        ?? (shiftPlanCopy.value?.length ?? 5)
    return DAY_HEADER_HEIGHT_PX + roomCount * ESTIMATED_ROOM_HEIGHT_PX
}

// G2: Progressive room rendering — mount rooms in batches per frame
const visibleRoomCounts = ref(new Map<string, number>())
const INITIAL_ROOMS_BATCH = 4
let _isUnmounted = false

onBeforeUnmount(() => { _isUnmounted = true })

function progressivelyRevealRooms(dayKey: string) {
    if (_isUnmounted) return
    const day = daysLocal.value.find(d => d.withoutFormat === dayKey)
    if (!day) return
    const totalRooms = roomsForDayMap.value.get(day.fullDay)?.length ?? 0
    const current = visibleRoomCounts.value.get(dayKey) ?? 0
    if (current >= totalRooms) return

    requestAnimationFrame(() => {
        if (_isUnmounted) return
        const next = Math.min(current + 4, totalRooms)
        const updated = new Map(visibleRoomCounts.value)
        updated.set(dayKey, next)
        visibleRoomCounts.value = updated
        if (next < totalRooms) progressivelyRevealRooms(dayKey)
    })
}

function getRoomsToRender(day: any): any[] {
    const allRooms = roomsForDayMap.value.get(day.fullDay) || EMPTY_ARRAY
    const maxCount = visibleRoomCounts.value.get(day.withoutFormat)
    if (maxCount === undefined) return allRooms as any[]
    return allRooms.slice(0, maxCount)
}

const dayObserver = typeof IntersectionObserver !== 'undefined'
    ? new IntersectionObserver((entries) => {
        let changed = false
        for (const entry of entries) {
            const key = (entry.target as any).__dayKey
            if (!key) continue
            if (entry.isIntersecting) {
                if (!visibleDays.value.has(key)) { visibleDays.value.add(key); changed = true }
            } else {
                dayHeights.set(key, entry.target.getBoundingClientRect().height)
                if (visibleDays.value.has(key)) { visibleDays.value.delete(key); changed = true }
            }
        }
        if (changed) visibleDays.value = new Set(visibleDays.value)
    }, { rootMargin: '300px 0px' })
    : null

const setDayRef = (dayKey: string, el: HTMLElement | null) => {
    if (!dayObserver) return
    if (el) {
        (el as any).__dayKey = dayKey
        dayObserver.observe(el)
    } else if (el === null) {
        // el is null on unmount — no-op (we can't unobserve without the element ref)
    }
}

// G2: Watch visibleDays to progressively reveal rooms for newly visible days
watch(visibleDays, (newSet, oldSet) => {
    for (const dayKey of newSet) {
        if (!oldSet?.has(dayKey)) {
            visibleRoomCounts.value.set(dayKey, INITIAL_ROOMS_BATCH)
            progressivelyRevealRooms(dayKey)
        }
    }
    for (const [key] of visibleRoomCounts.value) {
        if (!newSet.has(key)) visibleRoomCounts.value.delete(key)
    }
})

// Key pro Tag+Raum (wichtig!)
const roomDayKey = (dayLabel: string, room: any) => {
    const roomId = room?.roomId ?? room?.id
    const dayIso = extractDate(dayLabel) ?? String(dayLabel)
    return `${dayIso}__${roomId}`
}

// Refs/State jetzt pro roomDayKey
const roomNameRefs = new Map<string, HTMLElement | null>()
const roomContainerRefs = new Map<string, HTMLElement | null>()
const roomNameTruncated = ref(new Map<string, boolean>())
const roomContainerHeights = ref(new Map<string, number>())

// ── P3: Single shared ResizeObserver for all room containers ──
const sharedRoomObserver = typeof ResizeObserver !== 'undefined'
    ? new ResizeObserver((entries) => {
        for (const entry of entries) {
            const key = (entry.target as any).__roomDayKey
            if (key) updateRoomContainerHeight(key)
        }
    })
    : null

const setRoomNameRef = (key: string, el: HTMLElement | null) => {
    if (el) {
        roomNameRefs.set(key, el)
        nextTick(() => checkRoomNameTruncation(key))
    } else {
        roomNameRefs.delete(key)
    }
}

const setRoomContainerRef = (key: string, el: HTMLElement | null) => {
    if (el) {
        (el as any).__roomDayKey = key
        roomContainerRefs.set(key, el)
        nextTick(() => updateRoomContainerHeight(key))
        sharedRoomObserver?.observe(el)
    } else {
        const old = roomContainerRefs.get(key)
        if (old) sharedRoomObserver?.unobserve(old)
        roomContainerRefs.delete(key)
    }
}

const updateRoomContainerHeight = (key: string) => {
    const el = roomContainerRefs.get(key)
    if (!el) return
    roomContainerHeights.value.set(key, el.clientHeight)
    checkRoomNameTruncation(key)
}

const getRoomNameMaxWidth = (key: string): string => {
    const height = roomContainerHeights.value.get(key)
    if (height && height > 48) return `${Math.max(height - 32, 0)}px` // py-4 abziehen
    return "4rem"
}

const checkRoomNameTruncation = (key: string) => {
    const el = roomNameRefs.get(key)
    if (!el) {
        roomNameTruncated.value.set(key, false)
        return
    }
    const truncated = el.scrollWidth > el.clientWidth || el.scrollHeight > el.clientHeight
    roomNameTruncated.value.set(key, truncated)
}

const checkAllRoomNameTruncations = () => {
    roomContainerRefs.forEach((_, key) => updateRoomContainerHeight(key))
    roomNameRefs.forEach((_, key) => checkRoomNameTruncation(key))
}

const isRoomNameTruncated = (key: string) => roomNameTruncated.value.get(key) ?? false

/**
 * helper: extractDate
 */
function extractDate(raw: any): string | null {
    if (!raw) return null
    const s = String(raw).trim()
    const datePart = s.split(" ")[0]

    if (/^\d{4}-\d{2}-\d{2}$/.test(datePart)) return datePart

    if (/^\d{2}\.\d{2}\.\d{4}$/.test(datePart)) {
        const [day, month, year] = datePart.split(".")
        return `${year}-${month}-${day}`
    }

    return null
}

/** Resolve events for a room+day from eventsById + content[day].eventIds (Option B deduplication) */
function getRoomDayEvents(room: any, day: string): any[] {
    if (!room?.eventsById || !room?.content?.[day]?.eventIds) return []
    return room.content[day].eventIds.map((id: number) => room.eventsById[id]).filter(Boolean)
}

/** Resolve shifts for a room+day from shiftsById + content[day].shiftIds (Option B deduplication) */
function getRoomDayShifts(room: any, day: string): any[] {
    if (!room?.shiftsById || !room?.content?.[day]?.shiftIds) return []
    let shifts = room.content[day].shiftIds.map((id: number) => room.shiftsById[id]).filter(Boolean)

    // Filter: nur nicht voll besetzte Schichten anzeigen
    const showOnlyNotFullyStaffed = (page.props.shift_plan_daily_settings ?? page.props.shift_plan_settings ?? page.props.auth?.user?.calendar_settings)?.show_only_not_fully_staffed_shifts
    if (showOnlyNotFullyStaffed) {
        shifts = shifts.filter((shift: any) => {
            const qualifications = Array.isArray(shift?.shifts_qualifications)
                ? shift.shifts_qualifications
                : Object.values(shift?.shifts_qualifications || {})

            return qualifications.some((q: any) => {
                const capacity = q?.value ?? 0
                const qualificationId = q?.shift_qualification_id

                const assignedCount = ['users', 'freelancer', 'serviceProviders'].reduce((acc, group) => {
                    const items = shift[group] || []
                    return acc + items.filter((item: any) => item?.pivot?.shift_qualification_id === qualificationId).length
                }, 0)

                return capacity > assignedCount
            })
        })
    }

    return shifts
}

// P4: Optimized event index builder — cached extractDate, native Date loop, dedup multi-day
function buildRoomDayEventsIndex(rooms: AnyRoom[]): Map<any, Map<string, AnyEvent[]>> {
    const index = new Map<any, Map<string, AnyEvent[]>>()
    const dateCache = new Map<string, string | null>()

    const extractDateCached = (raw: any): string | null => {
        const key = String(raw ?? '')
        let result = dateCache.get(key)
        if (result === undefined) { result = extractDate(raw); dateCache.set(key, result) }
        return result
    }

    const push = (roomId: any, dayIso: string, ev: AnyEvent) => {
        let byDay = index.get(roomId)
        if (!byDay) { byDay = new Map(); index.set(roomId, byDay) }
        let arr = byDay.get(dayIso)
        if (!arr) { arr = []; byDay.set(dayIso, arr) }
        if (ev?.id != null && arr.some(x => x?.id === ev.id)) return
        arr.push(ev)
    }

    const MS_PER_DAY = 86_400_000

    for (const room of rooms || []) {
        const roomId = room.roomId ?? room.id
        const content = room?.content || {}
        const processedMultiDay = new Set<number>()

        for (const dayKey of Object.keys(content)) {
            const dayIso = extractDateCached(dayKey)
            const dayEvents: AnyEvent[] = getRoomDayEvents(room, dayKey)

            if (dayIso) {
                for (const ev of dayEvents) push(roomId, dayIso, ev)
            }

            for (const ev of dayEvents) {
                if (ev?.id != null && processedMultiDay.has(ev.id)) continue

                const startIso = extractDateCached(ev.start || ev.startDate || ev.start_date)
                const endIso   = extractDateCached(ev.end   || ev.endDate   || ev.end_date)
                if (!startIso || !endIso || startIso === endIso) continue

                if (ev?.id != null) processedMultiDay.add(ev.id)

                const startMs = new Date(startIso + 'T00:00:00').getTime()
                const endMs = new Date(endIso + 'T00:00:00').getTime()
                if (isNaN(startMs) || isNaN(endMs)) continue

                for (let ms = startMs; ms <= endMs; ms += MS_PER_DAY) {
                    const d = new Date(ms)
                    const ymd = d.getFullYear() + '-' +
                        String(d.getMonth() + 1).padStart(2, '0') + '-' +
                        String(d.getDate()).padStart(2, '0')
                    push(roomId, ymd, ev)
                }
            }
        }
    }

    return index
}

/**
 * Initial load
 */
const initializeDailyShiftPlan = async () => {
    const hasInitialDays = Array.isArray(props.days) && props.days.length > 0
    const hasInitialShiftPlan =
        props.shiftPlan &&
        (Array.isArray(props.shiftPlan) ? props.shiftPlan.length > 0 : Object.keys(props.shiftPlan).length > 0)

    if (!hasInitialDays || !hasInitialShiftPlan) {
        const baseParams = {
            start_date: props.dateValue?.[0],
            end_date: props.dateValue?.[1],
            projectId: (props.project as any)?.id ?? page.props.currentProject?.id ?? null,
            isInProjectView: props.isInProjectView,
        }

        const { data: metaData } = await axios.get(route("shift.plan.meta"), {
            params: baseParams,
        })

        const metaRooms = metaData.rooms ?? []
        daysLocal.value = withoutExtraRows(enrichDays(metaData.days ?? []))

        // Show skeleton rooms immediately while batch loads
        shiftPlanCopy.value = metaRooms.map((r: any) => ({
            roomId: r.roomId,
            roomName: r.roomName,
            content: {},
        }))

        if (metaData.singleShiftPresets) {
            singleShiftPresetsLocal.value = Array.isArray(metaData.singleShiftPresets)
                ? metaData.singleShiftPresets
                : Object.values(metaData.singleShiftPresets ?? {})
        }
        if (metaData.shiftGroupPresets) {
            shiftGroupPresetsLocal.value = Array.isArray(metaData.shiftGroupPresets)
                ? metaData.shiftGroupPresets
                : Object.values(metaData.shiftGroupPresets ?? {})
        }

        // Single batch request instead of N per-room requests — shares one buildShiftPlanContext
        const { data: batchData } = await axios.get(route("shift.plan.rooms.batch"), {
            params: baseParams,
        })

        if (batchData.lookups) mergeLookups(batchData.lookups)
        shiftPlanCopy.value = (batchData.rooms ?? []).filter(Boolean)
        triggerRef(shiftPlanCopy)
        return
    }

    daysLocal.value = withoutExtraRows(enrichDays(props.days ?? []))
    shiftPlanCopy.value = Array.isArray(props.shiftPlan) ? props.shiftPlan : Object.values(props.shiftPlan ?? {})
    triggerRef(shiftPlanCopy)
}

watch(() => props.days, (v) => { daysLocal.value = withoutExtraRows(v as any[]) })

// Zeitraum geändert (z.B. Schicht außerhalb des Projektzeitraums angelegt → headerObject/dateRange
// aktualisiert): Daten für den neuen Zeitraum nachladen
watch(() => [props.dateValue?.[0], props.dateValue?.[1]], ([newStart, newEnd], [oldStart, oldEnd]) => {
    if (newStart !== oldStart || newEnd !== oldEnd) initializeDailyShiftPlan()
})
watch(() => props.shiftPlan, (v) => {
    shiftPlanCopy.value = Array.isArray(v) ? v : Object.values(v ?? {})
    triggerRef(shiftPlanCopy)
})

/**
 * Craft filter set
 */
const craftIdSet = computed<Set<any>>(() => {
    const ids = (user_filtersResolved.value as any)?.craft_ids
    return new Set(Array.isArray(ids) ? ids : [])
})

const craftPositionMap = computed(() => {
    const map = new Map<number, number>()
    for (const c of craftsResolved.value ?? []) {
        if (c?.id != null) map.set(c.id, c.position ?? 0)
    }
    return map
})

// P2: Pre-computed index — filter + sort once, then O(1) lookup
// Debounced to prevent rapid rebuilds during WebSocket bursts
const filteredShiftsIndex = shallowRef(new Map<string, any[]>())

function rebuildFilteredShiftsIndex() {
    const map = new Map<string, any[]>()
    const rooms = shiftPlanCopy.value || []
    const set = craftIdSet.value
    const posMap = craftPositionMap.value
    const settings = page.props.shift_plan_daily_settings ?? page.props.shift_plan_settings ?? page.props.auth?.user?.calendar_settings
    const showOnlyNotFullyStaffed = (settings as any)?.show_only_not_fully_staffed_shifts

    const getShiftCraftId = (s: any): number | null => {
        return s?.craftId ?? s?.craft_id ?? s?.craft?.id ?? null
    }

    const getCraftPos = (s: any): number => {
        const craftId = getShiftCraftId(s)
        return (craftId != null && posMap.has(craftId)) ? posMap.get(craftId)! : (s?.craft?.position ?? 9999)
    }

    for (const room of rooms) {
        const roomId = room.roomId ?? room.id
        const content = room?.content || {}
        const shiftsById = room?.shiftsById || {}

        for (const dayKey of Object.keys(content)) {
            const dayData = content[dayKey]
            if (!dayData?.shiftIds?.length) continue

            let shifts = dayData.shiftIds.map((id: number) => shiftsById[id]).filter(Boolean)

            if (set.size > 0) shifts = shifts.filter((s: any) => {
                const cid = getShiftCraftId(s)
                return cid != null && set.has(cid)
            })

            if (showOnlyNotFullyStaffed) {
                shifts = shifts.filter((shift: any) => {
                    const qualifications = Array.isArray(shift?.shifts_qualifications)
                        ? shift.shifts_qualifications
                        : Object.values(shift?.shifts_qualifications || {})
                    return qualifications.some((q: any) => {
                        const capacity = q?.value ?? 0
                        const qualificationId = q?.shift_qualification_id
                        const assignedCount = ['users', 'freelancer', 'serviceProviders'].reduce((acc, group) => {
                            const items = shift[group] || []
                            return acc + items.filter((item: any) => item?.pivot?.shift_qualification_id === qualificationId).length
                        }, 0)
                        return capacity > assignedCount
                    })
                })
            }

            shifts.sort((a: any, b: any) => {
                const cmp = (a.start ?? '').toString().localeCompare((b.start ?? '').toString())
                if (cmp !== 0) return cmp
                const posCmp = getCraftPos(a) - getCraftPos(b)
                return posCmp !== 0 ? posCmp : (a.id ?? 0) - (b.id ?? 0)
            })

            map.set(`${roomId}|${dayKey}`, shifts)
        }
    }
    filteredShiftsIndex.value = map
}

let _shiftsRebuildTimer: ReturnType<typeof setTimeout> | null = null
let _shiftsFirstRun = true
watch([shiftPlanCopy, craftIdSet, craftPositionMap], () => {
    if (_shiftsFirstRun) {
        _shiftsFirstRun = false
        rebuildFilteredShiftsIndex()
        return
    }
    if (_shiftsRebuildTimer) clearTimeout(_shiftsRebuildTimer)
    _shiftsRebuildTimer = setTimeout(() => {
        rebuildFilteredShiftsIndex()
        _shiftsRebuildTimer = null
    }, 200)
}, { immediate: true })

function getFilteredShiftsForRoomDay(room: any, dayLabel: string): any[] {
    const roomId = room?.roomId ?? room?.id
    return filteredShiftsIndex.value.get(`${roomId}|${dayLabel}`) ?? EMPTY_ARRAY as any[]
}

/**
 * Hide empty rooms
 */
const hideUnoccupiedRooms = computed<boolean>(() => {
    return (page.props.shift_plan_daily_settings ?? page.props.shift_plan_settings ?? page.props.auth?.user?.calendar_settings)?.hide_unoccupied_rooms === true
})

/**
 * Events index
 */
const roomDayEventsIndex = shallowRef<Map<any, Map<string, AnyEvent[]>>>(new Map())

// Rebuild events index — debounced to batch rapid WebSocket updates
let _eventsRebuildTimer: ReturnType<typeof setTimeout> | null = null
let _eventsFirstRun = true
watch(
    [eventsVersion, shiftPlanCopy],
    () => {
        if (_eventsFirstRun) {
            _eventsFirstRun = false
            roomDayEventsIndex.value = buildRoomDayEventsIndex(shiftPlanCopy.value || [])
            return
        }
        if (_eventsRebuildTimer) clearTimeout(_eventsRebuildTimer)
        _eventsRebuildTimer = setTimeout(() => {
            roomDayEventsIndex.value = buildRoomDayEventsIndex(shiftPlanCopy.value || [])
            _eventsRebuildTimer = null
        }, 150)
    },
    { immediate: true }
)

// P2: Pre-computed dayIso cache to avoid repeated extractDate calls
const dayIsoCache = computed(() => {
    const map = new Map<string, string>()
    for (const d of daysLocal.value || []) {
        const iso = extractDate(d.fullDay)
        if (iso) map.set(d.fullDay, iso)
    }
    return map
})

function getEventsForRoomDay(room: any, targetDay: string): AnyEvent[] {
    const roomId = room?.roomId ?? room?.id
    const dayIso = dayIsoCache.value.get(targetDay) ?? extractDate(targetDay)
    if (!dayIso) return []
    return roomDayEventsIndex.value.get(roomId)?.get(dayIso) ?? EMPTY_ARRAY as any[]
}

/**
 * roomsForDayMap
 */
const roomsForDayMap = computed<Map<string, AnyRoom[]>>(() => {
    const map = new Map<string, AnyRoom[]>()
    const rooms = shiftPlanCopy.value || []

    if (!hideUnoccupiedRooms.value) {
        for (const d of (daysLocal.value || [])) map.set(d.fullDay, rooms)
        return map
    }

    for (const d of (daysLocal.value || [])) {
        const dayLabel = d.fullDay
        const filtered = rooms.filter((room: any) => {
            const events = getEventsForRoomDay(room, dayLabel)
            const shifts = getFilteredShiftsForRoomDay(room, dayLabel)
            return (events?.length ?? 0) > 0 || (shifts?.length ?? 0) > 0
        })
        map.set(dayLabel, filtered)
    }

    return map
})

const isDayWithoutRooms = (dayLabel: string): boolean => {
    return (roomsForDayMap.value.get(dayLabel)?.length ?? 0) === 0
}

/**
 * Hide unoccupied days — Tage ohne jeglichen Termin oder Schicht komplett ausblenden
 */
const hideUnoccupiedDays = computed<boolean>(() => {
    return (page.props.shift_plan_daily_settings ?? page.props.shift_plan_settings ?? page.props.auth?.user?.calendar_settings)?.hide_unoccupied_days === true
})

const dayHasContent = (dayLabel: string): boolean => {
    for (const room of shiftPlanCopy.value || []) {
        if ((getEventsForRoomDay(room, dayLabel)?.length ?? 0) > 0) return true
        if ((getFilteredShiftsForRoomDay(room, dayLabel)?.length ?? 0) > 0) return true
    }
    return false
}

const daysToRender = computed<any[]>(() => {
    if (!hideUnoccupiedDays.value) return daysLocal.value
    return (daysLocal.value || []).filter((d: any) => d.isExtraRow || dayHasContent(d.fullDay))
})

/**
 * roomsArray for EventComponent
 */
const roomsArray = computed(() => {
    const from = roomsResolved.value
    if (from && from.length) {
        return from.map((r: any) => ({ id: r.id ?? r.roomId, name: r.name ?? r.roomName }))
    }
    const derived = (shiftPlanCopy.value || []).map((r: any) => ({ id: r.roomId ?? r.id, name: r.roomName ?? r.name }))
    const m = new Map<any, any>()
    derived.forEach(r => { if (r?.id != null) m.set(r.id, r) })
    return Array.from(m.values())
})

// Provide rooms for child components (e.g. SingleShiftInDailyShiftView's AddShiftModal)
provide("shiftPlanRooms", roomsArray)

const shiftQualificationsArray = computed(() =>
    Array.isArray(shiftQualificationsResolved.value)
        ? shiftQualificationsResolved.value
        : Object.values(shiftQualificationsResolved.value || {})
)

/**
 * Modals
 */
const openAddShiftForRoomAndDay = (day: string | null, roomId: number | null) => {
    shiftToEdit.value = null
    roomForShiftAdd.value = roomId
    dayForShiftAdd.value = day
    showAddShiftModal.value = true
}

const openAddShiftByPresetOrGroup = (day: any, room: any) => {
    dayForPreset.value = day
    roomForPreset.value = room
    showAddShiftByPresetOrGroupModal.value = true
}

const closeAddShiftModal = (success = false, shift = null) => {
    // Erstellung über den Topbar-Button (freie Datumswahl, kein Tag/keine Schicht vorgegeben):
    // Der Store-Request liefert keine neuen Props; der Broadcast erreicht nur bereits
    // gerenderte Tage. Daher headerObject (Zeitraum-Quelle) nachladen und Daten neu holen.
    const wasFreeDateCreate = success
        && showAddShiftModal.value
        && shiftToEdit.value === null
        && dayForShiftAdd.value === null

    if (success && shift) {
        const room = shiftPlanCopy.value.find((r: any) => (r.roomId ?? r.id) === shift.roomId)
        if (room) {
            if (room.shiftsById) room.shiftsById[shift.id] = shift
            if (room.eventsById && shift.eventId && room.eventsById[shift.eventId]) {
                const event = room.eventsById[shift.eventId]
                if (Array.isArray(event.shifts)) {
                    const idx = event.shifts.findIndex((s: any) => s.id === shift.id)
                    if (idx !== -1) event.shifts[idx] = shift
                }
            }
        }
        triggerRef(shiftPlanCopy)
    }
    showAddShiftModal.value = false
    shiftToEdit.value = null
    roomForShiftAdd.value = null
    dayForShiftAdd.value = null

    if (wasFreeDateCreate && props.isInProjectView) {
        const prevStart = props.dateValue?.[0]
        const prevEnd = props.dateValue?.[1]
        router.reload({
            only: ['headerObject'],
            onFinish: () => nextTick(() => {
                // Zeitraum unverändert (Schicht innerhalb): der dateValue-Watcher feuert
                // nicht, daher hier explizit neu laden. Bei geändertem Zeitraum lädt
                // bereits der Watcher.
                if (props.dateValue?.[0] === prevStart && props.dateValue?.[1] === prevEnd) {
                    initializeDailyShiftPlan()
                }
            }),
        })
    }
}

const openNewEventModalWithBaseData = (day: string, roomId: number | null) => {
    eventToEdit.value = false
    wantedRoom.value = roomId
    wantedDate.value = day
    showEventComponent.value = true
}

const eventComponentClosed = () => {
    showEventComponent.value = false
    eventToEdit.value = false
    wantedRoom.value = null
    wantedDate.value = null
}

/**
 * Daily view mode
 */
const changeDailyViewMode = () => {
    router.patch(
        route("user.update.daily_view", page.props.auth.user.id),
        { daily_view: dailyViewMode.value, context: 'shift_plan' },
        { preserveScroll: false, preserveState: false }
    )
}

const changeDailyViewModeValue = (newValue: boolean, onSuccessCallback?: () => void) => {
    dailyViewMode.value = newValue
    router.patch(
        route("user.update.daily_view", page.props.auth.user.id),
        { daily_view: dailyViewMode.value, context: 'shift_plan' },
        { preserveScroll: false, preserveState: false, onSuccess: onSuccessCallback }
    )
}

/**
 * Shortcuts
 */
const jumpToToday = () => {
    const today = new Date().toISOString().slice(0, 10)
    const patchDates = () => {
        router.patch(
            route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
            { start_date: today, end_date: today, isDailyView: true },
            { preserveScroll: true, preserveState: false }
        )
    }

    if (!dailyViewMode.value) {
        changeDailyViewModeValue(true, patchDates)
        return
    }

    patchDates()
}

const jumpToCurrentWeek = () => {
    const today = new Date()
    const currentWeekStart = new Date(today)
    const currentWeekEnd = new Date(today)

    const dayOfWeek = today.getDay()
    const daysToMonday = dayOfWeek === 0 ? 6 : dayOfWeek - 1
    currentWeekStart.setDate(today.getDate() - daysToMonday)

    const daysToSunday = dayOfWeek === 0 ? 0 : 7 - dayOfWeek
    currentWeekEnd.setDate(today.getDate() + daysToSunday)

    router.patch(
        route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
        {
            start_date: currentWeekStart.toISOString().slice(0, 10),
            end_date: currentWeekEnd.toISOString().slice(0, 10),
            isDailyView: true,
        },
        { preserveScroll: true, preserveState: false }
    )
}

const jumpToCurrentMonth = () => {
    const today = new Date()
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0)

    const patchDates = () => {
        router.patch(
            route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
            {
                start_date: monthStart.toISOString().slice(0, 10),
                end_date: monthEnd.toISOString().slice(0, 10),
                isDailyView: true,
            },
            { preserveScroll: true, preserveState: false }
        )
    }

    if (dailyViewMode.value) {
        changeDailyViewModeValue(false, patchDates)
        return
    }

    patchDates()
}

/**
 * Projektzeitraum
 */
const projectStart = computed(() => {
    const headerObj: any = (page.props as any)?.headerObject || {}
    const fromRange = Array.isArray(props.dateValue) ? props.dateValue[0] : null
    const fromHeader = headerObj?.firstEventInProject?.start_time
    const p: any = props.project || {}
    return fromRange || fromHeader || p.start_date || p.startDate || p.start || p.starts_at || p.startsAt || null
})

const projectEnd = computed(() => {
    const headerObj: any = (page.props as any)?.headerObject || {}
    const fromRange = Array.isArray(props.dateValue) ? props.dateValue[1] : null
    const fromHeader = headerObj?.lastEventInProject?.end_time
    const p: any = props.project || {}
    return fromRange || fromHeader || p.end_date || p.endDate || p.end || p.ends_at || p.endsAt || null
})

function formatDate(dateLike: any) {
    if (!dateLike) return "-"
    const d = dayjs(dateLike)
    return d.isValid() ? d.format("DD.MM.YYYY") : "-"
}

/**
 * Sticky / Toolbar height
 */
const topBarContainerClass = computed(() => {
    if (props.project) return "w-full sticky top-0 z-40 px-3 pt-2 pb-2 bg-white"
    return "card glassy p-4 bg-white/50 w-full sticky top-0 z-40 !rounded-t-none"
})

const topBarStyle = computed(() => {
    if (props.isInProjectView) {
        return { top: 'var(--project-header-height, 130px)' }
    }
    return { top: `${props.stickyOffsetTopPx}px` }
})

const topBarEl = ref<HTMLElement | null>(null)
const topBarHeightPx = ref<number>(72)

let ro: ResizeObserver | null = null

function measureTopBarHeight() {
    const h = topBarEl.value?.offsetHeight
    if (typeof h === "number" && h > 0 && h !== topBarHeightPx.value) topBarHeightPx.value = h
}

const downloadShiftPersonnelPlanXLSX = () => {
    const url = route("projects.exports.shifts-personal-plan", props.project.id)
    window.open(url, "_blank")
}

onMounted(async () => {
    setTimeout(() => { showCalendarWarning.value = "" }, 5000)

    // Load shift plan data and crafts in parallel. Die Worker-Daten (shifts.workers)
    // werden hier bewusst NICHT geladen: das Unbesetzt-Dropdown zieht seine Kandidaten
    // aus shifts.crafts, Kollisionen kommen on-demand über shift.check-collisions.
    await Promise.all([
        initializeDailyShiftPlan(),
        axios.get(route("shifts.crafts"), { params: { lightweight: 1 } }).then(({ data }) => {
            craftsLoaded.value = data.crafts ?? []
        }).catch(() => { craftsLoaded.value = [] }),
        loadProjectDayAssignments(),
    ])

    const ShiftCalendarListener = useShiftCalendarListener(shiftPlanCopy as any, {
        onEventsChanged: () => { eventsVersion.value++ },
        onShiftDataChanged: () => { triggerRef(shiftPlanCopy) },
        onLookupsReceived: mergeLookups,
    })
    ShiftCalendarListener.init()

    await nextTick()
    measureTopBarHeight()
    checkAllRoomNameTruncations()

    if (topBarEl.value && "ResizeObserver" in window) {
        ro = new ResizeObserver(() => measureTopBarHeight())
        ro.observe(topBarEl.value)
    } else {
        window.addEventListener("resize", measureTopBarHeight)
    }
    window.addEventListener("resize", checkAllRoomNameTruncations)
})

onUnmounted(() => {
    if (ro && topBarEl.value) ro.unobserve(topBarEl.value)
    ro = null
    window.removeEventListener("resize", measureTopBarHeight)
    window.removeEventListener("resize", checkAllRoomNameTruncations)
    sharedRoomObserver?.disconnect()
    dayObserver?.disconnect()
})



const dayHeaderStyle = computed(() => {
    if (props.isInProjectView) {
        return { top: `calc(var(--project-header-height, 130px) + ${topBarHeightPx.value}px)` }
    }
    return { top: `${props.stickyOffsetTopPx + topBarHeightPx.value}px` }
})
</script>

<style scoped>
/* optional */
</style>
