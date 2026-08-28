<template>
    <div id="myCalendar" ref="calendarRef" class="bg-white" :class="isFullscreen ? 'overflow-auto h-screen' : ''">
        <!-- Topbar -->
        <div ref="topbarRef" class="fixed z-[45] top-14 lg:top-0 left-0 lg:left-16 right-0">
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
                :daily-view="isDaily"
            >
                <template #buttonsRight>
                    <!-- Dezenter Hinweis-Chip statt des früheren vollflächigen roten Banners;
                         nur Icon + Anzahl, Details im Tooltip. Tooltip öffnet nach rechts,
                         damit er beim Umbruch der Leiste nicht unter der Mainnav (z-50) liegt -->
                    <div
                        v-if="eventsWithoutRoomLen > 0"
                        class="ui-button !bg-amber-50 !border-amber-300/80 !text-amber-800 text-xs"
                        @click="showEventsWithoutRoomComponent = true"
                    >
                        <ToolTipWithTextComponent
                            direction="right"
                            :text="String(eventsWithoutRoomLen)"
                            :icon="IconAlertTriangle"
                            icon-size="size-4"
                            tooltip-width="w-64"
                            :tooltip-text="eventsWithoutRoomLen + ' ' + $t('without room') + ' – ' + $t('There are events without a room in this period. Click to view and assign them.')"
                        />
                    </div>
                </template>
            </FunctionBarCalendar>
            <div
                v-if="hasFailedMonths"
                class="w-full h-8 px-4 py-2 bg-danger cursor-pointer"
                @click="retryFailedMonths"
            >
                <div class="flex items-center justify-center w-full h-full gap-x-1">
                    <IconAlertTriangle class="size-4 text-white" aria-hidden="true" />
                    <div class="text-white text-sm font-bold">
                        {{ $t('Some calendar data could not be loaded. Click here to retry.') }}
                    </div>
                </div>
            </div>
        </div>
        <!-- Grid -->
        <div :style="{ paddingTop: topbarHeight + 'px' }">
            <!-- Monatsansicht -->
            <div v-if="!isDaily && !atAGlance">
                <div class="w-max -ml-3">
                    <div :class="project ? 'bg-surface-canvas/50' : 'bg-white'">
                        <!-- Kopfzeile soll exakt dieselbe Raumreihenfolge/-filterung nutzen wie das Grid -->
                        <CalendarHeader :rooms="newCalendarData" :filtered-events-length="eventsWithoutRoomLen" :sticky-top="topbarHeight" :show-day-remarks-column="dayRemarksColumnVisible" :is-fullscreen="isFullscreen" />
                        <div
                            class="w-fit events-by-days-container"
                            :class="[isFullscreen ? 'mt-4' : '']"
                            ref="calendarToCalculate"
                        >
                            <!-- Eine Tageszeile = eigene Komponente: re-rendert nur, wenn sich
                                 IHRE Daten ändern (Slot-Ersatz beim Monats-Merge, Sichtbarkeits-
                                 wechsel, Zoom/Settings) — nicht mehr bei jedem Scroll-Tick des
                                 gesamten Kalenders. -->
                            <CalendarDayRow
                                v-for="(day, dayIndex) in days"
                                :key="day.fullDay"
                                :day="day"
                                :is-first-row="dayIndex === 0"
                                :visible="isDayRendered(day.withoutFormat)"
                                :calendar-rooms="newCalendarData"
                                :rooms="rooms"
                                :multi-edit="multiEdit"
                                :is-fullscreen="isFullscreen"
                                :day-sticky-top="dayStickyTop"
                                :day-remarks-column-visible="dayRemarksColumnVisible"
                                :day-remarks-can-edit="dayRemarksCanEdit"
                                :selected-cells="multiEdit ? selectedCells : null"
                                :is-admin="isAdmin"
                                :is-planning="isPlanning"
                                :verifier-for-event-typ-ids="verifierForEventTypIds"
                                :first-project-tab-id="first_project_tab_id"
                                :first-project-shift-tab-id="firstProjectShiftTabId"
                                :register-row-el="registerRowElement"
                                :unregister-row-el="unregisterRowElement"
                                @edit-event="showEditEventModel"
                                @open-add-sub-event-modal="openAddSubEventModal"
                                @open-confirm-modal="openDeleteEventModal"
                                @show-decline-event-modal="openDeclineEventModal"
                                @accept-room-request="acceptSingleRoomRequest"
                                @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                @shift-edited="refetchMonthForDay"
                                @toggle-cell="toggleCellSelection"
                                @open-new-event="openNewEventModalWithBaseData"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <div v-else-if="usePage().props.auth.user.calendar_daily_view && !usePage().props.auth.user.at_a_glance">
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
                    @accept-room-request="acceptSingleRoomRequest"
                    @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                    @shift-edited="refetchMonthForDay"
                    :verifierForEventTypIds="verifierForEventTypIds"
                    :is-planning="isPlanning"
                />
            </div>
            <div class="w-max" v-else>
                <div class="flex items-center sticky gap-0.5 h-16 bg-surface-inverse z-30 top-[64px] rounded-lg mb-3">
                    <div v-for="room in newCalendarData" :key="room.roomId ?? room.id">
                        <div :style="{ minWidth: columnWidth + 'px', maxWidth: columnWidth + 'px', width: columnWidth + 'px' }" class="flex items-center h-full truncate">
                            <SingleRoomInHeader :room="room" is-light />
                        </div>
                    </div>
                </div>
                <div class="flex gap-0.5">
                    <div v-for="room in newCalendarData" :key="room.roomId ?? room.id" class="flex flex-col" :style="{ minWidth: columnWidth + 'px', maxWidth: columnWidth + 'px', width: columnWidth + 'px' }">
                        <template v-for="day in days" :key="day.fullDay">
                            <div v-for="item in itemsInCell(day, room)" :key="`${item.type}-${item.data.id}`" class="mb-0.5" :id="'scroll_container-' + day.withoutFormat">
                                <div v-if="item.type === 'shift'" class="py-0.5">
                                    <ShiftInCalendarCell
                                        :shift="item.data"
                                        :day="dayKey(day)"
                                        @shift-edited="refetchMonthForDay(day)"
                                    />
                                </div>
                                <div v-else class="py-0.5" @click="onEventClick(item.data, $event)">
                                    <AsyncSingleEventInCalendar
                                        :event="item.data"
                                        :multi-edit="multiEdit"
                                        :font-size="fontSizeCalc"
                                        :line-height="lineHeightCalc"
                                        :rooms="rooms"
                                        :has-admin-role="isAdmin"
                                        :width="eventCardWidth"
                                        :first_project_tab_id="first_project_tab_id"
                                        :firstProjectShiftTabId="firstProjectShiftTabId"
                                        @edit-event="showEditEventModel"
                                        @edit-sub-event="openAddSubEventModal"
                                        @open-add-sub-event-modal="openAddSubEventModal"
                                        @open-confirm-modal="openDeleteEventModal"
                                        @show-decline-event-modal="openDeclineEventModal"
                                        @accept-room-request="acceptSingleRoomRequest"
                                        @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                        :verifierForEventTypIds="verifierForEventTypIds"
                                        :is-planning="isPlanning"
                                        :cell-day="day.withoutFormat"
                                    />
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Multi-Edit Bottom Bar -->
        <div class="fixed bottom-0 w-full bg-surface-inverse/30 z-[45] pointer-events-none py-3" v-if="multiEdit">
            <!-- Auswahl-Zähler bzw. Bedien-Hinweis -->
            <div class="flex items-center justify-center pb-2">
                <span class="rounded-full bg-surface-inverse/90 text-white text-xs px-3 py-1 select-none">
                    <template v-if="checkedCount > 0 || selectedCellCount > 0">
                        {{ $t('{0} event(s) · {1} cell(s) selected', [checkedCount, selectedCellCount]) }}
                    </template>
                    <template v-else>
                        {{ $t('Click events to select them - click free cell space to select cells') }}
                    </template>
                </span>
            </div>
            <!-- Zellen ausgewählt: erstellen bzw. (mit Terminen kombiniert) duplizieren -->
            <div class="flex flex-wrap items-center justify-center gap-2 px-4" v-if="selectedCellCount > 0">
                <FormButton
                    v-if="checkedCount === 0 && (isAdmin || can('create events without request') || can('can edit planning calendar'))"
                    class="transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="showMultiCellCreateModal = true"
                    :text="$t('Create event in {0} cells', [selectedCellCount])"
                />
                <FormButton
                    v-else
                    class="transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="showMultiCellDuplicateModal = true"
                    :text="$t('Duplicate {0} event(s) into {1} cells', [checkedCount, selectedCellCount])"
                />
                <!-- Verschieben nur bei genau EINER Zelle — bei mehreren Zellen wäre
                     dasselbe Original nicht mehrfach verschiebbar -->
                <FormButton
                    v-if="checkedCount > 0 && selectedCellCount === 1"
                    class="transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="showMultiCellMoveModal = true"
                    :text="$t('Move {0} event(s) into this cell', [checkedCount])"
                />
                <FormButton
                    class="bg-danger hover:bg-danger/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="cancelMultiEditDuplicateSelection"
                    :text="$t('Cancel selection')"
                />
            </div>
            <div class="flex flex-wrap items-center justify-center gap-2 px-4" v-else>
                <!-- Bearbeiten (beide Kalender; im Planungskalender nur mit Bearbeitungsrecht) -->
                <template v-if="!isPlanning || can('can edit planning calendar') || isAdmin">
                    <FormButton
                        :disabled="checkedCount === 0"
                        @click="showMultiEditModal = true"
                        :text="checkedCount + ' Termin(e) verschieben'"
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                    />
                    <FormButton
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                        @click="showMultiDuplicateModal = true"
                        :disabled="checkedCount === 0"
                        :text="checkedCount + ' ' + $t('Duplicate events')"
                    />
                </template>
                <!-- Verifizierungs-Workflow (nur Planungskalender) -->
                <template v-if="isPlanning">
                    <FormButton
                        v-if="can('can see planning calendar') || isAdmin"
                        :disabled="checkedCount === 0"
                        @click="requestVerification"
                        :text="checkedCount + ' ' + $t('request verification')"
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                    />
                    <FormButton
                        v-if="can('can edit planning calendar') || isAdmin"
                        :disabled="checkedCount === 0"
                        @click="approveRequests"
                        :text="checkedCount + ' ' + $t('Approve events')"
                        class="transition-all duration-300 ease-in-out pointer-events-auto"
                    />
                    <FormButton
                        v-if="can('can edit planning calendar') || isAdmin"
                        class="bg-danger hover:bg-danger/70 transition-all duration-300 ease-in-out pointer-events-auto"
                        @click="showRejectEventVerificationRequestModal = true"
                        :disabled="checkedCount === 0"
                        :text="checkedCount + ' ' + $t('Reject events')"
                    />
                </template>
                <FormButton
                    v-if="hasSelectedRoomRequests"
                    class="bg-success hover:bg-success/80 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="bulkAcceptRoomRequests"
                    :disabled="checkedCount === 0"
                    :text="checkedCount + ' ' + $t('Accept requests')"
                />
                <FormButton
                    v-if="hasSelectedRoomRequests"
                    class="bg-danger hover:bg-danger/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="bulkDeclineRoomRequests"
                    :disabled="checkedCount === 0"
                    :text="checkedCount + ' ' + $t('Decline requests')"
                />
                <FormButton
                    v-if="!isPlanning || can('can edit planning calendar') || isAdmin"
                    class="bg-danger hover:bg-danger/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="openDeleteSelectedEventsModal = true"
                    :disabled="checkedCount === 0"
                    :text="checkedCount + ' ' + $t('Delete events')"
                />
                <FormButton
                    class="bg-danger hover:bg-danger/70 transition-all duration-300 ease-in-out pointer-events-auto"
                    @click="cancelMultiEditDuplicateSelection"
                    :disabled="checkedCount === 0"
                    :text="$t('Cancel selection')"
                />
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
            :isAdmin="isAdmin"
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
        <MultiDuplicateModal v-if="showMultiDuplicateModal" :checked-events="editEvents" :rooms="rooms" :is-planning="isPlanning" @closed="closeMultiDuplicateModal" />

        <MultiCellEventCreateModal
            v-if="showMultiCellCreateModal"
            :cells="selectedCellsList"
            :event-types="eventTypes"
            :event-statuses="eventStatuses"
            :rooms="rooms"
            :is-planning="isPlanning"
            @closed="closeMultiCellCreateModal"
        />
        <MultiCellDuplicateModal
            v-if="showMultiCellDuplicateModal"
            :event-ids="editEvents"
            :cells="selectedCellsList"
            :rooms="rooms"
            :is-planning="isPlanning"
            @closed="closeMultiCellDuplicateModal"
        />
        <MultiCellMoveModal
            v-if="showMultiCellMoveModal && selectedCellsList.length === 1"
            :event-ids="editEvents"
            :cell="selectedCellsList[0]"
            :rooms="rooms"
            @closed="closeMultiCellMoveModal"
        />

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
            :isAdmin="isAdmin"
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
import {computed, defineAsyncComponent, inject, nextTick, onBeforeUnmount, onMounted, provide, ref, shallowRef, triggerRef, watch} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import {IconAlertTriangle} from "@tabler/icons-vue";

import {usePermission} from "@/Composeables/Permission.js";
import {useTranslation} from "@/Composeables/Translation.js";
import {useShiftCalendarListener} from "@/Composeables/Listener/useShiftCalendarListener.js";
import {useCalendarZoom} from "@/Composeables/useCalendarZoom.js";
import {useDayRemarks} from "@/Composeables/useDayRemarks.js";
import {can} from "laravel-permission-to-vuejs";
import CalendarPlaceholder from "@/Components/Calendar/Elements/CalendarPlaceholder.vue";
import SingleRoomInHeader from "@/Components/Calendar/Elements/SingleRoomInHeader.vue";
import ToolTipWithTextComponent from "@/Components/ToolTips/ToolTipWithTextComponent.vue";
// Tageszeile synchron importieren: das Grid-Skelett muss beim ersten Render
// stehen, sonst springt die Scroll-Höhe beim Nachladen der Zeilen-Chunks.
import CalendarDayRow from "@/Components/Calendar/Elements/CalendarDayRow.vue";
import {dayKey, deKeyToIso, itemsInCell} from "@/Components/Calendar/calendarCellItems.js";

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
const isAdmin = computed(() => hasAdminRole());

const AsyncEventComponent = defineAsyncComponent({ loader: () => import("@/Layouts/Components/EventComponent.vue") });
const FunctionBarCalendar = defineAsyncComponent({ loader: () => import("@/Components/FunctionBars/FunctionBarCalendar.vue") });
const CalendarHeader = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/CalendarHeader.vue") });
const AsyncEventsWithoutRoomComponent = defineAsyncComponent({ loader: () => import("@/Layouts/Components/EventsWithoutRoomComponent.vue") });
const AsyncDailyViewCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/DailyViewCalendar.vue") });
const MultiDuplicateModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiDuplicateModal.vue") });
const AddSubEventModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/AddSubEventModal.vue") });
const DeclineEventModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/DeclineEventModal.vue") });
const ConfirmDeleteModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/ConfirmDeleteModal.vue") });
const FormButton = defineAsyncComponent({ loader: () => import("@/Layouts/Components/General/Buttons/FormButton.vue") });
const MultiEditModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiEditModal.vue") });
const MultiCellEventCreateModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiCellEventCreateModal.vue") });
const MultiCellDuplicateModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiCellDuplicateModal.vue") });
const MultiCellMoveModal = defineAsyncComponent({ loader: () => import("@/Layouts/Components/MultiCellMoveModal.vue") });
const RejectEventVerificationRequestModal = defineAsyncComponent({
    loader: () => import("@/Pages/EventVerification/Components/RejectEventVerificationRequestModal.vue"),
    delay: 200,
    timeout: 3000
});
const AsyncSingleEventInCalendar = defineAsyncComponent({
    loader: () =>  import('@/Components/Calendar/Elements/SingleEventInCalendar.vue'),
    loadingComponent: CalendarPlaceholder,
});
const ShiftInCalendarCell = defineAsyncComponent({
    loader: () => import('@/Components/Calendar/Elements/ShiftInCalendarCell.vue'),
});

// Tagesbemerkungen: Sichtbarkeit/Rechte zentral aus dem Composable.
// Anzeige-Updates (eigenes Speichern + Broadcasts) laufen über den reaktiven
// Live-Store in useDayRemarks — die day-Objekte hier sind NICHT zuverlässig
// reaktiv (computed/enrichDays + v-memo auf den Seiten), Mutationen daran
// würden kein Re-Render auslösen. Bearbeitet wird inline in der Zelle
// (DayRemarkCell); das Modal nutzen nur Schichtplan & Tagesansichten.
const {
    columnVisible: dayRemarksColumnVisible,
    canEdit: dayRemarksCanEdit,
    listenForDayRemarkUpdates,
} = useDayRemarks();
// Live-Updates anderer User → Store (Zellen/Chips lesen reaktiv daraus)
const stopDayRemarkListener = listenForDayRemarkUpdates();
onBeforeUnmount(() => stopDayRemarkListener());

// User & Settings
const user = computed(() => page.props.auth.user);
const settings = computed(() => user.value.calendar_settings);
const {
    zoomFactor: zoom_factor,
    columnWidth,
    rowHeight,
    eventCardWidth,
    isCompact,
    zoomIn,
    zoomOut,
    monthViewRequestId,
} = useCalendarZoom();
const isDaily = computed(() => !!user.value.calendar_daily_view);
const atAGlance = computed(() => !!user.value.at_a_glance);

// Maße/Styles der Tageszeilen/Zellen liegen jetzt in CalendarDayRow.vue —
// hier verbleiben nur die Werte, die die At-a-Glance-Ansicht braucht
// (columnWidth, eventCardWidth, fontSizeCalc, lineHeightCalc).
// Topbar count
const eventsWithoutRoomLen = computed(() =>
    Array.isArray(props.eventsWithoutRoom) ? props.eventsWithoutRoom.length : (props.eventsWithoutRoom?.length ?? 0)
);

// Dynamic topbar height measurement
const topbarRef = ref(null);
const calendarRef = ref(null);
const topbarHeight = ref(80); // default fallback

// Sticky offset for the date in the day column: topbar + room header (h-11) + spacing
const dayStickyTop = computed(() => topbarHeight.value + 44 + 8);
let topbarObserver = null;

// State
const multiEdit = ref(false);
// Für die Kompakt-Terminkacheln als Inject statt Prop: die lesen den Wert nur
// im Klick-Handler (nicht im Render) — so re-rendern beim Multi-Edit-Toggle
// nicht tausende gemountete Kompakt-Kacheln, deren Optik sich gar nicht ändert.
provide('calendarMultiEdit', multiEdit);
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

const fontSizeCalc = computed(() => `max(calc(${zoom_factor.value} * 0.875rem), 10px)`);
const lineHeightCalc = computed(() => `max(calc(${zoom_factor.value} * 1.25rem), 1.3)`);

type DayLike = { withoutFormat: string };
type RoomLike = { id?: number|string; roomId?: number|string };

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

const cellKey = (day, room) => `${day.withoutFormat}:${(room.roomId ?? room.id)}`;

// ---------- Zeilen-Sichtbarkeit (Mount-only mit LRU-Obergrenze) ----------
// Ein IntersectionObserver auf den 365 Zeilen-Wurzeln mountet Zell-Inhalte
// kurz vor dem Viewport. Einmal gemountete Zeilen BLEIBEN gemountet — der
// DOM ist der Zwischenspeicher: Zurückscrollen in besuchte Bereiche baut
// nichts neu auf (Kundenanforderung: große Zeiträume, viel Hin- und
// Herscrollen). Begrenzt wird nicht über die Entfernung, sondern über eine
// zoomabhängige OBERGRENZE an gemounteten Zeilen (LRU): erst wenn sie
// überschritten ist, werden die am längsten nicht gesehenen, nicht
// viewport-nahen Zeilen wieder entladen. Bei Kompakt-Zoom (billige Pillen)
// deckt die Grenze einen ganzen Jahres-Zeitraum ab; bei 100 %+ (teure
// Vollkarten) hält sie den DOM in Schach. Updates werden pro Frame gesammelt
// (rAF), damit ein Scroll-Tick höchstens EIN Re-Render auslöst.
const renderedDayKeys = shallowRef(new Set<string>());
const isDayRendered = (key: string) => renderedDayKeys.value.has(key);

// Zeilen, die aktuell im nahen Beobachtungsfenster liegen — nie evikten.
const nearRowKeys = new Set<string>();
// LRU-Reihenfolge: key -> zuletzt gesehen (monoton steigende Sequenz)
const rowLastSeen = new Map<string, number>();
let rowSeenSeq = 0;

// Budget: ~24.000px an Zeilen bleiben gemountet (Untergrenze 120 Zeilen).
// 33px-Zeilen (40 %): 727 → ganzer Jahres-Zeitraum bleibt stehen;
// 212px-Zeilen (100 %): ~113 → knapp 4 Monate Vollkarten.
const mountedRowCap = computed(() => Math.max(120, Math.round(24000 / rowHeight.value)));

let rowNearObserver: IntersectionObserver | null = null;
let visibilityFlushHandle: number | null = null;
let visibilityFlushTimeout: number | null = null;
const pendingShow = new Set<string>();

function evictRowsOverCap(keys: Set<string>): boolean {
    const cap = mountedRowCap.value;
    if (keys.size <= cap) return false;
    const candidates: Array<[string, number]> = [];
    for (const key of keys) {
        if (!nearRowKeys.has(key)) {
            candidates.push([key, rowLastSeen.get(key) ?? 0]);
        }
    }
    candidates.sort((a, b) => a[1] - b[1]); // am längsten nicht gesehen zuerst
    let removed = false;
    let toRemove = keys.size - cap;
    for (const [key] of candidates) {
        if (toRemove <= 0) break;
        keys.delete(key);
        toRemove--;
        removed = true;
    }
    return removed;
}

function flushRowVisibility() {
    if (visibilityFlushHandle !== null) {
        cancelAnimationFrame(visibilityFlushHandle);
        visibilityFlushHandle = null;
    }
    if (visibilityFlushTimeout !== null) {
        window.clearTimeout(visibilityFlushTimeout);
        visibilityFlushTimeout = null;
    }
    if (!pendingShow.size) return;
    const keys = renderedDayKeys.value;
    let changed = false;
    for (const key of pendingShow) {
        if (!keys.has(key)) { keys.add(key); changed = true; }
    }
    pendingShow.clear();
    if (evictRowsOverCap(keys)) changed = true;
    if (changed) triggerRef(renderedDayKeys);
}

function scheduleVisibilityFlush() {
    if (visibilityFlushHandle !== null || visibilityFlushTimeout !== null) return;
    // rAF batcht pro Frame — feuert in Hintergrund-Tabs aber NIE. Der
    // setTimeout-Fallback stellt sicher, dass der Flush auch dann läuft
    // (z.B. Tab im Hintergrund geöffnet/aktualisiert); wer zuerst dran ist,
    // räumt beide Handles ab.
    visibilityFlushHandle = requestAnimationFrame(flushRowVisibility);
    visibilityFlushTimeout = window.setTimeout(flushRowVisibility, 60);
}

function initRowVisibilityObservers() {
    if (rowNearObserver) return;
    rowNearObserver = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            const key = entry.target.getAttribute('data-row-day');
            if (!key) continue;
            if (entry.isIntersecting) {
                nearRowKeys.add(key);
                rowLastSeen.set(key, ++rowSeenSeq);
                pendingShow.add(key);
            } else {
                // Verlassen des Fensters entlädt NICHTS — es endet nur der
                // Evict-Schutz und die LRU-Uhr dieser Zeile bleibt stehen.
                nearRowKeys.delete(key);
            }
        }
        scheduleVisibilityFlush();
    }, { root: null, rootMargin: '1200px 0px', threshold: 0 });
}

// Von CalendarDayRow beim Mount/Unmount aufgerufen (stabile Funktions-Props):
// registriert die Zeilen-Wurzel für Sichtbarkeits- UND Monatsfokus-Observer.
const registerRowElement = (el: HTMLElement, day: DayLike) => {
    initRowVisibilityObservers();
    initMonthObserver();
    rowNearObserver!.observe(el);
    monthObserver!.observe(el);
};
const unregisterRowElement = (el: HTMLElement) => {
    rowNearObserver?.unobserve(el);
    monthObserver?.unobserve(el);
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
// Fehlgeschlagene Monats-Loads: key -> Fehlversuche. Ohne Tracking würde jeder
// Scroll-Trigger endlos neu laden und der User sähe nur leere Zellen (Prod-Bug).
const failedMonths = ref<Map<string, number>>(new Map());
const MAX_MONTH_LOAD_RETRIES = 3;
const hasFailedMonths = computed(() =>
    Array.from(failedMonths.value.values()).some((count) => count >= MAX_MONTH_LOAD_RETRIES)
);
async function retryFailedMonths() {
    failedMonths.value.clear();
    await ensureAroundInternal(focusedMonthKey.value);
}
const monthControllers = new Map<string, AbortController>();
let currentEpoch = 0;
const monthEpoch = new Map<string, number>();
// Daten-Zwischenspeicher: geladene Monate bleiben im Speicher (Kunden laden
// große Zeiträume und scrollen viel hin und her — 36 Monate ≈ 3 Jahre à
// ~1,6 MB JSON sind unkritisch). Erst darüber wird geräumt, und zwar die
// Monate, die am WEITESTEN vom aktuellen Fokus entfernt sind — nie die
// gerade betrachteten (der alte Code räumte in Einfüge-Reihenfolge).
const MAX_LOADED_MONTHS = 36;
function pruneLoadedIfNeeded() {
    if (loadedMonths.value.size <= MAX_LOADED_MONTHS) return;
    const focusIdx = focusedMonthKey.value
        ? (monthIndexByKey.value.get(focusedMonthKey.value) ?? 0)
        : 0;
    const byDistanceDesc = [...loadedMonths.value].sort((a, b) => {
        const da = Math.abs((monthIndexByKey.value.get(a) ?? 0) - focusIdx);
        const db = Math.abs((monthIndexByKey.value.get(b) ?? 0) - focusIdx);
        return db - da;
    });
    let toRemove = loadedMonths.value.size - MAX_LOADED_MONTHS;
    for (const key of byDistanceDesc) {
        if (toRemove <= 0) break;
        removeCalendarMonthData(key);
        loadedMonths.value.delete(key);
        toRemove--;
    }
}

function removeCalendarMonthData(monthKey: string) {
    ensureCalendarShape();
    // Nur die Slots des Monats einzeln löschen — die Slot-Identität aller
    // anderen Monate bleibt stabil, betroffene Zeilen re-rendern gezielt.
    for (const room of newCalendarData.value) {
        if (!room?.content || typeof room.content !== 'object') continue;
        for (const deKey of Object.keys(room.content)) {
            const iso = deDateToIso(deKey);
            if (iso && isoInMonth(iso, monthKey)) {
                delete room.content[deKey];
            }
        }
    }
}

function setCalendarMonthData(monthKey: string, incomingCalendar: any) {
    ensureCalendarShape();

    const incRooms: any[] = Array.isArray(incomingCalendar) ? incomingCalendar : [];
    if (incRooms.length === 0) return;
    if (!Array.isArray(newCalendarData.value) || newCalendarData.value.length === 0) {
        const mapped = incRooms.map((inc) => {
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
                roomColor: inc?.roomColor ?? null,
                content: pruned,
            };
        });

        // Sort rooms to match the order from the rooms prop (position-based from DB)
        const roomOrder = new Map<number, number>();
        (props.rooms as any[]).forEach((r: any, idx: number) => {
            roomOrder.set(r.id, idx);
        });
        mapped.sort((a: any, b: any) => {
            const posA = roomOrder.get(a.roomId) ?? Number.MAX_SAFE_INTEGER;
            const posB = roomOrder.get(b.roomId) ?? Number.MAX_SAFE_INTEGER;
            return posA - posB;
        });

        newCalendarData.value = mapped;
        loadedMonths?.value?.add?.(monthKey);
        return;
    }
    // MERGE statt Komplett-Ersatz: Raum-Objekte und das Räume-Array behalten
    // ihre Identität (und damit alle vorhandenen Felder wie roomColor), nur
    // die Tages-Slots des geladenen Monats werden einzeln ersetzt. Dadurch
    // re-rendern ausschliesslich die Zeilen dieses Monats — ein fertiger
    // Monats-Request invalidiert nicht mehr das gesamte Grid.
    const targetRooms: any[] = newCalendarData.value;
    const targetByRoomId = new Map<number, any>();
    for (const r of targetRooms) {
        if (r && typeof r.roomId !== 'undefined') {
            targetByRoomId.set(r.roomId, r);
        }
    }

    let addedRoom = false;
    for (const inc of incRooms) {
        const roomId = inc?.roomId;
        if (roomId == null) continue;

        let target = targetByRoomId.get(roomId);
        if (!target) {
            target = { roomId: roomId, roomName: inc?.roomName ?? '', roomColor: inc?.roomColor ?? null, content: {} };
            targetRooms.push(target);
            targetByRoomId.set(roomId, target);
            addedRoom = true;
        }
        if (!target.content || typeof target.content !== 'object') {
            target.content = {};
        }

        const incContent = inc?.content && typeof inc.content === 'object' ? inc.content : {};

        // Alte Slots dieses Monats entfernen, die der neue Load nicht mehr liefert
        for (const deKey of Object.keys(target.content)) {
            const iso = deDateToIso(deKey);
            if (iso && isoInMonth(iso, monthKey) && !(deKey in incContent)) {
                delete target.content[deKey];
            }
        }
        // Neue/aktualisierte Slots einzeln ersetzen (Slot-Identität = Invalidierung
        // für die cellItems-Computed der betroffenen Zeile)
        for (const deKey of Object.keys(incContent)) {
            const iso = deDateToIso(deKey);
            if (iso && isoInMonth(iso, monthKey)) {
                target.content[deKey] = incContent[deKey];
            }
        }

        if (inc?.roomName && inc.roomName !== target.roomName) {
            target.roomName = inc.roomName;
        }
        if ((inc?.roomColor ?? null) !== (target.roomColor ?? null)) {
            target.roomColor = inc?.roomColor ?? null;
        }
    }

    // Nur bei neu hinzugekommenen Räumen neu sortieren — der Raumbestand ist
    // über die Monats-Loads hinweg stabil, unnötige sort()-Läufe würden die
    // Array-Iteration aller Zeilen invalidieren.
    if (addedRoom) {
        const roomOrder = new Map<number, number>();
        (props.rooms as any[]).forEach((r: any, idx: number) => {
            roomOrder.set(r.id, idx);
        });
        targetRooms.sort((a: any, b: any) => {
            const posA = roomOrder.get(a.roomId) ?? Number.MAX_SAFE_INTEGER;
            const posB = roomOrder.get(b.roomId) ?? Number.MAX_SAFE_INTEGER;
            return posA - posB;
        });
    }
}

async function loadMonth(key: string, epoch: number) {
    if (!key) return;
    if (loadedMonths.value.has(key) || loadingMonths.value.has(key)) return;
    if ((failedMonths.value.get(key) ?? 0) >= MAX_MONTH_LOAD_RETRIES) return;

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
        failedMonths.value.delete(key);
        pruneLoadedIfNeeded();
    } catch (err) {
        const name = (err as any)?.name;
        if (name === 'CanceledError' || name === 'AbortError') return;
        if (typeof axios.isCancel === 'function' && axios.isCancel(err)) return;
        failedMonths.value.set(key, (failedMonths.value.get(key) ?? 0) + 1);
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
        // Den einen laufenden Hintergrund-Load nie abbrechen — sonst wirft
        // jeder Fokuswechsel die fast fertige Antwort weg und der Monat muss
        // beim nächsten Idle-Slot komplett neu geladen werden.
        if (key === backgroundLoadingKey) continue;
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

// Beim Scrollen wird ein breiteres Fenster geladen als beim Erstaufruf: der
// Initial-Load bleibt bewusst bei Radius 1 (schnelles erstes Bild), waehrend
// des Scrollens sind Termine, die erst nach dem Anhalten erscheinen, das
// groessere Uebel. KEEP_RADIUS haelt bereits laufende Requests am Leben —
// vorher brach jeder Fokuswechsel die Nachbarmonate ab, sodass bei schnellem
// Scrollen ueber mehrere Monate gar nichts fertig wurde.
const SCROLL_WINDOW_RADIUS = 2;
const KEEP_RADIUS = 3;

async function ensureAroundInternal(key: string | null, radius = SCROLL_WINDOW_RADIUS) {
    if (!key) return;
    const idx = monthIndexByKey.value.get(key);
    if (idx == null) return;
    const myEpoch = ++currentEpoch;
    const targets = windowKeysAround(idx, radius);
    cancelAllExcept(windowKeysAround(idx, Math.max(radius, KEEP_RADIUS)));
    await Promise.allSettled(targets.map(k => loadMonth(k, myEpoch)));
    // Nur weiterladen, wenn inzwischen kein neuerer Fokus gewonnen hat
    if (myEpoch === currentEpoch) scheduleBackgroundPrefetch();
}

// ---------- Hintergrund-Vorladen des gesamten Zeitraums ----------
// Nach dem sichtbaren Fenster lädt ein Idle-Loop nach und nach ALLE Monate
// des Zeitraums (immer den, der dem aktuellen Fokus am nächsten ist), einen
// pro Schritt — so ist beim Hin- und Herscrollen irgendwann alles da und
// nichts muss mehr nachladen. Gestoppt wird am Cache-Budget
// (MAX_LOADED_MONTHS): weiter entfernte Monate würde das Pruning sofort
// wieder verwerfen. Fehlgeschlagene Monate respektieren ihr Retry-Limit.
let backgroundPrefetchHandle: number | null = null;
let backgroundLoadingKey: string | null = null;

function pickNextBackgroundMonth(): string | null {
    const list = monthList.value;
    if (!list.length) return null;
    const focusIdx = focusedMonthKey.value
        ? (monthIndexByKey.value.get(focusedMonthKey.value) ?? 0)
        : 0;
    let best: string | null = null;
    let bestDist = Infinity;
    for (let i = 0; i < list.length; i++) {
        const key = list[i].key;
        if (loadedMonths.value.has(key) || loadingMonths.value.has(key)) continue;
        if ((failedMonths.value.get(key) ?? 0) >= MAX_MONTH_LOAD_RETRIES) continue;
        const dist = Math.abs(i - focusIdx);
        if (dist < bestDist) { bestDist = dist; best = key; }
    }
    if (best !== null && bestDist > Math.floor(MAX_LOADED_MONTHS / 2)) return null;
    return best;
}

function cancelBackgroundPrefetch() {
    if (backgroundPrefetchHandle === null) return;
    if (typeof (window as any).cancelIdleCallback === 'function') {
        (window as any).cancelIdleCallback(backgroundPrefetchHandle);
    } else {
        window.clearTimeout(backgroundPrefetchHandle);
    }
    backgroundPrefetchHandle = null;
}

function scheduleBackgroundPrefetch() {
    if (backgroundPrefetchHandle !== null) return;
    const run = () => {
        backgroundPrefetchHandle = null;
        void runBackgroundPrefetchStep();
    };
    backgroundPrefetchHandle = typeof (window as any).requestIdleCallback === 'function'
        ? (window as any).requestIdleCallback(run, { timeout: 3000 })
        : window.setTimeout(run, 600);
}

async function runBackgroundPrefetchStep() {
    if (backgroundLoadingKey !== null) return; // es läuft schon ein Hintergrund-Load
    const key = pickNextBackgroundMonth();
    if (!key) return; // alles (im Budget) geladen — Neustart beim nächsten Fokuswechsel
    backgroundLoadingKey = key;
    try {
        await loadMonth(key, currentEpoch);
    } finally {
        backgroundLoadingKey = null;
    }
    scheduleBackgroundPrefetch();
}

const ensureAround = debounce(() => {
    requestAnimationFrame(() => ensureAroundInternal(focusedMonthKey.value));
}, 120);

let monthObserver: IntersectionObserver | null = null;
// Beobachtet werden ALLE Tageszeilen (Zeilen-Wurzeln aus CalendarDayRow),
// nicht nur die erste je Monat: bei einem Jahres-Zeitraum liegt die erste
// Zeile eines Monats schnell weit ausserhalb von Viewport + rootMargin.
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
    // Restlichen Zeitraum erst nach dem ersten Bild und nur im Leerlauf laden
    scheduleBackgroundPrefetch();
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

    // Observe topbar height for responsive layout
    if (topbarRef.value) {
        topbarObserver = new ResizeObserver((entries) => {
            for (const entry of entries) {
                topbarHeight.value = entry.contentRect.height;
            }
        });
        topbarObserver.observe(topbarRef.value);
    }

    // Strg/Cmd + Scrollrad zoomt durch die Stufen (statt Browser-Zoom)
    calendarRef.value?.addEventListener('wheel', handleWheelZoom, { passive: false });
});

onBeforeUnmount(() => {
    calendarRef.value?.removeEventListener('wheel', handleWheelZoom);
    if (monthObserver) monthObserver.disconnect();
    monthObserver = null;
    if (rowNearObserver) rowNearObserver.disconnect();
    rowNearObserver = null;
    if (visibilityFlushHandle !== null) {
        cancelAnimationFrame(visibilityFlushHandle);
        visibilityFlushHandle = null;
    }
    if (visibilityFlushTimeout !== null) {
        window.clearTimeout(visibilityFlushTimeout);
        visibilityFlushTimeout = null;
    }
    // Beim Unmount darf auch der laufende Hintergrund-Load sterben
    backgroundLoadingKey = null;
    cancelAllExcept([]);
    cancelBackgroundPrefetch();

    // Remove fullscreen event listeners
    document.removeEventListener('fullscreenchange', handleFullscreenChange);
    document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
    document.removeEventListener('mozfullscreenchange', handleFullscreenChange);
    document.removeEventListener('msfullscreenchange', handleFullscreenChange);

    // Clean up topbar observer
    if (topbarObserver) {
        topbarObserver.disconnect();
        topbarObserver = null;
    }

});

// ---------- Multi-Edit etc. ----------
const checkedCount = computed(() => editEvents.value.length);
// Nur die tatsächlichen Raumanfragen aus der Auswahl — normale Termine in einer
// gemischten Auswahl gehen nicht mit an die Bulk-Annehmen/-Ablehnen-Endpunkte.
const selectedRoomRequestIds = computed(() => {
    if (!editEvents.value.length) return [];
    const selectedIds = new Set(editEvents.value);
    const requestIds = [];
    for (const room of newCalendarData.value) {
        for (const slot of Object.values(room.content || {})) {
            for (const evt of (slot.events ?? [])) {
                if (selectedIds.has(evt.id) && evt.occupancy_option && !requestIds.includes(evt.id)) {
                    requestIds.push(evt.id);
                }
            }
        }
    }
    return requestIds;
});
const hasSelectedRoomRequests = computed(() => selectedRoomRequestIds.value.length > 0);

// Multi-Edit-Flags direkt auf den (reaktiven) Event-Objekten setzen: nur die
// betroffenen Terminkacheln lesen considerOnMultiEdit und re-rendern. Das
// frühere Neu-Aufbauen des kompletten Räume-Arrays per map+spread würde die
// Zeilen-Stabilität von CalendarDayRow zunichtemachen (Voll-Re-Render pro Klick).
function setEventConsiderFlag(eventId, considerOnMultiEdit) {
    for (const room of newCalendarData.value) {
        for (const slot of Object.values(room.content ?? {})) {
            for (const evt of (slot.events ?? [])) {
                if (evt.id === eventId && evt.considerOnMultiEdit !== considerOnMultiEdit) {
                    evt.considerOnMultiEdit = considerOnMultiEdit;
                }
            }
        }
    }
}

function clearAllConsiderFlags() {
    for (const room of newCalendarData.value) {
        for (const slot of Object.values(room.content ?? {})) {
            for (const evt of (slot.events ?? [])) {
                if (evt.considerOnMultiEdit) evt.considerOnMultiEdit = false;
            }
        }
    }
}

function handleMultiEditEventCheckboxChange(eventId, considerOnMultiEdit, eventRoomId) {
    if (considerOnMultiEdit) {
        if (!editEvents.value.includes(eventId)) editEvents.value.push(eventId);
    } else {
        editEvents.value = editEvents.value.filter(id => id !== eventId);
    }
    setEventConsiderFlag(eventId, considerOnMultiEdit);
}
function toggleMultiEdit(value) {
    multiEdit.value = value;
    if (!value) clearCellSelection();
    if (!value && editEvents.value.length) {
        clearAllConsiderFlags();
        editEvents.value = [];
    }
}
// ---------- Multi-Edit: Zellen-Auswahl (Tag×Raum) ----------
// Klick auf freie Zellenfläche im Multi-Edit wählt die Zelle aus. Nur Zellen
// gewählt → "Termin in N Zellen erstellen"; Termine UND Zellen gewählt →
// "M Termin(e) in N Zellen duplizieren".
const selectedCells = ref(new Map());
const selectedCellCount = computed(() => selectedCells.value.size);
const selectedCellsList = computed(() => Array.from(selectedCells.value.values()));
const showMultiCellCreateModal = ref(false);
const showMultiCellDuplicateModal = ref(false);
const showMultiCellMoveModal = ref(false);

const toggleCellSelection = (day, room) => {
    if (!multiEdit.value || day.isExtraRow) return;
    const key = cellKey(day, room);
    const next = new Map(selectedCells.value);
    if (next.has(key)) {
        next.delete(key);
    } else {
        next.set(key, { day: day.withoutFormat, room_id: room.roomId ?? room.id });
    }
    selectedCells.value = next;
};
const clearCellSelection = () => {
    if (selectedCells.value.size) selectedCells.value = new Map();
};

// Nach Erstellen/Duplizieren die betroffenen Monate neu laden (zuverlässiger
// als auf Broadcasts zu warten)
async function refetchMonthsForCells(cellList) {
    const keys = new Set(
        cellList.map((cell) => (cell.day ?? "").slice(0, 7)).filter(Boolean)
    );
    for (const key of keys) {
        loadedMonths.value.delete(key);
        failedMonths.value.delete(key);
        await loadMonth(key, ++currentEpoch);
    }
}

const closeMultiCellCreateModal = async (created) => {
    showMultiCellCreateModal.value = false;
    if (created) {
        const cells = selectedCellsList.value;
        clearCellSelection();
        await refetchMonthsForCells(cells);
    }
};

const closeMultiCellDuplicateModal = async (duplicated) => {
    showMultiCellDuplicateModal.value = false;
    if (duplicated) {
        const cells = selectedCellsList.value;
        clearCellSelection();
        cancelMultiEditDuplicateSelection();
        await refetchMonthsForCells(cells);
    }
};

const closeMultiCellMoveModal = async (moved) => {
    showMultiCellMoveModal.value = false;
    if (moved) {
        // Beim Verschieben ändern sich auch die Ursprungszellen — deshalb neben der
        // Ziel-Zelle auch die Monate der ausgewählten (verschobenen) Termine neu laden
        const cells = [...selectedCellsList.value];
        for (const room of newCalendarData.value) {
            for (const [d, slot] of Object.entries(room.content ?? {})) {
                if ((slot.events ?? []).some(e => e.considerOnMultiEdit)) {
                    cells.push({ day: d.includes('-') ? d : deKeyToIso(d) });
                }
            }
        }
        clearCellSelection();
        cancelMultiEditDuplicateSelection();
        await refetchMonthsForCells(cells);
    }
};

const cancelMultiEditDuplicateSelection = () => {
    // Clear event and cell selections but keep multi-edit mode active
    clearCellSelection();
    editEvents.value = [];
    clearAllConsiderFlags();
};

const openDeclineEventModal = (event) => { declineEvent.value = event; showDeclineEventModal.value = true; };
const acceptSingleRoomRequest = (event) => {
    router.put(route('events.accept', { event: event.id }), { accepted: true }, { preserveScroll: true });
};
const bulkAcceptRoomRequests = () => {
    router.put(route('events.bulk-accept'), { eventIds: selectedRoomRequestIds.value }, { preserveScroll: true });
};
const bulkDeclineRoomRequests = () => {
    router.put(route('events.bulk-decline'), { eventIds: selectedRoomRequestIds.value }, { preserveScroll: true });
};
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
        clearAllConsiderFlags();
    }
};
const closeMultiDuplicateModal = (closedOnPurpose) => {
    showMultiDuplicateModal.value = false;
    if (closedOnPurpose) {
        // Clear event selections but keep multi-edit mode active
        editEvents.value = [];
        clearAllConsiderFlags();
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
        clearAllConsiderFlags();
    }
};
const deleteSelectedEvents = () => {
    axios.post(route("multi-edit.delete"), { events: editEvents.value })
        .finally(() => {
            openDeleteSelectedEventsModal.value = false;
            // Clear event selections but keep multi-edit mode active
            editEvents.value = [];
            clearAllConsiderFlags();
        });
};
const jumpToDayOfMonth = async (day) => {
    // Globales `html { scroll-behavior: smooth }` (siehe app.blade.php) zwingt
    // sonst alle programmatischen Scrolls in eine Animation. Während des
    // Sprungs temporär abschalten, damit Korrektur-Scrolls instant wirken und
    // sich nicht gegenseitig abbrechen.
    const htmlEl = document.documentElement;
    const previousScrollBehavior = htmlEl.style.scrollBehavior;
    htmlEl.style.scrollBehavior = 'auto';

    try {
        // Zielmonat (und Nachbarn) vorab laden, damit Tagehöhen final stehen,
        // bevor wir die Scroll-Position berechnen (wichtig bei expand_days).
        const targetMonthKey = (day || '').slice(0, 7);
        if (targetMonthKey && monthIndexByKey.value.has(targetMonthKey)) {
            focusedMonthKey.value = targetMonthKey;
            await ensureAroundInternal(targetMonthKey);
            await nextTick();
            await new Promise(r => requestAnimationFrame(r));
        }

        const computeOffset = () => {
            const calendarHeader = document.querySelector('header.sticky');
            const headerHeight = calendarHeader ? (calendarHeader as HTMLElement).offsetHeight : 64;
            return topbarHeight.value + headerHeight;
        };

        const scrollOnce = () => {
            const dayElement = document.querySelector<HTMLElement>(`.day-container[data-day-to-jump="${day}"]`);
            if (!dayElement) return false;
            const totalOffset = computeOffset();
            const calendarEl = calendarRef.value as HTMLElement | null;

            if (isFullscreen.value && calendarEl) {
                const elementTop = dayElement.getBoundingClientRect().top
                    - calendarEl.getBoundingClientRect().top
                    + calendarEl.scrollTop;
                calendarEl.scrollTo({ top: Math.max(elementTop - totalOffset, 0), behavior: 'auto' });
            } else {
                const elementTop = dayElement.getBoundingClientRect().top + window.scrollY;
                window.scrollTo({ top: Math.max(elementTop - totalOffset, 0), behavior: 'auto' });
            }
            return true;
        };

        if (!scrollOnce()) return;

        // Nachkorrektur: IntersectionObserver kann während/nach dem Sprung weitere
        // Monate triggern, deren Events bei expand_days die Zeilenhöhe ändern und
        // damit die finale Y-Position des Zieltages verschieben. Mehrfach
        // korrigieren, bis die Position stabil ist (max. ~600ms).
        for (let i = 0; i < 4; i++) {
            await new Promise(r => setTimeout(r, 150));
            const dayElement = document.querySelector<HTMLElement>(`.day-container[data-day-to-jump="${day}"]`);
            if (!dayElement) continue;
            const totalOffset = computeOffset();
            const calendarEl = calendarRef.value as HTMLElement | null;
            const rect = dayElement.getBoundingClientRect();
            const currentTop = (isFullscreen.value && calendarEl)
                ? rect.top - calendarEl.getBoundingClientRect().top
                : rect.top;
            const diff = currentTop - totalOffset;
            if (Math.abs(diff) <= 1) break;
            if (isFullscreen.value && calendarEl) {
                calendarEl.scrollBy({ top: diff, behavior: 'auto' });
            } else {
                window.scrollBy({ top: diff, behavior: 'auto' });
            }
        }
    } finally {
        htmlEl.style.scrollBehavior = previousScrollBehavior;
    }
};
const approveRequests = () => {
    router.post(route("event-verifications.approved-by-events"), { events: editEvents.value }, {
        preserveScroll: true, preserveState: true, onSuccess: () => {
            // Clear event selections but keep multi-edit mode active
            editEvents.value = [];
            clearAllConsiderFlags();
        }
    });
};
const requestVerification = () => {
    router.post(route("events-verifications.request-verification"), { events: editEvents.value }, {
        preserveScroll: true, preserveState: true, onSuccess: () => {
            // Clear event selections but keep multi-edit mode active
            editEvents.value = [];
            clearAllConsiderFlags();
        }
    });
};

const closeShowRejectEventVerificationModal = () => {
    showRejectEventVerificationRequestModal.value = false;
    // Auswahl leeren, Multi-Edit-Modus bleibt aktiv (Zellen sind in diesem Zweig nie gewählt)
    cancelMultiEditDuplicateSelection();
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

// Zell-Logik (dayKey/itemsInCell/deKeyToIso) kommt aus calendarCellItems.js —
// geteilt mit CalendarDayRow und der At-a-Glance-Ansicht.

// Nach Schicht-Bearbeitung den betroffenen Monat neu laden
async function refetchMonthForDay(day: any) {
    const key = monthKeyFromDay(day);
    if (!key) return;
    loadedMonths.value.delete(key);
    failedMonths.value.delete(key);
    await loadMonth(key, ++currentEpoch);
}

// When multi-edit is enabled, clicking an event toggles its selection
const onEventClick = (evt: any, e?: MouseEvent) => {
    if (!multiEdit.value) return;
    if (e && typeof e.stopPropagation === 'function') e.stopPropagation();
    const nextState = !(evt?.considerOnMultiEdit === true);
    handleMultiEditEventCheckboxChange(evt.id, nextState, (evt?.room_id ?? evt?.roomId ?? null));
};

// ---------- Zoom-Bedienung ----------
// Strg/Cmd + Scrollrad: stufig zoomen, Browser-Zoom unterdrücken. Kurz
// gedrosselt, damit ein Radtick nicht mehrere Stufen überspringt.
let wheelZoomLockedUntil = 0;
const handleWheelZoom = (e: WheelEvent) => {
    if (!(e.ctrlKey || e.metaKey)) return;
    if (atAGlance.value) return;
    e.preventDefault();
    const now = Date.now();
    if (now < wheelZoomLockedUntil) return;
    wheelZoomLockedUntil = now + 180;
    if (e.deltaY > 0) zoomOut();
    else zoomIn();
};

// "Monatsansicht" aus dem Zoom-Dropdown: nach dem Sprung auf die kompakteste
// Stufe zum Anfang des aktuell fokussierten Monats scrollen.
watch(monthViewRequestId, async () => {
    const key = focusedMonthKey.value ?? monthList.value[0]?.key;
    if (!key) return;
    const firstDayOfMonth = (props.days as any[]).find(
        (d: any) => (d.withoutFormat || '').startsWith(key) && !d.isExtraRow
    );
    if (!firstDayOfMonth) return;
    await nextTick();
    await jumpToDayOfMonth(firstDayOfMonth.withoutFormat);
});
</script>

<!-- Zeilen-/Zell-Styles (month-separator-label, .cell-Scrollbars) liegen in CalendarDayRow.vue -->

