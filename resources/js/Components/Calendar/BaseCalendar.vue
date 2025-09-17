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
                        {{eventsWithoutRoomLen === 1 ? $t('{0} Event without room!', [eventsWithoutRoomLen]) : $t('{0} Events without room!', [eventsWithoutRoomLen]) }}
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
                        <div
                            class="w-fit events-by-days-container"
                            :class="[isFullscreen ? 'mt-4' : '']"
                            ref="calendarToCalculate"
                        >
                            <div
                                v-for="day in days"
                                :key="day.fullDay"
                                class="flex gap-0.5 day-container"
                                :class="day.isWeekend ? 'bg-gray-50' : ''"
                                :style="dayRowStyle"
                                :data-day="day.fullDay"
                                :data-day-to-jump="day.withoutFormat"
                                :data-month="monthKeyFromDay(day)"
                                :ref="el => registerMonthSentinel(el, day)"
                            >
                                <SingleDayInCalendar v-if="!day.isExtraRow" :isFullscreen="isFullscreen" :day="day" />

                                <pre>{{ newCalendarData }}</pre>
                                <!-- Räume -->
                                <template v-if="!day.isExtraRow">
                                    <template v-for="(room, roomIdx) in newCalendarData" :key="room.id ?? room.roomId ?? roomIdx">
                                        <!-- Eine Cell (Tag × Raum) -->
                                        <section
                                            :style="cellStyle"
                                            :class="containerClass"
                                            :id="`scroll_container-${day.withoutFormat}`"
                                            :data-room-id="room.roomId ?? room.id"
                                            :ref="el => registerCell(el, day, room)"
                                        >
                                            <!-- Nur rendern, wenn Cell (Tag×Raum) in/nahe Viewport -->
                                            <template v-if="isCellVisible(cellKey(day, room))">
                                                <pre>
                                                    {{ room.content?.[dayKey(day)]?.events }}
                                                </pre>
                                                <SingleEventInCalendar
                                                    v-for="(evt, idx) in (room.content?.[dayKey(day)]?.events ?? [])"
                                                    :key="evt.id"
                                                    v-memo="[evt.id, evt.updated_at, multiEdit, textStyle.fontSize, textStyle.lineHeight, cardWidthNum]"
                                                    class="py-0.5"
                                                    :id="`event_scroll-${idx}-day-${day.withoutFormat}-room-${(room.roomId ?? room.id)}`"
                                                    :event="evt"
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
                                            </template>
                                        </section>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Optional: Tagesansicht -->
            <AsyncDailyViewCalendar v-else />
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
            v-if="showRejectEventVerificationRequestModal"
            @close="closeShowRejectEventVerificationModal"
            :event-ids="editEvents"
        />
    </div>
</template>

<script setup>
import { computed, defineAsyncComponent, inject, onMounted, onBeforeUnmount, ref, shallowRef } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { IconAlertTriangle } from "@tabler/icons-vue";

import { usePermission } from "@/Composeables/Permission.js";
import { useTranslation } from "@/Composeables/Translation.js";
import { useShiftCalendarListener } from "@/Composeables/Listener/useShiftCalendarListener.js";
import { can } from "laravel-permission-to-vuejs";
import SingleEventInCalendar from "@/Components/Calendar/Elements/SingleEventInCalendar.vue";

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

const AsyncEventComponent = defineAsyncComponent({ loader: () => import("@/Layouts/Components/EventComponent.vue") });
const FunctionBarCalendar = defineAsyncComponent({ loader: () => import("@/Components/FunctionBars/FunctionBarCalendar.vue") });
const CalendarHeader = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/CalendarHeader.vue") });
const AsyncEventsWithoutRoomComponent = defineAsyncComponent({ loader: () => import("@/Layouts/Components/EventsWithoutRoomComponent.vue") });
const AsyncDailyViewCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/DailyViewCalendar.vue") });
const SingleDayInCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/SingleDayInCalendar.vue") });
const MultiDuplicateModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiDuplicateModal.vue") });
const AddSubEventModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/AddSubEventModal.vue") });
const DeclineEventModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/DeclineEventModal.vue") });
const ConfirmDeleteModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/ConfirmDeleteModal.vue") });
const FormButton = defineAsyncComponent({ loader: () => import("@/Layouts/Components/General/Buttons/FormButton.vue") });
const MultiEditModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiEditModal.vue") });
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
const containerClass = computed(() => ['group/container border-t border-gray-300 border-dashed relative overflow-scroll', (zoom_factor.value > 0.4 ? "cell" : "overflow-hidden")]);

// Topbar count
const eventsWithoutRoomLen = computed(() =>
    Array.isArray(props.eventsWithoutRoom) ? props.eventsWithoutRoom.length : (props.eventsWithoutRoom?.length ?? 0)
);

// State
const multiEdit = ref(false);
const isFullscreen = ref(false);
const showMultiEditModal = ref(false);
const editEvents = ref([]);
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
const showRejectEventVerificationRequestModal = ref(false);

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

// ---- Hilfsfunktionen: Keys & Formate
const toGermanDate = (iso) => {
    // 'YYYY-MM-DD' -> 'DD.MM.YYYY'
    if (!iso || iso.length < 10) return iso;
    const [y, m, d] = iso.split("-");
    return `${d}.${m}.${y}`;
};
const dayKey = (day) => {
    // Bevorzugt geliefertes fullDay (z.B. '01.01.2025'), fallback auf Konvertierung
    return day.fullDay ?? toGermanDate(day.withoutFormat);
};
const monthKeyFromDay = (day) => (day.withoutFormat || "").slice(0, 7);

// ---------- Virtualisierung pro Cell ----------
function useCellVisibility(options = {}) {
    const { root = null, rootMargin = '1200px', threshold = 0.01 } = options;
    const visibleKeys = ref(new Set());
    let io = null;
    const map = new Map();

    const isVisible = (key) => visibleKeys.value.has(key);

    const observe = (el, key) => {
        if (!el) return;
        if (!io) {
            io = new IntersectionObserver((entries) => {
                let changed = false;
                for (const entry of entries) {
                    const k = map.get(entry.target);
                    if (!k) continue;
                    if (entry.isIntersecting) {
                        if (!visibleKeys.value.has(k)) {
                            const next = new Set(visibleKeys.value);
                            next.add(k);
                            visibleKeys.value = next;
                            changed = true;
                        }
                    } else {
                        if (visibleKeys.value.has(k)) {
                            const next = new Set(visibleKeys.value);
                            next.delete(k);
                            visibleKeys.value = next;
                            changed = true;
                        }
                    }
                }
                if (changed) {/* optional rAF batching */}
            }, { root, rootMargin, threshold });
        }
        map.set(el, key);
        io.observe(el);
    };

    const dispose = () => {
        if (io) io.disconnect();
        map.clear();
        visibleKeys.value.clear();
    };

    return { observe, isVisible, dispose };
}
const { observe: observeCell, isVisible: isCellVisible, dispose: disposeCells } = useCellVisibility({
    root: null,
    rootMargin: '1200px',
    threshold: 0.01
});
const cellKey = (day, room) => `${day.withoutFormat}:${(room.roomId ?? room.id)}`;
const registerCell = (el, day, room) => { if (el) observeCell(el, cellKey(day, room)); };

// ---------- Monatsweises Paging (sichtbarer Monat ±1) ----------
const monthList = computed(() => {
    const map = new Map(); // 'YYYY-MM' -> {start, end}
    for (const d of props.days) {
        const iso = d.withoutFormat; // 'YYYY-MM-DD'
        if (!iso) continue;
        const key = iso.slice(0, 7); // 'YYYY-MM'
        if (!map.has(key)) map.set(key, { start: iso, end: iso });
        else {
            const rec = map.get(key);
            if (iso < rec.start) rec.start = iso;
            if (iso > rec.end) rec.end = iso;
        }
    }
    return Array.from(map.entries())
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([key, range]) => ({ key, ...range }));
});
const monthIndexByKey = computed(() => {
    const idx = new Map();
    monthList.value.forEach((m, i) => idx.set(m.key, i));
    return idx;
});
const loadedMonths = ref(new Set());
const loadingMonths = ref(new Set());
const monthSentinelSeen = ref(new Set());
let monthObserver = null;

async function loadMonth(key) {
    if (!key) return;
    if (loadedMonths.value.has(key) || loadingMonths.value.has(key)) return;
    const rec = monthList.value.find(m => m.key === key);
    if (!rec) return;

    loadingMonths.value.add(key);
    try {
        const { data } = await axios.get(route("events.all"), {
            params: {
                start_date: rec.start,
                end_date: rec.end,
                isPlanning: props.isPlanning
            }
        });
        applyCalendarPatch(data.calendar);
        loadedMonths.value = new Set(loadedMonths.value).add(key);
    } finally {
        const next = new Set(loadingMonths.value);
        next.delete(key);
        loadingMonths.value = next;
    }
}
async function ensureAround(key) {
    const idx = monthIndexByKey.value.get(key);
    if (idx == null) return;
    const keys = [monthList.value[idx - 1]?.key, monthList.value[idx]?.key, monthList.value[idx + 1]?.key].filter(Boolean);
    await Promise.all(keys.map(loadMonth));
}
function initMonthObserver() {
    if (monthObserver) return;
    monthObserver = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            const key = entry.target.getAttribute('data-month');
            if (key) ensureAround(key);
        }
    }, { root: null, rootMargin: '1500px 0px', threshold: 0.01 });
}
function registerMonthSentinel(el, day) {
    if (!el) return;
    const key = monthKeyFromDay(day);
    if (!key || monthSentinelSeen.value.has(key)) return;
    monthSentinelSeen.value.add(key);
    initMonthObserver();
    monthObserver.observe(el);
}

// Initial: ersten Monat ±1 laden
onMounted(async () => {
    const ShiftCalendarListener = useShiftCalendarListener(newCalendarData);
    ShiftCalendarListener.init();
    const initialKey = monthList.value[0]?.key;
    await ensureAround(initialKey);
});

onBeforeUnmount(() => {
    disposeCells();
    if (monthObserver) monthObserver.disconnect();
    monthObserver = null;
    monthSentinelSeen.value.clear();
});

// ---------- Patch-Update (keine Deep-Mutation) ----------
function applyCalendarPatch(patchRooms) {
    const byId = new Map(patchRooms.map(r => [r.roomId, r]));
    const next = newCalendarData.value.map(room => {
        const inc = byId.get(room.roomId);
        if (!inc) return room;
        const content = { ...room.content };
        let changed = false;
        Object.entries(inc.content).forEach(([dayStr, { events }]) => {
            const slot = content[dayStr];
            if (!slot) return;
            if (slot.events !== events) {
                content[dayStr] = { ...slot, events };
                changed = true;
            }
        });
        return changed ? { ...room, content } : room;
    });
    newCalendarData.value = next;
}

// ---------- Multi-Edit etc. ----------
const checkedCount = computed(() => editEvents.value.length);

function handleMultiEditEventCheckboxChange(eventId, considerOnMultiEdit, eventRoomId) {
    if (considerOnMultiEdit) {
        if (!editEvents.value.includes(eventId)) editEvents.value.push(eventId);
    } else {
        editEvents.value = editEvents.value.filter(id => id !== eventId);
    }

    const next = newCalendarData.value.map(room => {
        let roomChanged = false;
        const content = { ...room.content };
        for (const [d, slot] of Object.entries(content)) {
            let changed = false;
            const evts = (slot.events ?? []).map(e => {
                if (e.id === eventId) { changed = true; return { ...e, considerOnMultiEdit }; }
                return e;
            });
            if (changed) { content[d] = { ...slot, events: evts }; roomChanged = true; }
        }
        return roomChanged ? { ...room, content } : room;
    });
    newCalendarData.value = next;
}
function toggleMultiEdit(value) {
    multiEdit.value = value;
    if (!value && editEvents.value.length) {
        const next = newCalendarData.value.map(room => {
            let roomChanged = false;
            const content = { ...room.content };
            for (const [d, slot] of Object.entries(content)) {
                let changed = false;
                const evts = (slot.events ?? []).map(e => {
                    if (e.considerOnMultiEdit) { changed = true; return { ...e, considerOnMultiEdit: false }; }
                    return e;
                });
                if (changed) { content[d] = { ...slot, events: evts }; roomChanged = true; }
            }
            return roomChanged ? { ...room, content } : room;
        });
        newCalendarData.value = next;
        editEvents.value = [];
    }
}
const cancelMultiEditDuplicateSelection = () => toggleMultiEdit(false);

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
