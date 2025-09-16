<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white">
        <!-- Topbar -->
        <div class="w-full left-8 top-0 px-5 fixed z-40">
            <FunctionBarCalendar
                :multi-edit="multiEdit"
                :project="project"
                :rooms="rooms"
                :is-fullscreen="isFullscreen"
                :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                @open-fullscreen-mode="openFullscreen"
                @wants-to-add-new-event="showEditEventModel(null)"
                @update-multi-edit="toggleMultiEdit"
                @jump-to-day-of-month="jumpToDayOfMonth"
                :is-planning="isPlanning"
            />
            <div
                v-if="eventsWithoutRoomLen > 0"
                class="w-full h-8 px-4 py-2 bg-red-500 cursor-pointer"
                @click="showEventsWithoutRoomComponent = true"
            >
                <div class="flex items-center justify-center w-full h-full gap-x-1">
                    <IconAlertTriangle class="size-4 text-white" aria-hidden="true" />
                    <div class="text-white text-sm font-bold">
                        {{
                            eventsWithoutRoomLen === 1
                                ? $t('{0} Event without room!', [eventsWithoutRoomLen])
                                : $t('{0} Events without room!', [eventsWithoutRoomLen])
                        }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Grid -->
        <div class="pt-20">
            <!-- Monatsansicht -->
            <div v-if="!isDaily && !atAGlance">
                <div class="w-max -ml-3">
                    <div :class="project ? 'bg-lightBackgroundGray/50' : 'bg-white'">
                        <CalendarHeader :rooms="rooms" :filtered-events-length="eventsWithoutRoomLen" />
                        <div class="w-fit events-by-days-container" :class="[isFullscreen ? 'mt-4' : '']" ref="calendarToCalculate">
                            <div
                                v-for="day in days"
                                :key="day.fullDay"
                                class="flex gap-0.5 day-container"
                                :class="day.isWeekend ? 'bg-gray-50' : ''"
                                :style="dayRowStyle"
                                :data-day="day.fullDay"
                                :data-day-to-jump="day.withoutFormat"
                            >
                                <SingleDayInCalendar v-if="!day.isExtraRow" :isFullscreen="isFullscreen" :day="day" />

                                <!-- Räume -->
                                <div
                                    v-for="(room, roomIdx) in newCalendarData"
                                    :key="room.id ?? room.roomId ?? roomIdx"
                                    v-if="!day.isExtraRow"
                                    class="relative"
                                >
                                    <!-- Jump-to-bottom Button -->
                                    <div
                                        v-if="(room.content?.[day.fullDay]?.events?.length ?? 0) > 1 && !settings.expand_days"
                                        class="absolute bottom-2 right-4 z-10"
                                    >
                                        <IconChevronDown
                                            class="h-6 w-6 text-gray-400 text-hover cursor-pointer"
                                            stroke-width="2"
                                            @click="scrollToNextEventInDay(day.withoutFormat, room.content?.[day.fullDay]?.events?.length ?? 0, (room.roomId ?? room.id))"
                                        />
                                    </div>

                                    <!-- Zelle -->
                                    <div
                                        :style="cellStyle"
                                        :class="['group/container border-t border-gray-300 border-dashed relative', cellClass]"
                                        :id="'scroll_container-' + day.withoutFormat"
                                    >
                                        <!-- Projektgruppen (memoisiert) -->
                                        <div
                                            v-if="settings.display_project_groups"
                                            v-for="group in projectGroups(room.content?.[day.fullDay]?.events ?? [])"
                                            :key="group.id"
                                        >
                                            <Link
                                                :disabled="groupLinkDisabled(group)"
                                                :href="route('projects.tab', { project: group.id, projectTab: first_project_tab_id })"
                                                class="bg-artwork-navigation-background text-white text-xs font-bold px-2 py-1 rounded-lg mb-0.5 flex items-center gap-x-1"
                                            >
                                                <component :is="group.icon" class="size-4" aria-hidden="true" />
                                                <span>{{ group.name }}</span>
                                            </Link>
                                        </div>

                                        <!-- Events -->
                                        <div
                                            v-for="(event, index) in (room.content?.[day.fullDay]?.events ?? [])"
                                            :key="event.id ?? `${day.fullDay}-${index}`"
                                            class="py-0.5"
                                            :id="`event_scroll-${index}-day-${day.withoutFormat}-room-${room.roomId ?? room.id}`"
                                        >
                                            <!-- Fallback-Filter: wenn event.roomId/room.roomId vorhanden sind, nur im passenden Raum anzeigen -->
                                            <AsyncSingleEventInCalendar
                                                v-if="!event.roomId || !room.roomId || event.roomId === (room.roomId ?? room.id)"
                                                :event="event"
                                                :multi-edit="multiEdit"
                                                :font-size="textStyle.fontSize"
                                                :line-height="textStyle.lineHeight"
                                                :rooms="rooms"
                                                :has-admin-role="hasAdminRole()"
                                                :width="cardWidthNum"
                                                :first_project_tab_id="first_project_tab_id"
                                                :firstProjectShiftTabId="firstProjectShiftTabId"
                                                :verifierForEventTypIds="verifierForEventTypIds"
                                                :is-planning="isPlanning"
                                                @edit-event="showEditEventModel"
                                                @edit-sub-event="openAddSubEventModal"
                                                @open-add-sub-event-modal="openAddSubEventModal"
                                                @open-confirm-modal="openDeleteEventModal"
                                                @show-decline-event-modal="openDeclineEventModal"
                                                @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                            />
                                        </div>

                                        <!-- Hover Add -->
                                        <div v-if="settings.expand_days" class="absolute inset-0 pointer-events-none"></div>
                                        <div class="absolute left-2 bottom-3 hidden group-hover/container:block z-50">
                                            <ToolTipComponent
                                                :tooltip-text="isPlanning ? $t('Add new planned event') : $t('Add new event on this day')"
                                                :icon="IconCircleDashedPlus"
                                                classes="cursor-pointer card glassy text-artwork-buttons-create h-8 w-8 flex items-center justify-center rounded-full"
                                                @click="openNewEventModalWithBaseData(day.withoutFormat, (room.roomId ?? room.id))"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tagesansicht -->
            <div v-else-if="isDaily && !atAGlance">
                <AsyncDailyViewCalendar
                    :multi-edit="multiEdit"
                    :rooms="rooms"
                    :days="days"
                    :calendarData="newCalendarData"
                    :project="project"
                    :eventStatuses="eventStatuses"
                    :eventTypes="eventTypes"
                    :eventsWithoutRoom="eventsWithoutRoom"
                    :projectNameUsedForProjectTimePeriod="projectNameUsedForProjectTimePeriod"
                    :firstProjectShiftTabId="firstProjectShiftTabId"
                    :first-project-tab-id="first_project_tab_id"
                    :verifierForEventTypIds="verifierForEventTypIds"
                    :is-planning="isPlanning"
                    @edit-event="showEditEventModel"
                    @edit-sub-event="openAddSubEventModal"
                    @open-add-sub-event-modal="openAddSubEventModal"
                    @open-confirm-modal="openDeleteEventModal"
                    @show-decline-event-modal="openDeclineEventModal"
                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                />
            </div>

            <!-- At-a-glance -->
            <div class="mt-[4.5rem] w-max" v-else>
                <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[64px] rounded-lg mb-3">
                    <div v-for="(room, rIdx) in newCalendarData" :key="room.id ?? room.roomId ?? rIdx">
                        <div :style="{ minWidth: cellWidthPx, maxWidth: cellWidthPx, width: cellWidthPx }" class="flex items-center h-full truncate">
                            <SingleRoomInHeader :room="room" is-light />
                        </div>
                    </div>
                </div>

                <div class="flex gap-0.5">
                    <div v-for="(room, rIdx) in newCalendarData" :key="'agl-'+(room.id ?? room.roomId ?? rIdx)">
                        <div v-for="(events, k) in room.content" :key="events?.date ?? k" class="flex flex-col">
                            <div
                                v-for="(event, index) in (events?.events ?? [])"
                                :key="event.id ?? `${events?.date}-${index}`"
                                :style="{ minWidth: cellWidthPx, maxWidth: cellWidthPx, width: cellWidthPx }"
                                class="mb-0.5 h-full"
                                :id="'scroll_container-' + (events?.date ?? k)"
                            >
                                <div class="py-0.5">
                                    <AsyncSingleEventInCalendar
                                        :event="event"
                                        :multi-edit="multiEdit"
                                        :font-size="textStyle.fontSize"
                                        :line-height="textStyle.lineHeight"
                                        :rooms="rooms"
                                        :has-admin-role="hasAdminRole()"
                                        :width="cardWidthNum"
                                        :first_project_tab_id="first_project_tab_id"
                                        :firstProjectShiftTabId="firstProjectShiftTabId"
                                        :verifierForEventTypIds="verifierForEventTypIds"
                                        :is-planning="isPlanning"
                                        @edit-event="showEditEventModel"
                                        @edit-sub-event="openAddSubEventModal"
                                        @open-add-sub-event-modal="openAddSubEventModal"
                                        @open-confirm-modal="openDeleteEventModal"
                                        @show-decline-event-modal="openDeclineEventModal"
                                        @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hinweis, wenn Projektzeitraum aktiv & leer -->
        <div v-if="!checkIfAnyRoomHasAnEventOrShift && settings.use_project_time_period">
            <div class="mt-24 ml-4">
                <div class="border-l-4 border-red-400 bg-red-50 p-4 w-fit">
                    <div class="flex">
                        <div class="shrink-0">
                            <IconExclamationCircle class="h-5 w-5 text-red-400" stroke-width="2" aria-hidden="true" />
                        </div>
                        <div class="ml-1">
                            <p class="text-sm text-red-700 font-bold">
                                {{ $t('The selected project has no dates') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi-Edit Bottom Bar -->
        <div class="fixed bottom-0 w-full h-32 bg-artwork-navigation-background/30 z-40 pointer-events-none" v-if="multiEdit">
            <div class="flex items-center justify-center h-full gap-4" v-if="!isPlanning">
                <div>
                    <FormButton
                        :disabled="checkedCount === 0"
                        @click="showMultiEditModal = true"
                        :text="checkedCount + ' Termin(e) verschieben'"
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                    />
                </div>
                <div>
                    <FormButton
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                        @click="showMultiDuplicateModal = true"
                        :disabled="checkedCount === 0"
                        :text="checkedCount + ' ' + $t('Duplicate events')"
                    />
                </div>
                <div>
                    <FormButton
                        class="bg-artwork-error hover:bg-artwork-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                        @click="openDeleteSelectedEventsModal = true"
                        :disabled="checkedCount === 0"
                        :text="checkedCount + ' ' + $t('Delete events')"
                    />
                </div>
                <div>
                    <FormButton
                        class="bg-artwork-error hover:bg-artwork-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                        @click="cancelMultiEditDuplicateSelection"
                        :disabled="checkedCount === 0"
                        :text="$t('Cancel selection')"
                    />
                </div>
            </div>
            <div class="flex items-center justify-center h-full gap-4" v-else>
                <div v-if="can('can see planning calendar') || hasAdminRole()">
                    <FormButton
                        :disabled="checkedCount === 0"
                        @click="requestVerification"
                        :text="checkedCount + ' ' + $t('request verification')"
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                    />
                </div>
                <div v-if="can('can edit planning calendar') || hasAdminRole()">
                    <FormButton
                        :disabled="checkedCount === 0"
                        @click="approveRequests"
                        :text="checkedCount + ' ' + $t('Approve events')"
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                    />
                </div>
                <div v-if="can('can edit planning calendar') || hasAdminRole()">
                    <FormButton
                        class="bg-artwork-error hover:bg-artwork-error/70 transition-all duration-300 ease-in-out pointer-events-auto"
                        @click="showRejectEventVerificationModal = true"
                        :disabled="checkedCount === 0"
                        :text="checkedCount + ' ' + $t('Reject events')"
                    />
                </div>
            </div>
        </div>

        <!-- Modals -->
        <AsyncEventComponent
            v-if="showEventComponent"
            :showHints="usePage().props.show_hints"
            :eventTypes="eventTypes"
            :rooms="rooms"
            :calendarProjectPeriod="settings.use_project_time_period"
            :project="project"
            :event="eventToEdit"
            :wantedRoomId="wantedRoom"
            :isAdmin="hasAdminRole()"
            :roomCollisions="roomCollisions"
            :first_project_calendar_tab_id="first_project_calendar_tab_id"
            :requires-axios-requests="true"
            @closed="eventComponentClosed"
            :event-statuses="eventStatuses"
            :is-planning="isPlanning"
            :wanted-date="wantedDate"
        />

        <ConfirmDeleteModal
            v-if="deleteComponentVisible"
            :title="deleteTitle"
            :description="deleteDescription"
            @closed="deleteComponentVisible = false"
            @delete="deleteEvent"
        />

        <DeclineEventModal
            v-if="showDeclineEventModal"
            :request-to-decline="declineEvent"
            :event-types="eventTypes"
            @closed="showDeclineEventModal = false"
        />

        <MultiEditModal v-if="showMultiEditModal" :checked-events="editEvents" :rooms="rooms" @closed="closeMultiEditModal" />
        <MultiDuplicateModal v-if="showMultiDuplicateModal" :checked-events="editEvents" :rooms="rooms" @closed="closeMultiDuplicateModal" />

        <ConfirmDeleteModal
            v-if="openDeleteSelectedEventsModal"
            :title="$t('Delete assignments')"
            :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
            @closed="closeDeleteSelectedEventsModal"
            @delete="deleteSelectedEvents"
        />

        <AddSubEventModal
            v-if="showAddSubEventModal"
            :event="eventToEdit"
            :event-types="eventTypes"
            :sub-event-to-edit="subEventToEdit"
            @close="closeAddSubEventModal"
        />

        <AsyncEventsWithoutRoomComponent
            v-if="showEventsWithoutRoomComponent"
            @closed="showEventsWithoutRoomComponent = false"
            :showHints="usePage().props.show_hints"
            :eventTypes="eventTypes"
            :rooms="rooms"
            :eventsWithoutRoom="usePage().props.eventsWithoutRoom"
            :isAdmin="hasAdminRole()"
            :event-statuses="eventStatuses"
            :first_project_calendar_tab_id="first_project_calendar_tab_id"
        />

        <RejectEventVerificationRequestModal
            v-if="showRejectEventVerificationModal"
            @close="closeShowRejectEventVerificationModal"
            :event-ids="editEvents"
        />
    </div>
</template>

<script setup>
import { computed, defineAsyncComponent, inject, onMounted, ref, shallowRef } from "vue";
import { router, usePage, Link } from "@inertiajs/vue3";
import axios from "axios";
import {IconExclamationCircle, IconAlertTriangle, IconChevronDown, IconCircleDashedPlus} from "@tabler/icons-vue";

import { usePermission } from "@/Composeables/Permission.js";
import { useTranslation } from "@/Composeables/Translation.js";
import { lazyLoadComponentIfVisible } from "@/Composeables/utils.js";
import { useShiftCalendarListener } from "@/Composeables/Listener/useShiftCalendarListener.js";
import CalendarPlaceholder from "@/Components/Calendar/Elements/CalendarPlaceholder.vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import { can } from "laravel-permission-to-vuejs";

const props = defineProps({
    rooms: { type: Object, required: true },
    days: { type: Object, required: true },
    calendarData: { type: Object, required: true },
    project: { type: Object, default: null },
    eventsWithoutRoom: { type: Object, required: false },
    projectNameUsedForProjectTimePeriod: { type: String, default: "" },
    firstProjectShiftTabId: { type: [String, Number], default: null },
    eventStatuses: { type: Object, default: null },
    isPlanning: { type: Boolean, default: false },
    verifierForEventTypIds: { type: Array, default: [] }
});

const $t = useTranslation();
const page = usePage();
const { hasAdminRole } = usePermission(page.props);

const AsyncSingleEventInCalendar = lazyLoadComponentIfVisible({
    componentLoader: () => import('@/Components/Calendar/Elements/SingleEventInCalendar.vue'),
    loadingComponent: CalendarPlaceholder,
});
const AsyncEventComponent = defineAsyncComponent({ loader: () => import("@/Layouts/Components/EventComponent.vue") });
const FunctionBarCalendar = defineAsyncComponent({ loader: () => import("@/Components/FunctionBars/FunctionBarCalendar.vue") });
const CalendarHeader = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/CalendarHeader.vue") });
const AsyncEventsWithoutRoomComponent = defineAsyncComponent({ loader: () => import("@/Layouts/Components/EventsWithoutRoomComponent.vue") });
const AsyncDailyViewCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/DailyViewCalendar.vue") });
const SingleRoomInHeader = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/SingleRoomInHeader.vue") });
const MultiDuplicateModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiDuplicateModal.vue") });
const AddSubEventModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/AddSubEventModal.vue") });
const DeclineEventModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/DeclineEventModal.vue") });
const ConfirmDeleteModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/ConfirmDeleteModal.vue") });
const FormButton = defineAsyncComponent({ loader: () => import("@/Layouts/Components/General/Buttons/FormButton.vue") });
const MultiEditModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiEditModal.vue") });
const SingleDayInCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/SingleDayInCalendar.vue") });
const RejectEventVerificationRequestModal = defineAsyncComponent({
    loader: () => import("@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue"),
    delay: 200,
    timeout: 3000
});

// User & Settings
const user = computed(() => page.props.auth.user);
const settings = computed(() => user.value.calendar_settings);
const zoom_factor = ref(user.value.zoom_factor ?? 1);
const isDaily = computed(() => !!user.value.daily_view);
const atAGlance = computed(() => !!user.value.at_a_glance);

// Maße/Styles
const cellWidthPx = computed(() => `${zoom_factor.value * 212}px`);
const cardWidthNum = computed(() => zoom_factor.value * 196);
const rowHeightPx = computed(() => `${zoom_factor.value * 115}px`);
const dayRowStyle = computed(() => ({
    height: settings.value.expand_days ? "" : rowHeightPx.value,
    minHeight: settings.value.expand_days ? rowHeightPx.value : ""
}));
const cellStyle = computed(() => ({
    minWidth: cellWidthPx.value,
    maxWidth: cellWidthPx.value,
    height: settings.value.expand_days ? "" : rowHeightPx.value,
    minHeight: settings.value.expand_days ? rowHeightPx.value : ""
}));
const cellClass = computed(() => (zoom_factor.value > 0.4 ? "cell" : "overflow-hidden"));

// Topbar count
const eventsWithoutRoomLen = computed(() =>
    Array.isArray(props.eventsWithoutRoom) ? props.eventsWithoutRoom.length : (props.eventsWithoutRoom?.length ?? 0)
);

// State
const multiEdit = ref(false);
const isFullscreen = ref(false);
const showMultiEditModal = ref(false);
const editEvents = ref([]);
const editEventsRoomIds = ref([]);
const editEventsRoomsDesiredDays = ref([]);
const openDeleteSelectedEventsModal = ref(false);
const showEventsWithoutRoomComponent = ref(false);
const showAddSubEventModal = ref(false);
const showDeclineEventModal = ref(false);
const showEventComponent = ref(false);
const deleteComponentVisible = ref(false);
const deleteTitle = ref("");
const deleteDescription = ref("");
const deleteType = ref("");
const eventToEdit = ref(null);
const subEventToEdit = ref(null);
const declineEvent = ref(null);
const eventToDelete = ref(null);
const wantedRoom = ref(null);
const roomCollisions = ref([]);
const showMultiDuplicateModal = ref(false);
const newCalendarData = shallowRef(props.calendarData);
const wantedDate = ref(null);
const showRejectEventVerificationModal = ref(false);

// Injects
const first_project_calendar_tab_id = inject("first_project_calendar_tab_id");
const first_project_tab_id = inject("first_project_tab_id");
const eventTypes = inject("eventTypes");

// Textgrößen
const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return { fontSize, lineHeight };
});

// Projektgruppen-Cache
const groupsCache = new WeakMap();
function projectGroups(events = []) {
    const cached = groupsCache.get(events);
    if (cached) return cached;
    const out = [];
    const seen = new Set();
    for (let i = 0; i < events.length; i++) {
        const p = events[i]?.project;
        if (!p) continue;
        if (p.isGroup) {
            if (!seen.has(p.id)) { seen.add(p.id); out.push(p); }
        } else if (p.isInGroup && Array.isArray(p.group)) {
            for (const g of p.group) {
                if (!seen.has(g.id)) { seen.add(g.id); out.push(g); }
            }
        }
    }
    groupsCache.set(events, out);
    return out;
}
function groupLinkDisabled(group) {
    if (hasAdminRole()) return false;
    return !group.userIds.includes(user.value.id);
}

// Scroll helper
const scrollToNextEventInDay = (day, length, room) => {
    const idx = Math.max(0, (length ?? 1) - 1);
    const el = document.getElementById(`event_scroll-${idx}-day-${day}-room-${room}`);
    if (el) el.scrollIntoView({ behavior: "smooth", block: "nearest", inline: "nearest" });
};

// Checked Count
const checkedCount = computed(() => editEvents.value.length);

// Patch-Update (keine Deep-Mutation)
function applyCalendarPatch(patchRooms) {
    const byId = new Map(patchRooms.map(r => [r.roomId, r]));
    const next = newCalendarData.value.map(room => {
        const inc = byId.get(room.roomId);
        if (!inc) return room;
        const content = { ...room.content };
        let changed = false;
        Object.entries(inc.content).forEach(([day, { events }]) => {
            const slot = content[day];
            if (!slot) return;
            if (slot.events !== events) {
                content[day] = { ...slot, events };
                changed = true;
            }
        });
        return changed ? { ...room, content } : room;
    });
    newCalendarData.value = next;
}

// Multi-Edit toggles (patch-basiert)
const handleMultiEditEventCheckboxChange = (eventId, considerOnMultiEdit, eventRoomId) => {
    if (considerOnMultiEdit) {
        if (!editEvents.value.includes(eventId)) editEvents.value.push(eventId);
    } else {
        editEvents.value = editEvents.value.filter(id => id !== eventId);
        editEventsRoomIds.value = editEventsRoomIds.value.filter(rid => rid !== eventRoomId);
    }

    const next = newCalendarData.value.map(room => {
        let roomChanged = false;
        const content = { ...room.content };
        for (const [day, slot] of Object.entries(content)) {
            let changed = false;
            const evts = (slot.events ?? []).map(e => {
                if (e.id === eventId) { changed = true; return { ...e, considerOnMultiEdit }; }
                return e;
            });
            if (changed) { content[day] = { ...slot, events: evts }; roomChanged = true; }
        }
        return roomChanged ? { ...room, content } : room;
    });
    newCalendarData.value = next;
};

const toggleMultiEdit = (value) => {
    multiEdit.value = value;
    if (!value && editEvents.value.length) {
        const next = newCalendarData.value.map(room => {
            let roomChanged = false;
            const content = { ...room.content };
            for (const [day, slot] of Object.entries(content)) {
                let changed = false;
                const evts = (slot.events ?? []).map(e => {
                    if (e.considerOnMultiEdit) { changed = true; return { ...e, considerOnMultiEdit: false }; }
                    return e;
                });
                if (changed) { content[day] = { ...slot, events: evts }; roomChanged = true; }
            }
            return roomChanged ? { ...room, content } : room;
        });
        newCalendarData.value = next;
        editEvents.value = [];
    }
};

const cancelMultiEditDuplicateSelection = () => {
    toggleMultiEdit(false);
};

// Modals / Actions
const openDeclineEventModal = (event) => { declineEvent.value = event; showDeclineEventModal.value = true; };
const openDeleteEventModal = (event, type) => {
    deleteType.value = type;
    if (type === "main") {
        deleteTitle.value = $t("Delete event?");
        deleteDescription.value = $t("Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.");
    } else {
        deleteTitle.value = $t("Delete sub-event?");
        deleteDescription.value = $t("Are you sure you want to delete the selected assignments?");
    }
    eventToDelete.value = event;
    deleteComponentVisible.value = true;
};
const openNewEventModalWithBaseData = (day, roomId) => {
    eventToEdit.value = false;
    wantedRoom.value = roomId;
    wantedDate.value = day;
    showEventComponent.value = true;
};
const showEditEventModel = (event) => { eventToEdit.value = event; showEventComponent.value = true; };
const openFullscreen = () => {
    const elem = document.getElementById("myCalendar");
    if (!elem) return;
    if (elem.requestFullscreen) elem.requestFullscreen();
    else if (elem.webkitRequestFullscreen) elem.webkitRequestFullscreen();
    else if (elem.msRequestFullscreen) elem.msRequestFullscreen();
    isFullscreen.value = true;
};
const closeMultiEditModal = (closedOnPurpose) => { showMultiEditModal.value = false; if (closedOnPurpose) toggleMultiEdit(false); };
const closeMultiDuplicateModal = (closedOnPurpose) => { showMultiDuplicateModal.value = false; if (closedOnPurpose) toggleMultiEdit(false); };
const closeAddSubEventModal = () => { showAddSubEventModal.value = false; eventToEdit.value = null; subEventToEdit.value = null; };

const eventComponentClosed = (closedOnPurpose) => {
    if (closedOnPurpose) {
        const cs = settings.value;
        if (cs.use_project_time_period) {
            router.patch(route("user.calendar_settings.toggle_calendar_settings_use_project_period"), {
                use_project_time_period: true,
                project_id: cs.time_period_project_id
            }, { preserveState: false, preserveScroll: true });
            return;
        }
    }
    showEventComponent.value = false;
    eventToEdit.value = null;
    wantedRoom.value = null;
    wantedDate.value = null;
};
const deleteEvent = () => {
    if (deleteType.value === "main") axios.delete(route("events.delete", eventToDelete.value));
    else axios.delete(route("subEvent.delete", eventToDelete.value));
    deleteComponentVisible.value = false;
};
const closeDeleteSelectedEventsModal = (closedOnPurpose) => { openDeleteSelectedEventsModal.value = false; if (closedOnPurpose) toggleMultiEdit(false); };
const deleteSelectedEvents = () => {
    axios.post(route("multi-edit.delete"), { events: editEvents.value })
        .finally(() => { openDeleteSelectedEventsModal.value = false; toggleMultiEdit(false); });
};

const jumpToDayOfMonth = (day) => {
    const dayElement = document.querySelector(`.day-container[data-day-to-jump="${day}"]`);
    if (dayElement) window.scrollTo({ top: dayElement.offsetTop - 130, behavior: "smooth" });
};
const approveRequests = () => {
    router.post(route("event-verifications.approved-by-events"), { events: editEvents.value }, {
        preserveScroll: true, preserveState: true, onSuccess: () => toggleMultiEdit(false)
    });
};
const requestVerification = () => {
    router.post(route("events-verifications.request-verification"), { events: editEvents.value }, {
        preserveScroll: true, preserveState: true, onSuccess: () => toggleMultiEdit(false)
    });
};
const closeShowRejectEventVerificationModal = () => { showRejectEventVerificationModal.value = false; toggleMultiEdit(false); };

// Leerer Kalender?
const checkIfAnyRoomHasAnEventOrShift = computed(() =>
    newCalendarData.value.some(room =>
        room.content && Object.values(room.content).some(slot => (slot?.events?.length ?? 0) > 0)
    )
);

// Live-Updates
onMounted(() => {
    const ShiftCalendarListener = useShiftCalendarListener(newCalendarData);
    ShiftCalendarListener.init();
});

// Monatsweises Nachladen (Patch)
onMounted(async () => {
    const dateRanges = splitByMonth();
    for (const [start_date, end_date] of dateRanges) {
        const { data } = await axios.get(route("events"), { params: { start_date, end_date, isPlanning: props.isPlanning } });
        applyCalendarPatch(data.calendar);
    }
});

const splitByMonth = () => {
    const dateValues = inject("dateValue");
    const result = [];
    const start = new Date(dateValues[0]);
    const end = new Date(dateValues[1]);
    const lastDayOf = (y, m) => new Date(y, m + 1, 0).getDate();

    let y = start.getFullYear(), m = start.getMonth(), d = start.getDate();
    while (y < end.getFullYear() || m < end.getMonth()) {
        const md = lastDayOf(y, m);
        result.push([
            `${y.toString().padStart(4, "0")}-${(m + 1).toString().padStart(2, "0")}-${d.toString().padStart(2, "0")}`,
            `${y.toString().padStart(4, "0")}-${(m + 1).toString().padStart(2, "0")}-${md.toString().padStart(2, "0")}`
        ]);
        d = 1; m++; if (m === 12) { m = 0; y++; }
    }
    result.push([
        `${y.toString().padStart(4, "0")}-${(m + 1).toString().padStart(2, "0")}-${d.toString().padStart(2, "0")}`,
        dateValues[1]
    ]);
    return result;
};

const openAddSubEventModal = (desiredEvent, mode, mainEvent) => {
    if (mode === 'create') {
        //only set eventToEdit as base for new sub event
        eventToEdit.value = desiredEvent;
    } else if (mode === 'edit') {
        //only set eventToEdit as base for new sub event
        eventToEdit.value = mainEvent;
        subEventToEdit.value = desiredEvent;
    }

    showAddSubEventModal.value = true;
}


</script>

<style scoped>
.cell {
    overflow: auto;
    /* Firefox */
    scrollbar-color: #d4d4d4 #f3f3f3;
    scrollbar-width: thin;
}
/* WebKit */
.cell::-webkit-scrollbar { width: 2px !important; height: 2px !important; }
.cell::-webkit-scrollbar-thumb { background-color: #d4d4d4; border-radius: 10px; }
.cell::-webkit-scrollbar-track { background-color: #f3f3f3; }
</style>
