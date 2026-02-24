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
                            <date-picker-component :date-value-array="props.dateValue" :is_shift_plan="true"/>

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
                        </template>
                    </div>

                    <div class="flex items-center gap-x-5">
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
                            :filter-type="props.isInProjectView ? 'project_shift_filter' : 'shift_filter'"
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

                        <FunctionBarSetting :is-planning="false" is-in-shift-plan />
                    </div>
                </div>
            </div>

            <div
                v-for="day in daysLocal"
                :key="day.withoutFormat"
                class="flex flex-col w-full h-full relative ml-1"
                :class="props.isInProjectView ? 'mt-5' : ''"
            >
                <div v-if="!day.isExtraRow">
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
                            </div>

                            <div class="font-lexend text-sm font-bold py-4 text-center shrink-0 px-4">
                                {{ day.dayString }}, {{ day.fullDay }}
                            </div>

                            <div class="flex items-center justify-end min-w-0 flex-1">
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

                    <div
                        class="grid grid-cols-[3rem_1fr] ml-1"
                        v-for="room in (roomsForDayMap.get(day.fullDay) || [])"
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
                                    :px-per-min="1.0"
                                    :gap-threshold-min="90"
                                    @addEvent="openNewEventModalWithBaseData(day.withoutFormat, room.roomId)"
                                    @addShift="openAddShiftForRoomAndDay(day.withoutFormat, room.roomId)"
                                    @addShiftByPresetOrGroup="openAddShiftByPresetOrGroup(day, room)"
                                />
                            </div>
                        </div>
                    </div>
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
                :calendarProjectPeriod="pageProps.auth.user.calendar_settings.use_project_time_period"
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
import DatePickerComponent from "@/Layouts/Components/DatePickerComponent.vue";
import { ref, provide, onMounted, onUnmounted, watch, computed, nextTick, shallowRef } from "vue";
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
    IconX, IconFileExport,
    IconCalendarPlus,
    IconCalendarUser, IconFileTypeXls,
} from "@tabler/icons-vue";
import { useShiftCalendarListener } from "@/Composeables/Listener/useShiftCalendarListener.js";
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import FunctionBarSetting from "@/Artwork/Filter/FunctionBarSetting.vue";
import SwitchIconTooltip from "@/Artwork/Toggles/SwitchIconTooltip.vue";
import axios from "axios";
import DailyRoomSplitTimeline from "@/Pages/Shifts/DailyViewComponents/DailyRoomSplitTimeline.vue";
import dayjs from "dayjs";
import {can, is} from "laravel-permission-to-vuejs";
import ExportDailyProjectShiftPlanModal from "@/Pages/Projects/Components/ExportDailyProjectShiftPlanModal.vue";
import AddShiftsByPresetsAndGroupsModal from "@/Pages/Shifts/Components/AddShiftsByPresetsAndGroupsModal.vue";

type AnyRoom = any
type AnyEvent = any

const page = usePage()
const pageProps = computed(() => page.props).value


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


const shiftQualificationsResolved = computed(() => {
    const v: any = props.shiftQualifications
    if (Array.isArray(v)) return v
    if (v && Object.keys(v).length) return Object.values(v)
    const fromPage: any = page.props.shiftQualifications ?? {}
    return Array.isArray(fromPage) ? fromPage : Object.values(fromPage)
})

// Crafts loaded asynchronously from API
const craftsLoaded = ref<any[]>([])
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
    return result.slice().sort((a: any, b: any) => (a.position ?? 0) - (b.position ?? 0))
})

// Workers loaded asynchronously from API (craft-first), für spätere Nutzung vorbereitet
const workersLoaded = ref<{
    usersForShifts: any[]
    freelancersForShifts: any[]
    serviceProvidersForShifts: any[]
}>({
    usersForShifts: [],
    freelancersForShifts: [],
    serviceProvidersForShifts: [],
})
const usersForShiftsResolved = computed(() => workersLoaded.value.usersForShifts)
const freelancersForShiftsResolved = computed(() => workersLoaded.value.freelancersForShifts)
const serviceProvidersForShiftsResolved = computed(() => workersLoaded.value.serviceProvidersForShifts)

async function loadShiftPlanWorkers() {
    const start = Array.isArray(props.dateValue) ? props.dateValue[0] : null
    const end = Array.isArray(props.dateValue) ? props.dateValue[1] : null
    if (!start || !end) return
    try {
        const params: Record<string, any> = { start_date: start, end_date: end }
        const craftIds = (user_filtersResolved.value as any)?.craft_ids
        if (Array.isArray(craftIds) && craftIds.length) params.craft_ids = craftIds
        const { data } = await axios.get(route("shifts.workers"), { params })
        workersLoaded.value = {
            usersForShifts: data.usersForShifts ?? [],
            freelancersForShifts: data.freelancersForShifts ?? [],
            serviceProvidersForShifts: data.serviceProvidersForShifts ?? [],
        }
    } catch {
        workersLoaded.value = {
            usersForShifts: [],
            freelancersForShifts: [],
            serviceProvidersForShifts: [],
        }
    }
}

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

provide("event_properties", computed(() => {
    return (props.event_properties && Object.keys(props.event_properties).length)
        ? props.event_properties
        : (page.props.event_properties ?? {})
}).value)

/**
 * Lokaler State
 */
const showCalendarWarning = ref(props.calendarWarningText)
const daysLocal = shallowRef<any[]>(props.days ?? [])

// shiftPlanCopy
// WICHTIG: muss tief reaktiv sein, da Websocket-Listener eventsById/shiftsById und content[day].eventIds/shiftIds mutiert.
const shiftPlanCopy = ref<any[]>(
    Array.isArray(props.shiftPlan) ? props.shiftPlan : Object.values(props.shiftPlan ?? {})
)

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
const dailyViewMode = ref<boolean>(page.props.auth.user.daily_view ?? false)

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

// ResizeObserver pro Zeile (damit Höhe mitwächst, wenn Timelines sich ändern)
const roomResizeObservers = new Map<string, ResizeObserver>()

const setRoomNameRef = (key: string, el: HTMLElement | null) => {
    if (el) {
        roomNameRefs.set(key, el)
        nextTick(() => checkRoomNameTruncation(key))
    } else {
        roomNameRefs.delete(key)
    }
}

const setRoomContainerRef = (key: string, el: HTMLElement | null) => {
    // alten Observer weg
    const old = roomResizeObservers.get(key)
    if (old) old.disconnect()
    roomResizeObservers.delete(key)

    if (el) {
        roomContainerRefs.set(key, el)

        // direkt messen
        nextTick(() => updateRoomContainerHeight(key))

        // bei jeder Größenänderung neu messen
        if ("ResizeObserver" in window) {
            const ro = new ResizeObserver(() => updateRoomContainerHeight(key))
            ro.observe(el)
            roomResizeObservers.set(key, ro)
        }
    } else {
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
    const showOnlyNotFullyStaffed = page.props.auth?.user?.calendar_settings?.show_only_not_fully_staffed_shifts
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

function buildRoomDayEventsIndex(rooms: AnyRoom[]): Map<any, Map<string, AnyEvent[]>> {
    const index = new Map<any, Map<string, AnyEvent[]>>()

    const push = (roomId: any, dayIso: string, ev: AnyEvent) => {
        if (!index.has(roomId)) index.set(roomId, new Map())
        const byDay = index.get(roomId)!
        if (!byDay.has(dayIso)) byDay.set(dayIso, [])
        const arr = byDay.get(dayIso)!
        if (ev?.id != null && arr.some(x => x?.id === ev.id)) return
        arr.push(ev)
    }

    for (const room of rooms || []) {
        const roomId = room.roomId ?? room.id
        const content = room?.content || {}

        for (const dayKey of Object.keys(content)) {
            const dayIso = extractDate(dayKey)
            const dayEvents: AnyEvent[] = getRoomDayEvents(room, dayKey)

            if (dayIso) {
                for (const ev of dayEvents) push(roomId, dayIso, ev)
            }

            for (const ev of dayEvents) {
                const startIso = extractDate(ev.start || ev.startDate || ev.start_date)
                const endIso   = extractDate(ev.end   || ev.endDate   || ev.end_date)
                if (!startIso || !endIso || startIso === endIso) continue

                let d = dayjs(startIso)
                const end = dayjs(endIso)
                if (!d.isValid() || !end.isValid()) continue

                while (d.isBefore(end) || d.isSame(end, "day")) {
                    push(roomId, d.format("YYYY-MM-DD"), ev)
                    d = d.add(1, "day")
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
        daysLocal.value = metaData.days ?? []

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

        const roomPayloads = await Promise.all(
            metaRooms.map((r: any) =>
                axios
                    .get(route("shift.plan.room"), {
                        params: { ...baseParams, room_id: r.roomId },
                    })
                    .then((res) => res.data.room),
            ),
        )

        shiftPlanCopy.value = roomPayloads.filter(Boolean)
        return
    }

    daysLocal.value = props.days ?? []
    shiftPlanCopy.value = Array.isArray(props.shiftPlan) ? props.shiftPlan : Object.values(props.shiftPlan ?? {})
}

watch(() => props.days, (v) => { daysLocal.value = v ?? [] })
watch(() => props.shiftPlan, (v) => {
    shiftPlanCopy.value = Array.isArray(v) ? v : Object.values(v ?? {})
})

/**
 * Craft filter set
 */
const craftIdSet = computed<Set<any>>(() => {
    const ids = (user_filtersResolved.value as any)?.craft_ids
    return new Set(Array.isArray(ids) ? ids : [])
})

function getFilteredShiftsForRoomDay(room: any, dayLabel: string): any[] {
    const shiftsRaw: any[] = getRoomDayShifts(room, dayLabel)
    const set = craftIdSet.value
    const filtered = set.size === 0 ? shiftsRaw : shiftsRaw.filter(s => set.has(s?.craft?.id))

    // Sortierung: primär nach Startzeit, sekundär nach Craft-Position aus Settings
    const craftPositionMap = new Map<number, number>()
    for (const c of craftsResolved.value ?? []) {
        if (c?.id != null) craftPositionMap.set(c.id, c.position ?? 0)
    }
    const getCraftPos = (s: any): number => {
        const craftId = s?.craft?.id ?? s?.craft_id
        if (craftId != null && craftPositionMap.has(craftId)) return craftPositionMap.get(craftId)!
        return s?.craft?.position ?? 9999
    }

    return filtered.slice().sort((a: any, b: any) => {
        const aStart = (a.start ?? '').toString()
        const bStart = (b.start ?? '').toString()
        if (aStart !== bStart) return aStart.localeCompare(bStart)

        const aPos = getCraftPos(a)
        const bPos = getCraftPos(b)
        if (aPos !== bPos) return aPos - bPos

        return (a.id ?? 0) - (b.id ?? 0)
    })
}

/**
 * Hide empty rooms
 */
const hideUnoccupiedRooms = computed<boolean>(() => {
    return page.props.auth?.user?.calendar_settings?.hide_unoccupied_rooms === true
})

/**
 * Events index
 */
const roomDayEventsIndex = shallowRef<Map<any, Map<string, AnyEvent[]>>>(new Map())

watch(
    shiftPlanCopy,
    () => { roomDayEventsIndex.value = buildRoomDayEventsIndex(shiftPlanCopy.value || []) },
    // Deep nötig, damit Mutationen aus dem Websocket-Listener (push/splice in nested arrays) den Index neu bauen.
    { immediate: true, deep: true }
)

function getEventsForRoomDay(room: any, targetDay: string): AnyEvent[] {
    const roomId = room?.roomId ?? room?.id
    const dayIso = extractDate(targetDay)
    if (!dayIso) return []
    return roomDayEventsIndex.value.get(roomId)?.get(dayIso) ?? []
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

const shiftQualificationsArray = computed(() =>
    Array.isArray(shiftQualificationsResolved.value)
        ? shiftQualificationsResolved.value
        : Object.values(shiftQualificationsResolved.value || {})
)

/**
 * Modals
 */
const openAddShiftForRoomAndDay = (day: string, roomId: number | null) => {
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
    if (success && shift) {
        for (const room of shiftPlanCopy.value) {
            if (room.shiftsById && room.shiftsById[shift.id]) {
                room.shiftsById[shift.id] = shift;
            }
            if (room.eventsById) {
                for (const eventId of Object.keys(room.eventsById)) {
                    const event = room.eventsById[eventId];
                    if (Array.isArray(event.shifts)) {
                        const idx = event.shifts.findIndex((s: any) => s.id === shift.id);
                        if (idx !== -1) event.shifts[idx] = shift;
                    }
                }
            }
        }
    }
    showAddShiftModal.value = false
    shiftToEdit.value = null
    roomForShiftAdd.value = null
    dayForShiftAdd.value = null
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
        { daily_view: dailyViewMode.value },
        { preserveScroll: false, preserveState: false }
    )
}

const changeDailyViewModeValue = (newValue: boolean) => {
    dailyViewMode.value = newValue
    router.patch(
        route("user.update.daily_view", page.props.auth.user.id),
        { daily_view: dailyViewMode.value },
        { preserveScroll: false, preserveState: false }
    )
}

/**
 * Shortcuts
 */
const jumpToToday = () => {
    const today = new Date().toISOString().slice(0, 10)

    if (!dailyViewMode.value) {
        changeDailyViewModeValue(true)
        setTimeout(() => {
            router.patch(
                route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
                { start_date: today, end_date: today },
                { preserveScroll: true, preserveState: false }
            )
        }, 100)
        return
    }

    router.patch(
        route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
        { start_date: today, end_date: today },
        { preserveScroll: true, preserveState: false }
    )
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
        },
        { preserveScroll: true, preserveState: false }
    )
}

const jumpToCurrentMonth = () => {
    const today = new Date()
    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1)
    const monthEnd = new Date(today.getFullYear(), today.getMonth() + 1, 0)

    if (dailyViewMode.value) {
        changeDailyViewModeValue(false)
        setTimeout(() => {
            router.patch(
                route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
                {
                    start_date: monthStart.toISOString().slice(0, 10),
                    end_date: monthEnd.toISOString().slice(0, 10),
                },
                { preserveScroll: true, preserveState: false }
            )
        }, 100)
        return
    }

    router.patch(
        route("update.user.shift.calendar.filter.dates", page.props.auth.user.id),
        {
            start_date: monthStart.toISOString().slice(0, 10),
            end_date: monthEnd.toISOString().slice(0, 10),
        },
        { preserveScroll: true, preserveState: false }
    )
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

    await initializeDailyShiftPlan()

    // Load crafts asynchronously for shift plan
    try {
        const { data } = await axios.get(route("shifts.crafts"))
        craftsLoaded.value = data.crafts ?? []
    } catch {
        craftsLoaded.value = []
    }

    await loadShiftPlanWorkers()

    const ShiftCalendarListener = useShiftCalendarListener(shiftPlanCopy as any)
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
    roomResizeObservers.forEach(ro => ro.disconnect())
    roomResizeObservers.clear()
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
