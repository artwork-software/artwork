<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white" :class="isFullscreen ? 'overflow-auto h-screen' : ''">
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
                class="w-full h-8 px-4 py-2 bg-error cursor-pointer rounded-lg ml-4 -mt-1"
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
                        <!-- Kopfzeile soll exakt dieselbe Raumreihenfolge/-filterung nutzen wie das Grid -->
                        <CalendarHeader :rooms="newCalendarData" :filtered-events-length="eventsWithoutRoomLen" />
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

                                <!-- Räume -->
                                <template v-if="!day.isExtraRow">
                                    <template v-for="(room, roomIdx) in newCalendarData" :key="room.id ?? room.roomId ?? roomIdx">
                                        <!-- Eine Cell (Tag × Raum) -->
                                        <section
                                            :style="cellStyle"
                                            :id="`scroll_container-${day.withoutFormat}`"
                                            :data-room-id="room.roomId ?? room.id"
                                            :ref="el => registerCell(el, day, room)"
                                            :class="[
                    'group/container relative',                 // Basis
                    'border-dashed',                            // Linienoptik wie zuvor
                    // Dünne Standard-Linie:
                    'border-t border-gray-400',

                    // -> Hervorhebung bei >2 Terminen (nur wenn nicht expand_days):
                    (!settings.expand_days && eventsCount(day, room) > 1)
                      ? 'ring-2 ring-blue-300 rounded-lg'                  // klarer, ohne Schatten
                      : ''
                  ]"
                                        >
                                            <!-- INNERER WRAPPER: hält Scrollbereich + Floating-Buttons -->
                                            <div class="relative h-full w-full">
                                                <!-- SCROLLBAR NUR WENN SINNVOLL -->
                                                <div
                                                    class="events-scroll h-full"
                                                    :class="[
                                                        (!settings.expand_days && eventsCount(day, room) > 1) ? 'overflow-auto cell' : zoom_factor === 0.8 ? 'overflow-x-hidden overflow-y-auto' : 'overflow-hidden',
                                                        settings.expand_days ? 'flex flex-col' : ''
                                                      ]"
                                                    :style="cellStyle"
                                                >
                                                    <!-- Nur rendern, wenn Cell (Tag×Raum) in/nahe Viewport -->
                                                    <template v-if="isCellVisible(cellKey(day, room))">
                                                        <div
                                                            v-for="(evt, idx) in eventsInCell(day, room)"
                                                            :key="evt.id"
                                                            :class="[
                                                                'py-0.5',
                                                                (settings.expand_days && !!evt.allDay) ? 'flex-1 min-h-0' : ''
                                                            ]"
                                                            :id="`event_scroll-${idx}-day-${day.withoutFormat}-room-${(room.roomId ?? room.id)}`"
                                                            @click="onEventClick(evt, $event)"
                                                        >
                                                            <AsyncSingleEventInCalendar
                                                                v-memo="[evt.id, evt.updated_at, multiEdit, textStyle.fontSize, textStyle.lineHeight, cardWidthNum, day.withoutFormat, (room.roomId ?? room.id)]"
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
                                                                :is-height-full="settings.expand_days && !!evt.allDay"
                                                                @edit-event="showEditEventModel"
                                                                @edit-sub-event="openAddSubEventModal"
                                                                @open-add-sub-event-modal="openAddSubEventModal"
                                                                @open-confirm-modal="openDeleteEventModal"
                                                                @show-decline-event-modal="openDeclineEventModal"
                                                                @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                                            />
                                                        </div>
                                                    </template>

                                                    <!-- Platzhalter: weicher Abschluss, wenn wenig Inhalt -->
                                                    <div v-if="eventsCount(day, room) <= 1 && !settings.expand_days" class="h-2"></div>
                                                </div>

                                                <!-- "+"-Button: jetzt OBEN RECHTS, außerhalb des Scrollbereichs -->
                                                <button
                                                    type="button"
                                                    class="pointer-events-auto group-hover/container:inline-flex hidden absolute bottom-1 right-1 z-20
                                                     items-center justify-center cursor-pointer gap-1 rounded-md size-7 text-sm font-medium
                                                     ring-0 bg-white/90 hover:bg-gray-50/90 focus:outline-none focus:ring-0
                                                     transition duration-200 ease-in-out"
                                                    :aria-label="$t('Add event')"
                                                    @click="openNewEventModalWithBaseData(day.withoutFormat, (room.roomId ?? room.id))"
                                                >
                                                    <component :is="IconPlus" class="size-4" />
                                                </button>

                                                <!-- Scroll-to-next: unten rechts, ebenfalls außerhalb des Scrollbereichs -->
                                                <div
                                                    v-if="(eventsCount(day, room) > 1) && !settings.expand_days"
                                                    class="pointer-events-none absolute bottom-1 right-9 z-20"
                                                >
                                                    <button
                                                        type="button"
                                                        class="pointer-events-auto inline-flex items-center justify-center cursor-pointer gap-1
                               rounded-md size-7 text-sm font-medium ring-0 bg-white/90 hover:bg-gray-50/90
                               focus:outline-none focus:ring-0 transition duration-200 ease-in-out"
                                                        :aria-label="$t('Scroll to next event')"
                                                        @click="scrollToNextEvent(day, room)"
                                                        @keydown.enter.prevent="scrollToNextEvent(day, room)"
                                                    >
                                                        <component :is="IconChevronDown" class="size-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        </section>
                                    </template>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="usePage().props.auth.user.daily_view && !usePage().props.auth.user.at_a_glance">
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
                    @edit-event="showEditEventModel"
                    @edit-sub-event="openAddSubEventModal"
                    @open-add-sub-event-modal="openAddSubEventModal"
                    @open-confirm-modal="openDeleteEventModal"
                    @show-decline-event-modal="openDeclineEventModal"
                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                    :verifierForEventTypIds="verifierForEventTypIds"
                    :is-planning="isPlanning"
                />
            </div>
            <div class="mt-[4.5rem] w-max" v-else>
                <div class="flex items-center sticky gap-0.5 h-16 bg-artwork-navigation-background z-30 top-[64px] rounded-lg mb-3">
                    <div v-for="room in newCalendarData" :key="room.roomId">
                        <div :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="flex items-center h-full truncate">
                            <SingleRoomInHeader :room="room" is-light   />
                        </div>
                    </div>
                </div>
                <div class="flex gap-0.5">
                    <div v-for="room in newCalendarData">
                        <div v-for="events in room.content" :key="events" class="flex flex-col">
                            <div v-for="(event, index) in events.events" :style="{ minWidth: zoom_factor * 212 + 'px', maxWidth: zoom_factor * 212 + 'px', width: zoom_factor * 212 + 'px' }" class="mb-0.5" :id="'scroll_container-' + events.date">
                                <div class="py-0.5" :key="event.id" @click="onEventClick(event, $event)">
                                    <AsyncSingleEventInCalendar
                                        :event="event"
                                        :multi-edit="multiEdit"
                                        :font-size="textStyle.fontSize"
                                        :line-height="textStyle.lineHeight"
                                        :rooms="rooms"
                                        :has-admin-role="hasAdminRole()"
                                        :width="zoom_factor * 196"
                                        :first_project_tab_id="first_project_tab_id"
                                        :firstProjectShiftTabId="firstProjectShiftTabId"
                                        @edit-event="showEditEventModel"
                                        @edit-sub-event="openAddSubEventModal"
                                        @open-add-sub-event-modal="openAddSubEventModal"
                                        @open-confirm-modal="openDeleteEventModal"
                                        @show-decline-event-modal="openDeclineEventModal"
                                        @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                        :verifierForEventTypIds="verifierForEventTypIds"
                                        :is-planning="isPlanning"
                                    />
                                </div>
                            </div>
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
            v-if="showRejectEventVerificationRequestModal"
            @close="closeShowRejectEventVerificationModal"
            :event-ids="editEvents"
        />
    </div>
</template>

<script setup lang="ts">
import {computed, defineAsyncComponent, inject, nextTick, onBeforeUnmount, onMounted, ref, shallowRef} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import {IconAlertTriangle, IconChevronDown, IconPlus} from "@tabler/icons-vue";

import {usePermission} from "@/Composeables/Permission.js";
import {useTranslation} from "@/Composeables/Translation.js";
import {useShiftCalendarListener} from "@/Composeables/Listener/useShiftCalendarListener.js";
import {can} from "laravel-permission-to-vuejs";
import CalendarPlaceholder from "@/Components/Calendar/Elements/CalendarPlaceholder.vue";
import SingleRoomInHeader from "@/Components/Calendar/Elements/SingleRoomInHeader.vue";

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
const AsyncSingleEventInCalendar = defineAsyncComponent({
    loader: () =>  import('@/Components/Calendar/Elements/SingleEventInCalendar.vue'),
    loadingComponent: CalendarPlaceholder,
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
const rowHeightPx = computed(() => `${zoom_factor.value * 212}px`);
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
const newCalendarData = ref(props.calendarData);
const wantedDate = ref(null);
const showRejectEventVerificationRequestModal = ref(false);

const first_project_calendar_tab_id = inject("first_project_calendar_tab_id");
const first_project_tab_id = inject("first_project_tab_id");
const eventTypes = inject("eventTypes");

const textStyle = computed(() => {
    const fontSize = `max(calc(${zoom_factor.value} * 0.875rem), 10px)`;
    const lineHeight = `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`;
    return { fontSize, lineHeight };
});

const toGermanDate = (iso) => {
    if (!iso || iso.length < 10) return iso;
    const [y, m, d] = iso.split("-");
    return `${d}.${m}.${y}`;
};

type DayLike = { withoutFormat: string };
type RoomLike = { id?: number|string; roomId?: number|string };

const cellRefs = ref<Map<string, HTMLElement>>(new Map());

/*function scrollToNextEvent(day: DayLike, room: RoomLike) {
    const key = cellKey(day, room);
    let container = cellRefs.value.get(key) as HTMLElement | undefined;

    const roomId = String(room.roomId ?? room.id);

    // Fallback: über DOM ermitteln, falls Map (noch) leer ist
    if (!container) {
        const sel = `section[data-room-id="${roomId}"]#scroll_container-${day.withoutFormat}-${roomId}`;
        container = document.querySelector<HTMLElement>(sel) ?? undefined;
        if (container) cellRefs.value.set(key, container);
        else return; // keine Zelle gefunden
    }

    const selector = `[id^="event_scroll-"][id$="-day-${day.withoutFormat}-room-${roomId}"]`;
    const nodes = Array.from(container.querySelectorAll<HTMLElement>(selector));
    if (!nodes.length) return;

    const pad = 6;
    const currentTop = container.scrollTop;

    const next = nodes.find(n => n.offsetTop > currentTop + pad);
    const targetTop = next ? Math.max(next.offsetTop - pad, 0) : 0;

    container.scrollTo({ top: targetTop, behavior: 'smooth' });
}*/

const dayKey = (day) => day.fullDay ?? toGermanDate(day.withoutFormat);
const monthKeyFromDay = (day) => (day.withoutFormat || "").slice(0, 7);
function deDateToIso(de: string): string | null {
    if (!de || de.length < 10) return null;
    const [d, m, y] = de.split('.');
    if (!d || !m || !y) return null;
    const dd = d.padStart(2, '0');
    const mm = m.padStart(2, '0');
    return `${y}-${mm}-${dd}`;
}

function isoInMonth(iso: string, monthKey: string): boolean {
    return !!iso && !!monthKey && iso.startsWith(monthKey + '-');
}

function ensureCalendarShape() {
    if (!Array.isArray(newCalendarData.value)) {
        newCalendarData.value = [];
    }
}

function useCellVisibility(options = {}) {
    const { root = null, rootMargin = '1200px', threshold = 0.01 } = options;
    const visibleKeys = ref(new Set());
    let io: IntersectionObserver | null = null;
    const map = new Map<Element, string>();

    const isVisible = (key: string) => visibleKeys.value.has(key);

    const observe = (el: Element, key: string) => {
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
                if (changed) {}
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
const registerCell = (el: HTMLElement | null, day: DayLike, room: RoomLike) => {
    const key = cellKey(day, room);
    if (el) {
        observeCell(el, key);                  // Sichtbarkeits-IO
        cellRefs.value.set(key, el);           // <-- WICHTIG: referenz speichern
    } else {
        cellRefs.value.delete(key);
    }
};

const monthList = computed(() => {
    const map = new Map<string, { start: string; end: string }>();
    for (const d of props.days) {
        const iso = d.withoutFormat as string; // 'YYYY-MM-DD'
        if (!iso) continue;
        const key = iso.slice(0, 7); // 'YYYY-MM'
        if (!map.has(key)) map.set(key, { start: iso, end: iso });
        else {
            const rec = map.get(key)!;
            if (iso < rec.start) rec.start = iso;
            if (iso > rec.end) rec.end = iso;
        }
    }
    return Array.from(map.entries())
        .sort((a, b) => a[0].localeCompare(b[0]))
        .map(([key, range]) => ({ key, ...range }));
});

const monthIndexByKey = computed(() => {
    const idx = new Map<string, number>();
    monthList.value.forEach((m, i) => idx.set(m.key, i));
    return idx;
});

const loadedMonths = ref<Set<string>>(new Set());
const loadingMonths = ref<Set<string>>(new Set());
const monthControllers = new Map<string, AbortController>();
let currentEpoch = 0;
const monthEpoch = new Map<string, number>();
const MAX_LOADED_MONTHS = 24;
function pruneLoadedIfNeeded() {
    if (loadedMonths.value.size <= MAX_LOADED_MONTHS) return;
    const toRemove = loadedMonths.value.size - MAX_LOADED_MONTHS;
    let i = 0;
    for (const key of loadedMonths.value) {
        if (i >= toRemove) break;
        removeCalendarMonthData(key);
        loadedMonths.value.delete(key);
        i++;
    }
}

function removeCalendarMonthData(monthKey: string) {
    ensureCalendarShape();
    const rooms: any[] = newCalendarData.value;

    for (const room of rooms) {
        if (!room?.content || typeof room.content !== 'object') continue;

        const nextContent: Record<string, any> = {};
        for (const deKey of Object.keys(room.content)) {
            const iso = deDateToIso(deKey);
            if (!iso || !isoInMonth(iso, monthKey)) {
                nextContent[deKey] = room.content[deKey];
            }
        }
        room.content = nextContent;
    }
}

function setCalendarMonthData(monthKey: string, incomingCalendar: any) {
    ensureCalendarShape();

    const incRooms: any[] = Array.isArray(incomingCalendar) ? incomingCalendar : [];
    if (incRooms.length === 0) return;
    if (!Array.isArray(newCalendarData.value) || newCalendarData.value.length === 0) {
        newCalendarData.value = incRooms.map((inc) => {
            const incContent = inc?.content && typeof inc.content === 'object' ? inc.content : {};
            const pruned: Record<string, any> = {};

            for (const deKey of Object.keys(incContent)) {
                const iso = deDateToIso(deKey);
                if (iso && isoInMonth(iso, monthKey)) {
                    pruned[deKey] = incContent[deKey];
                }
            }

            return {
                roomId: inc?.roomId,
                roomName: inc?.roomName ?? '',
                content: pruned,
            };
        });
        loadedMonths?.value?.add?.(monthKey);
        return;
    }
    const targetRooms: any[] = newCalendarData.value;
    const targetByRoomId = new Map<number, any>();
    for (const r of targetRooms) {
        if (r && typeof r.roomId !== 'undefined') {
            targetByRoomId.set(r.roomId, r);
        }
    }

    for (const inc of incRooms) {
        const roomId = inc?.roomId;
        if (roomId == null) continue;

        let target = targetByRoomId.get(roomId);
        if (!target) {
            target = { roomId: roomId, roomName: inc?.roomName ?? '', content: {} };
            targetRooms.push(target);
            targetByRoomId.set(roomId, target);
        }
        if (!target.content || typeof target.content !== 'object') {
            target.content = {};
        }

        const pruned: Record<string, any> = {};
        for (const deKey of Object.keys(target.content)) {
            const iso = deDateToIso(deKey);
            if (!iso || !isoInMonth(iso, monthKey)) {
                pruned[deKey] = target.content[deKey];
            }
        }

        const incContent = inc?.content && typeof inc.content === 'object' ? inc.content : {};
        for (const deKey of Object.keys(incContent)) {
            const iso = deDateToIso(deKey);
            if (iso && isoInMonth(iso, monthKey)) {
                pruned[deKey] = incContent[deKey];
            }
        }

        target.content = pruned;
        if (inc?.roomName && inc.roomName !== target.roomName) {
            target.roomName = inc.roomName;
        }
    }
    newCalendarData.value = [...targetRooms];
}

async function loadMonth(key: string, epoch: number) {
    if (!key) return;
    if (loadedMonths.value.has(key) || loadingMonths.value.has(key)) return;

    const rec = monthList.value.find(m => m.key === key);
    if (!rec) return;
    monthEpoch.set(key, epoch);
    const prev = monthControllers.get(key);
    if (prev) prev.abort();

    const controller = new AbortController();
    monthControllers.set(key, controller);
    loadingMonths.value.add(key);

    try {
        const { data } = await axios.get(route("events.all"), {
            params: {
                start_date: rec.start,
                end_date: rec.end,
                isPlanning: props.isPlanning,
            },
            signal: controller.signal,
        });
        if (monthEpoch.get(key) !== epoch) return;
        if (controller.signal.aborted) return;
        setCalendarMonthData(key, data?.calendar ?? []);

        loadedMonths.value.add(key);
        pruneLoadedIfNeeded();
    } catch (err) {
        const name = (err as any)?.name;
        if (name === 'CanceledError' || name === 'AbortError') return;
        if (typeof axios.isCancel === 'function' && axios.isCancel(err)) return;
        console.error('Fehler beim Laden Monat', key, err);
    } finally {
        loadingMonths.value.delete(key);
        if (monthControllers.get(key) === controller) {
            monthControllers.delete(key);
        }
    }
}

function windowKeysAround(idx: number, radius = 1): string[] {
    const keys: string[] = [];
    for (let off = -radius; off <= radius; off++) {
        const k = monthList.value[idx + off]?.key;
        if (k) keys.push(k);
    }
    return keys;
}

function cancelAllExcept(targets: string[]) {
    for (const [key, controller] of monthControllers.entries()) {
        if (!targets.includes(key)) {
            controller.abort();
            monthControllers.delete(key);
            loadingMonths.value.delete(key);
        }
    }
}

const focusedMonthKey = ref<string | null>(null);

let debounceTimer: number | null = null;
function debounce(fn: () => void, wait = 120) {
    return () => {
        if (debounceTimer) window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(fn, wait);
    };
}

async function ensureAroundInternal(key: string | null) {
    if (!key) return;
    const idx = monthIndexByKey.value.get(key);
    if (idx == null) return;
    const myEpoch = ++currentEpoch;
    const targets = windowKeysAround(idx, 1);
    cancelAllExcept(targets);
    await Promise.allSettled(targets.map(k => loadMonth(k, myEpoch)));
}

const ensureAround = debounce(() => {
    requestAnimationFrame(() => ensureAroundInternal(focusedMonthKey.value));
}, 120);

let monthObserver: IntersectionObserver | null = null;
const monthSentinelSeen = ref<Set<string>>(new Set());

function initMonthObserver() {
    if (monthObserver) return;
    monthObserver = new IntersectionObserver((entries) => {
        let best: { key: string; ratio: number } | null = null;
        for (const entry of entries) {
            if (!entry.isIntersecting) continue;
            const key = entry.target.getAttribute('data-month');
            if (!key) continue;
            const ratio = entry.intersectionRatio ?? 0;
            if (!best || ratio > best.ratio) best = { key, ratio };
        }
        if (best?.key) {
            focusedMonthKey.value = best.key;
            ensureAround();
        }
    }, { root: null, rootMargin: '1200px 0px', threshold: [0.1, 0.5, 0.75] });
}

function registerMonthSentinel(el, day) {
    if (!el) return;
    const key = monthKeyFromDay(day);
    if (!key || monthSentinelSeen.value.has(key)) return;
    monthSentinelSeen.value.add(key);
    initMonthObserver();
    monthObserver!.observe(el);
}

function waitUntil(pred: () => boolean, { interval = 30, timeout = 5000 } = {}): Promise<void> {
    return new Promise((resolve, reject) => {
        const start = Date.now();
        const t = setInterval(() => {
            if (pred()) {
                clearInterval(t);
                resolve();
            } else if (Date.now() - start > timeout) {
                clearInterval(t);
                resolve();
            }
        }, interval);
    });
}

function pickInitialMonthKey(): string | null {
    const todayIsoMonth = new Date().toISOString().slice(0, 7);
    if (monthIndexByKey.value.has(todayIsoMonth)) return todayIsoMonth;
    return monthList.value[0]?.key ?? null;
}

const didInitialLoad = ref(false);

async function runInitialLoad() {
    await nextTick();
    await waitUntil(() => monthList.value.length > 0);

    const initialKey = pickInitialMonthKey();
    if (!initialKey) {
        console.warn('[Calendar] Kein initialer Monat ermittelbar (monthList leer).');
        return;
    }
    focusedMonthKey.value = initialKey;
    const epoch = ++currentEpoch;
    const idx = monthIndexByKey.value.get(initialKey)!;
    const targets = windowKeysAround(idx, 1);
    ensureCalendarShape();
    cancelAllExcept(targets);
    await Promise.allSettled(targets.map(k => loadMonth(k, epoch)));

    didInitialLoad.value = true;
}


onMounted(async () => {
    await runInitialLoad();

    const ShiftCalendarListener = useShiftCalendarListener(newCalendarData);
    ShiftCalendarListener.init();
    initMonthObserver();
    if (focusedMonthKey.value && !loadedMonths.value.has(focusedMonthKey.value)) {
        const idx = monthIndexByKey.value.get(focusedMonthKey.value)!;
        const epoch = ++currentEpoch;
        const targets = windowKeysAround(idx, 1);
        cancelAllExcept(targets);
        await Promise.allSettled(targets.map(k => loadMonth(k, epoch)));
    }


    // Listen for fullscreen changes to reset isFullscreen when exiting
    document.addEventListener('fullscreenchange', handleFullscreenChange);
    document.addEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.addEventListener('mozfullscreenchange', handleFullscreenChange);
    document.addEventListener('msfullscreenchange', handleFullscreenChange);
});

onBeforeUnmount(() => {
    if (monthObserver) monthObserver.disconnect();
    monthObserver = null;
    monthSentinelSeen.value.clear();
    cancelAllExcept([]);

    // Remove fullscreen event listeners
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
    document.removeEventListener('msfullscreenchange', handleFullscreenChange);
});

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
const cancelMultiEditDuplicateSelection = () => {
    // Clear event selections but keep multi-edit mode active
    editEvents.value = [];
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
};

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
const handleFullscreenChange = () => {
    // Check if still in fullscreen mode
    const isCurrentlyFullscreen = !!(document.fullscreenElement || document.webkitFullscreenElement || document.mozFullScreenElement || document.msFullscreenElement);
    isFullscreen.value = isCurrentlyFullscreen;
};
const closeMultiEditModal = (closedOnPurpose) => {
    showMultiEditModal.value = false;
    if (closedOnPurpose) {
        // Clear event selections but keep multi-edit mode active
        editEvents.value = [];
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
    }
};
const closeMultiDuplicateModal = (closedOnPurpose) => {
    showMultiDuplicateModal.value = false;
    if (closedOnPurpose) {
        // Clear event selections but keep multi-edit mode active
        editEvents.value = [];
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
    }
};
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
const closeDeleteSelectedEventsModal = (closedOnPurpose) => {
    openDeleteSelectedEventsModal.value = false;
    if (closedOnPurpose) {
        // Clear event selections but keep multi-edit mode active
        editEvents.value = [];
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
    }
};
const deleteSelectedEvents = () => {
    axios.post(route("multi-edit.delete"), { events: editEvents.value })
        .finally(() => {
            openDeleteSelectedEventsModal.value = false;
            // Clear event selections but keep multi-edit mode active
            editEvents.value = [];
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
        });
};
const jumpToDayOfMonth = (day) => {
    const dayElement = document.querySelector(`.day-container[data-day-to-jump="${day}"]`);
    if (dayElement) window.scrollTo({ top: dayElement.offsetTop - 130, behavior: "smooth" });
};
const approveRequests = () => {
    router.post(route("event-verifications.approved-by-events"), { events: editEvents.value }, {
        preserveScroll: true, preserveState: true, onSuccess: () => {
            // Clear event selections but keep multi-edit mode active
            editEvents.value = [];
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
        }
    });
};
const requestVerification = () => {
    router.post(route("events-verifications.request-verification"), { events: editEvents.value }, {
        preserveScroll: true, preserveState: true, onSuccess: () => {
            // Clear event selections but keep multi-edit mode active
            editEvents.value = [];
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
        }
    });
};

const openAddSubEventModal = (mainEvent, mode, desiredEvent) => {
    if (mode === 'create') {
        //only set eventToEdit as base for new sub event
        eventToEdit.value = mainEvent;
    } else if (mode === 'edit') {
        //only set eventToEdit as base for new sub event
        eventToEdit.value = mainEvent;
        subEventToEdit.value = desiredEvent;
    }

    showAddSubEventModal.value = true;
}

const eventsInCell = (day: any, room: any) =>
    (room.content?.[dayKey(day)]?.events ?? []);

const eventsCount = (day: any, room: any) =>
    eventsInCell(day, room).length;

// 🔧 scrollToNextEvent: sucht jetzt explizit den INNEREN Scroll-Container (.events-scroll)
function scrollToNextEvent(day: DayLike, room: RoomLike) {
    const key = cellKey(day, room);
    let section = cellRefs.value.get(key) as HTMLElement | undefined;

    const roomId = String(room.roomId ?? room.id);
    if (!section) {
        const sel = `section[data-room-id="${roomId}"]#scroll_container-${day.withoutFormat}`;
        section = document.querySelector<HTMLElement>(sel) ?? undefined;
        if (section) cellRefs.value.set(key, section);
        else return;
    }

    // 👉 Inneren Scroll-Container greifen:
    const container = section.querySelector<HTMLElement>('.events-scroll');
    if (!container) return;

    const selector = `[id^="event_scroll-"][id$="-day-${day.withoutFormat}-room-${roomId}"]`;
    const nodes = Array.from(container.querySelectorAll<HTMLElement>(selector));
    if (!nodes.length) return;

    const pad = 6;
    const currentTop = container.scrollTop;

    const next = nodes.find(n => n.offsetTop > currentTop + pad);
    const targetTop = next ? Math.max(next.offsetTop - pad, 0) : 0;

    container.scrollTo({ top: targetTop, behavior: 'smooth' });
}

// When multi-edit is enabled, clicking an event toggles its selection
const onEventClick = (evt: any, e?: MouseEvent) => {
    if (!multiEdit.value) return;
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const nextState = !(evt?.considerOnMultiEdit === true);
    handleMultiEditEventCheckboxChange(evt.id, nextState, (evt?.room_id ?? evt?.roomId ?? null));
};
</script>

<style scoped>
/* bleibt wie gehabt; wirkt jetzt auf den inneren .events-scroll Container, wenn >1 Event */
.cell {
    overflow: auto;
    scrollbar-color: #d4d4d4 #f3f3f3; /* Firefox */
    scrollbar-width: thin;
}
/* WebKit */
.cell::-webkit-scrollbar { width: 2px !important; height: 2px !important; }
.cell::-webkit-scrollbar-thumb { background-color: #d4d4d4; border-radius: 10px; }
.cell::-webkit-scrollbar-track { background-color: #f3f3f3; }
</style>
