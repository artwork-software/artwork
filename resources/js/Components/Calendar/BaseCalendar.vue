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
                            <template
                                v-for="(day, dayIndex) in visibleDays"
                                :key="day.fullDay"
                            >
                            <!-- Monatsgrenze: schwarzer Balken über die ganze Zeile mit Monat + Jahr
                                 (gleiche Optik wie die dunklen Leisten in anderen Screens);
                                 auch für den allerersten Tag des Zeitraums, sonst fehlt dem
                                 obersten Block bei Start mitten im Monat die Orientierung -->
                            <div
                                v-if="(day.isFirstDayOfMonth || dayIndex === 0) && !day.isExtraRow"
                                class="month-separator flex items-center h-7 w-full bg-surface-inverse"
                            >
                                <span
                                    class="month-separator-label text-xs font-semibold text-white whitespace-nowrap px-3"
                                >
                                    {{ formatMonthLabel(day.withoutFormat) }}
                                </span>
                            </div>
                            <div
                                class="flex gap-0.5 day-container border-t"
                                :class="settings.high_contrast ? 'border-gray-500' : 'border-gray-300'"
                                :style="[dayRowStyle, dayRowBackgroundStyle(day)]"
                                :data-day="day.fullDay"
                                :data-day-to-jump="day.withoutFormat"
                                :data-month="monthKeyFromDay(day)"
                                :ref="el => registerMonthSentinel(el, day)"
                            >
                                <SingleDayInCalendar v-if="!day.isExtraRow" :isFullscreen="isFullscreen" :day="day" :sticky-top="dayStickyTop" />

                                <!-- Tagesbemerkungen: sticky Spalte direkt neben dem Datum (Inline-Editor in der Zelle) -->
                                <DayRemarkCell
                                    v-if="dayRemarksColumnVisible && !day.isExtraRow"
                                    :day="day"
                                    :editable="dayRemarksCanEdit"
                                    :is-fullscreen="isFullscreen"
                                    :sticky-top="dayStickyTop"
                                />

                                <!-- Räume -->
                                <template v-if="!day.isExtraRow">
                                    <template v-for="(room, roomIdx) in newCalendarData" :key="room.id ?? room.roomId ?? roomIdx">
                                        <!-- Eine Cell (Tag × Raum) -->
                                        <section
                                            :style="[cellStyle, cellSeparatorStyle]"
                                            :id="`scroll_container-${day.withoutFormat}`"
                                            :data-room-id="room.roomId ?? room.id"
                                            :ref="el => registerCell(el, day, room)"
                                            :class="[
                    'group/container relative',                 // Basis
                    multiEdit ? 'cursor-pointer' : '',
                    // Tagesgrenze liegt jetzt als durchgezogene Linie auf der Zeile (day-container),
                    // die Zelle trägt nur noch die vertikale Raumtrennlinie (cellSeparatorStyle)
                  ]"
                                            @click="toggleCellSelection(day, room)"
                                        >
                                            <!-- Multi-Edit: Zellen-Auswahl-Overlay (Klick auf freie Fläche;
                                                 Termin-Klicks stoppen die Propagation und landen hier nicht) -->
                                            <div
                                                v-if="multiEdit"
                                                class="absolute inset-0 z-30 pointer-events-none"
                                                :class="isCellSelected(day, room)
                                                    ? 'border-2 border-dashed border-accent-600 bg-accent-600/10'
                                                    : 'group-hover/container:border group-hover/container:border-dashed group-hover/container:border-accent-500/70'"
                                            >
                                                <div
                                                    v-if="isCellSelected(day, room)"
                                                    class="absolute top-0.5 right-0.5 rounded-full bg-accent-600 text-white p-0.5"
                                                >
                                                    <component :is="IconCirclePlus" class="size-3.5" stroke-width="2" />
                                                </div>
                                            </div>
                                            <!-- INNERER WRAPPER: hält Scrollbereich + Floating-Buttons -->
                                            <div :class="['relative w-full', settings.expand_days ? '' : 'h-full']">
                                                <!-- SCROLLBAR NUR WENN SINNVOLL -->
                                                <div
                                                    :class="[
                                                        'events-scroll',
                                                        settings.expand_days ? '' : 'h-full',
                                                        settings.expand_days ? 'overflow-visible flex flex-col' : 'overflow-auto cell'
                                                      ]"
                                                    :style="cellStyle"
                                                >
                                                    <!-- Nur rendern, wenn Cell (Tag×Raum) in/nahe Viewport; bei
                                                         expand_days zeilenweise (siehe isDayRowVisible), damit die
                                                         Auto-Zeilenhöhe beim X-Scrollen stabil bleibt -->
                                                    <template v-if="settings.expand_days ? isDayRowVisible(day) : isCellVisible(cellKey(day, room))">
                                                        <template v-for="(cellItems) in [itemsInCell(day, room)]" :key="0">
                                                            <template v-for="(item, idx) in cellItems" :key="`${item.type}-${item.data.id}`">
                                                                <div
                                                                    v-if="item.type === 'shift'"
                                                                    class="py-0.5"
                                                                    @click.stop
                                                                >
                                                                    <ShiftInCalendarCell
                                                                        :shift="item.data"
                                                                        :day="dayKey(day)"
                                                                        @shift-edited="refetchMonthForDay(day)"
                                                                    />
                                                                </div>
                                                                <div
                                                                    v-else
                                                                    :class="[
                                                                        'py-0.5',
                                                                        (settings.expand_days && !!item.data.allDay) ? 'flex-1 min-h-0' : ''
                                                                    ]"
                                                                    :id="`event_scroll-${idx}-day-${day.withoutFormat}-room-${(room.roomId ?? room.id)}`"
                                                                    @click="onEventClick(item.data, $event)"
                                                                >
                                                                    <AsyncSingleEventInCalendar
                                                                        v-memo="[item.data.id, item.data.updated_at, multiEdit, item.data.considerOnMultiEdit, fontSizeCalc, lineHeightCalc, cardWidthNum, day.withoutFormat, (room.roomId ?? room.id)]"
                                                                        :event="item.data"
                                                                        :multi-edit="multiEdit"
                                                                        :font-size="fontSizeCalc"
                                                                        :line-height="lineHeightCalc"
                                                                        :rooms="rooms"
                                                                        :has-admin-role="isAdmin"
                                                                        :width="cardWidthNum"
                                                                        :first_project_tab_id="first_project_tab_id"
                                                                        :firstProjectShiftTabId="firstProjectShiftTabId"
                                                                        :verifierForEventTypIds="verifierForEventTypIds"
                                                                        :is-planning="isPlanning"
                                                                        :is-height-full="settings.expand_days && !!item.data.allDay"
                                                                        :cell-day="day.withoutFormat"
                                                                        @edit-event="showEditEventModel"
                                                                        @edit-sub-event="openAddSubEventModal"
                                                                        @open-add-sub-event-modal="openAddSubEventModal"
                                                                        @open-confirm-modal="openDeleteEventModal"
                                                                        @show-decline-event-modal="openDeclineEventModal"
                                                                        @accept-room-request="acceptSingleRoomRequest"
                                                                        @changed-multi-edit-checkbox="handleMultiEditEventCheckboxChange"
                                                                    />
                                                                </div>
                                                            </template>
                                                            <!-- Platzhalter: weicher Abschluss, wenn wenig Inhalt. Nicht im
                                                                 Kompaktmodus: dort würde er die Einzeiler-Kachel über die
                                                                 flache Zeilenhöhe drücken und unnötige Scrollbalken erzeugen. -->
                                                            <div v-if="cellItems.length <= 1 && !settings.expand_days && !isCompact" class="h-2"></div>
                                                        </template>
                                                    </template>
                                                </div>

                                                <!-- "+"-Button: jetzt OBEN RECHTS, außerhalb des Scrollbereichs.
                                                     Im Multi-Edit ausgeblendet — dort wählt der Zellen-Klick die Zelle aus. -->
                                                <button
                                                    v-if="!multiEdit"
                                                    type="button"
                                                    class="pointer-events-auto group-hover/container:inline-flex hidden absolute bottom-1 right-3 z-20
                                                     items-center justify-center cursor-pointer gap-1 rounded-md text-sm font-medium
                                                     ring-0 bg-white/90 hover:bg-gray-50/90 focus:outline-none focus:ring-0
                                                     transition duration-200 ease-in-out"
                                                    :style="addEventButtonStyle"
                                                    :aria-label="$t('Add event')"
                                                    @click="openNewEventModalWithBaseData(day.withoutFormat, (room.roomId ?? room.id))"
                                                >
                                                    <component :is="IconCirclePlus" :style="addEventIconStyle" />
                                                </button>

                                            </div>
                                        </section>
                                    </template>
                                </template>
                            </div>
                            </template>
                            <!-- Nachschub-Marker: kommt er in Sichtweite, werden sofort
                                 weitere Tageszeilen gerendert (progressives Rendern) -->
                            <div
                                v-if="!allDaysRendered"
                                ref="dayFillSentinel"
                                class="h-px w-full"
                                aria-hidden="true"
                            ></div>
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
import {computed, defineAsyncComponent, inject, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, triggerRef, watch} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import axios from "axios";
import {IconAlertTriangle, IconCirclePlus} from "@tabler/icons-vue";

import {usePermission} from "@/Composeables/Permission.js";
import {useTranslation} from "@/Composeables/Translation.js";
import {useShiftCalendarListener} from "@/Composeables/Listener/useShiftCalendarListener.js";
import {useCalendarZoom} from "@/Composeables/useCalendarZoom.js";
import {useDayRemarks} from "@/Composeables/useDayRemarks.js";
import {formatMonthLabel} from "@/Composeables/calendarDateUtils.js";
import {can} from "laravel-permission-to-vuejs";
import CalendarPlaceholder from "@/Components/Calendar/Elements/CalendarPlaceholder.vue";
import SingleRoomInHeader from "@/Components/Calendar/Elements/SingleRoomInHeader.vue";
import ToolTipWithTextComponent from "@/Components/ToolTips/ToolTipWithTextComponent.vue";

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
const SingleDayInCalendar = defineAsyncComponent({ loader: () => import("@/Components/Calendar/Elements/SingleDayInCalendar.vue") });
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
const DayRemarkCell = defineAsyncComponent({
    loader: () => import('@/Components/Calendar/Elements/DayRemarkCell.vue'),
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

// Maße/Styles: Spaltenbreite ist vom Zoom entkoppelt (Anzeigeeinstellung),
// der Zoom steuert nur noch die Zeilenhöhe/Informationsdichte.
const cellWidthPx = computed(() => `${columnWidth.value}px`);
const cardWidthNum = computed(() => eventCardWidth.value);
const rowHeightPx = computed(() => `${rowHeight.value}px`);
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
// "+"-Button: 40px Zielgröße, aber nie höher als die Zoom-Zeilenhöhe
// (bei 40 % sind die Zeilen nur 33px hoch — dort auf Zeile minus Offset kappen).
const addEventButtonSize = computed(() => Math.min(40, Math.max(20, rowHeight.value - 8)));
const addEventButtonStyle = computed(() => ({
    width: `${addEventButtonSize.value}px`,
    height: `${addEventButtonSize.value}px`
}));
const addEventIconStyle = computed(() => {
    const size = Math.round(addEventButtonSize.value * 0.65);
    return { width: `${size}px`, height: `${size}px` };
});
// Vertikale Trennlinie zwischen den Raumspalten (inline statt Tailwind-Klasse,
// damit sie unabhängig vom Zeilen-Border-Style bleibt).
const cellSeparatorStyle = computed(() => ({
    borderLeft: `1px solid ${settings.value.high_contrast ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.18)'}`
}));

// Wochenend-/Feiertags-Einfärbung der ganzen Tageszeile (inkl. Datumsspalte).
// Feiertag schlägt Wochenende; Feiertagston wird aus holiday.color abgeleitet.
// Eintägige Feiertage färben deutlich, mehrtägige Zeiträume (Ferien) nur sehr
// dezent — sonst wären ganze Wochen farblich überladen. Liegen beide auf einem
// Tag, gewinnt der eintägige Feiertag.
const dayTintColor = (day) => {
    if (!day || day.isExtraRow) return null;
    const holidays = day.holidays ?? [];
    const singleDayHoliday = holidays.find(
        (holiday) => holiday?.color && (!holiday.end_date || holiday.end_date === holiday.date)
    );
    const coloredHoliday = singleDayHoliday ?? holidays.find((holiday) => holiday?.color);
    if (coloredHoliday) {
        const alpha = singleDayHoliday
            ? (settings.value.high_contrast ? '59' : '33')
            : (settings.value.high_contrast ? '33' : '1A');
        return `${coloredHoliday.color}${alpha}`;
    }
    if (day.isWeekend) {
        return settings.value.high_contrast ? '#dbeafe' : '#eff6ff';
    }
    return null;
};
const dayRowBackgroundStyle = (day) => {
    const tint = dayTintColor(day);
    return tint ? { backgroundColor: tint } : {};
};
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

const toGermanDate = (iso) => {
    if (!iso || iso.length < 10) return iso;
    const [y, m, d] = iso.split("-");
    return `${d}.${m}.${y}`;
};

type DayLike = { withoutFormat: string };
type RoomLike = { id?: number|string; roomId?: number|string };

const cellRefs = ref<Map<string, HTMLElement>>(new Map());

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

// ---------- Progressives Rendern der Tageszeilen ----------
// Das Grid entstand bisher in EINEM Render-Durchgang: bei einem Jahres-Zeitraum
// sind das 365 Zeilen x Raeume an Zellen, die den ersten Paint blockieren — und
// weil die Monats-Requests in onMounted haengen, gingen sie erst danach raus.
// Jetzt kommt zuerst ein kleines Stueck, der Rest waechst in Haeppchen nach:
// im Leerlauf (requestIdleCallback) und sofort, wenn der Nutzer ans untere Ende
// des bereits Gerenderten scrollt.
const INITIAL_DAY_CHUNK = 45;
const DAY_CHUNK = 30;
const renderedDayCount = ref(INITIAL_DAY_CHUNK);
const dayCount = computed(() => ((props.days as any[]) ?? []).length);
const allDaysRendered = computed(() => renderedDayCount.value >= dayCount.value);
const visibleDays = computed(() => {
    const all = (props.days as any[]) ?? [];
    return allDaysRendered.value ? all : all.slice(0, renderedDayCount.value);
});

let dayFillHandle: number | null = null;
let dayFillUsesIdle = false;

function growRenderedDays(by: number) {
    if (allDaysRendered.value) return;
    renderedDayCount.value = Math.min(renderedDayCount.value + by, dayCount.value);
}

function cancelDayFill() {
    if (dayFillHandle === null) return;
    if (dayFillUsesIdle && typeof (window as any).cancelIdleCallback === 'function') {
        (window as any).cancelIdleCallback(dayFillHandle);
    } else {
        window.clearTimeout(dayFillHandle);
    }
    dayFillHandle = null;
}

function scheduleDayFill() {
    if (dayFillHandle !== null || allDaysRendered.value) return;
    const run = () => {
        dayFillHandle = null;
        growRenderedDays(DAY_CHUNK);
        scheduleDayFill();
    };
    if (typeof (window as any).requestIdleCallback === 'function') {
        dayFillUsesIdle = true;
        dayFillHandle = (window as any).requestIdleCallback(run, { timeout: 300 });
    } else {
        dayFillUsesIdle = false;
        dayFillHandle = window.setTimeout(run, 32);
    }
}

// Sprungziele (Datum/Monat) koennen noch ungerendert sein — vorher auffuellen,
// sonst findet der Sprung sein Ziel-Element nicht.
async function ensureDayRendered(dayIso: string) {
    if (!dayIso || allDaysRendered.value) return;
    const all = (props.days as any[]) ?? [];
    const idx = all.findIndex((d: any) => d?.withoutFormat === dayIso);
    if (idx < 0 || idx < renderedDayCount.value) return;
    renderedDayCount.value = Math.min(idx + 1 + DAY_CHUNK, all.length);
    await nextTick();
}

const dayFillSentinel = ref<HTMLElement | null>(null);
let dayFillObserver: IntersectionObserver | null = null;
let dayFillSentinelVisible = false;

// Solange der Marker sichtbar bleibt, Frame fuer Frame nachlegen — sonst haengt
// der Nutzer beim schnellen Scrollen an der Unterkante fest.
function pumpDayFill() {
    if (!dayFillSentinelVisible || allDaysRendered.value) return;
    growRenderedDays(DAY_CHUNK);
    requestAnimationFrame(pumpDayFill);
}

watch(dayFillSentinel, (el) => {
    dayFillObserver?.disconnect();
    dayFillObserver = null;
    dayFillSentinelVisible = false;
    if (!el) return;
    dayFillObserver = new IntersectionObserver((entries) => {
        dayFillSentinelVisible = entries.some(entry => entry.isIntersecting);
        if (dayFillSentinelVisible) pumpDayFill();
    }, { root: null, rootMargin: '1200px 0px', threshold: 0 });
    dayFillObserver.observe(el);
});

// Neuer Zeitraum -> wieder klein anfangen. Bewusst auf die Eckdaten geankert
// statt auf die Array-Identitaet: ein Inertia-Reload schickt `period` neu,
// ohne dass sich der Zeitraum geaendert hat — der Reset wuerde das Grid sonst
// nach jedem Speichern sichtbar zusammenklappen. Die Monats-Sentinels der alten
// Zeilen sind damit hinfaellig: ohne Reset beobachtet der Observer abgehaengte
// Elemente, `monthSentinelSeen` verhindert die Neuanmeldung und der
// Monatsfokus (und damit das Nachladen beim Scrollen) bliebe stehen.
const dayRangeKey = computed(() => {
    const all = (props.days as any[]) ?? [];
    if (!all.length) return '';
    return `${all[0]?.withoutFormat ?? ''}_${all[all.length - 1]?.withoutFormat ?? ''}_${all.length}`;
});

watch(dayRangeKey, () => {
    cancelDayFill();
    renderedDayCount.value = INITIAL_DAY_CHUNK;
    monthObserver?.disconnect();
    monthObserver = null;
    monthSentinelSeen.value.clear();
    scheduleDayFill();
});

function useCellVisibility(options = {}) {
    const { root = null, rootMargin = '1200px', threshold = 0.01 } = options;
    const visibleKeys = shallowRef(new Set<string>());
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
                        if (!visibleKeys.value.has(k)) { visibleKeys.value.add(k); changed = true; }
                    } else {
                        if (visibleKeys.value.has(k)) { visibleKeys.value.delete(k); changed = true; }
                    }
                }
                if (changed) triggerRef(visibleKeys);
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

    return { observe, isVisible, visibleKeys, dispose };
}
const { observe: observeCell, isVisible: isCellVisible, visibleKeys: visibleCellKeys, dispose: disposeCells } = useCellVisibility({
    root: null,
    rootMargin: '1200px',
    threshold: 0.01
});
const cellKey = (day, room) => `${day.withoutFormat}:${(room.roomId ?? room.id)}`;

// Bei "Tage aufklappen" bestimmen ALLE Zellen einer Zeile deren Auto-Höhe.
// Würden Zellen horizontal virtualisiert (mount/unmount je nach X-Scroll),
// spränge die Zeilenhöhe beim Scrollen und der Inhalt verschöbe sich vertikal.
// Deshalb rendert eine Zeile dort alle Raum-Zellen, sobald sie vertikal in
// Viewport-Nähe ist — erkennbar daran, dass mindestens eine ihrer Zellen im
// IntersectionObserver sichtbar ist (die Zellen unter dem Viewport-X-Bereich
// sind das immer). Im Modus mit fixer Zeilenhöhe bleibt die sparsamere
// Zell-Virtualisierung in beiden Achsen aktiv.
const visibleDayKeys = computed(() => {
    const days = new Set<string>();
    for (const key of visibleCellKeys.value) {
        days.add(key.slice(0, key.indexOf(':')));
    }
    return days;
});
const isDayRowVisible = (day) => visibleDayKeys.value.has(day.withoutFormat);
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
            target = { roomId: roomId, roomName: inc?.roomName ?? '', roomColor: inc?.roomColor ?? null, content: {} };
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
        if ((inc?.roomColor ?? null) !== (target.roomColor ?? null)) {
            target.roomColor = inc?.roomColor ?? null;
        }
    }
    // Sort rooms to match the order from the rooms prop (position-based from DB)
    const roomOrder = new Map<number, number>();
    (props.rooms as any[]).forEach((r: any, idx: number) => {
        roomOrder.set(r.id, idx);
    });
    targetRooms.sort((a: any, b: any) => {
        const posA = roomOrder.get(a.roomId) ?? Number.MAX_SAFE_INTEGER;
        const posB = roomOrder.get(b.roomId) ?? Number.MAX_SAFE_INTEGER;
        return posA - posB;
    });

    newCalendarData.value = [...targetRooms];
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
const PREFETCH_RADIUS = 3;

async function ensureAroundInternal(key: string | null, radius = SCROLL_WINDOW_RADIUS) {
    if (!key) return;
    const idx = monthIndexByKey.value.get(key);
    if (idx == null) return;
    const myEpoch = ++currentEpoch;
    const targets = windowKeysAround(idx, radius);
    cancelAllExcept(windowKeysAround(idx, Math.max(radius, KEEP_RADIUS)));
    await Promise.allSettled(targets.map(k => loadMonth(k, myEpoch)));
    // Nur prefetchen, wenn inzwischen kein neuerer Fokus gewonnen hat
    if (myEpoch === currentEpoch) schedulePrefetchAround(idx);
}

// Aeusserer Ring erst im Leerlauf — konkurriert so nicht mit dem Fenster,
// das der Nutzer gerade ansieht.
let prefetchHandle: number | null = null;
function schedulePrefetchAround(idx: number) {
    const run = () => {
        prefetchHandle = null;
        const epoch = currentEpoch;
        windowKeysAround(idx, PREFETCH_RADIUS)
            .filter(k => !loadedMonths.value.has(k) && !loadingMonths.value.has(k))
            .forEach(k => loadMonth(k, epoch));
    };
    if (prefetchHandle !== null) {
        if (typeof (window as any).cancelIdleCallback === 'function') {
            (window as any).cancelIdleCallback(prefetchHandle);
        } else {
            window.clearTimeout(prefetchHandle);
        }
    }
    prefetchHandle = typeof (window as any).requestIdleCallback === 'function'
        ? (window as any).requestIdleCallback(run, { timeout: 2000 })
        : window.setTimeout(run, 600);
}

const ensureAround = debounce(() => {
    requestAnimationFrame(() => ensureAroundInternal(focusedMonthKey.value));
}, 120);

let monthObserver: IntersectionObserver | null = null;
// Beobachtet werden ALLE Tageszeilen, nicht nur die erste je Monat. Bei einem
// Jahres-Zeitraum liegt die erste Zeile eines Monats schnell weit ausserhalb
// von Viewport + rootMargin, der Fokus haette sich dann auf einen Monat
// gestuetzt, dessen Startzeile gerade niemand sieht. Kosten: 365 statt 12
// beobachtete Elemente — neben den ~7.000 Zell-Observern vernachlaessigbar.
const observedSentinels = new WeakSet<Element>();

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
    if (!key || observedSentinels.has(el)) return;
    observedSentinels.add(el);
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
    // Nachbarmonate erst nach dem ersten Bild und nur im Leerlauf
    schedulePrefetchAround(idx);
}


onMounted(async () => {
    await runInitialLoad();
    // Erst die Daten anfordern, dann den Rest des Zeitraums nachrendern.
    scheduleDayFill();

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
    cancelAllExcept([]);
    if (prefetchHandle !== null) {
        if (typeof (window as any).cancelIdleCallback === 'function') {
            (window as any).cancelIdleCallback(prefetchHandle);
        } else {
            window.clearTimeout(prefetchHandle);
        }
        prefetchHandle = null;
    }

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

    // Progressives Rendern stoppen
    cancelDayFill();
    dayFillObserver?.disconnect();
    dayFillObserver = null;
    dayFillSentinelVisible = false;
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
    if (!value) clearCellSelection();
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

const isCellSelected = (day, room) => selectedCells.value.has(cellKey(day, room));
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
const jumpToDayOfMonth = async (day) => {
    // Ziel kann beim progressiven Rendern noch fehlen
    await ensureDayRendered(day);

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

const eventsInCell = (day: any, room: any) =>
    (room.content?.[dayKey(day)]?.events ?? []);

const shiftsInCell = (day: any, room: any) =>
    (room.content?.[dayKey(day)]?.shifts ?? []);

// "dd.mm.yyyy" → "yyyy-mm-dd"
const deKeyToIso = (deKey: string) => {
    const parts = String(deKey ?? '').split('.');
    return parts.length === 3 ? `${parts[2]}-${parts[1]}-${parts[0]}` : '';
};

// Effektive Startzeit ("HH:MM") eines Items an diesem Tag: beginnt es an einem
// Vortag, zählt es ab 00:00 — so mischen sich Termine und Schichten korrekt.
const itemStartTimeOnDay = (item: any, dayIso: string): string => {
    if (item.type === 'shift') {
        const shift = item.data;
        if (shift.startDate && shift.startDate < dayIso) return '00:00';
        const time = String(shift.start ?? '');
        const match = time.match(/(\d{2}:\d{2})/);
        return match ? match[1] : '00:00';
    }
    const start = String(item.data.start ?? ''); // "Y-m-d H:i"
    const datePart = start.slice(0, 10);
    if (datePart && datePart < dayIso) return '00:00';
    const match = start.match(/(\d{2}:\d{2})$/) ?? start.match(/\s(\d{2}:\d{2})/);
    return match ? match[1] : '00:00';
};

// Termine + eigenständige Schichten einer Zelle, gemischt nach Startzeit sortiert
const itemsInCell = (day: any, room: any) => {
    const events = eventsInCell(day, room).map((evt: any) => ({ type: 'event', data: evt }));
    const shifts = shiftsInCell(day, room).map((shift: any) => ({ type: 'shift', data: shift }));
    if (shifts.length === 0) return events;
    const dayIso = day.withoutFormat ?? deKeyToIso(dayKey(day));
    return [...events, ...shifts].sort((a, b) =>
        itemStartTimeOnDay(a, dayIso).localeCompare(itemStartTimeOnDay(b, dayIso))
    );
};

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

<style scoped>
/* Monatsname mittig zur sichtbaren Bildschirmbreite: sticky hält das Label
   beim horizontalen Scrollen durch das breite Grid in der Viewport-Mitte. */
.month-separator-label {
    position: sticky;
    left: 50vw;
    transform: translateX(-50%);
}

.cell {
    overflow: auto;
    scrollbar-color: rgba(156,163,175,0.5) transparent; /* Firefox */
    scrollbar-width: thin;
}
/* WebKit */
.cell::-webkit-scrollbar { width: 6px !important; height: 6px !important; }
.cell::-webkit-scrollbar-thumb { background-color: rgba(156,163,175,0.5); border-radius: 3px; }
.cell::-webkit-scrollbar-thumb:hover { background-color: rgba(107,114,128,0.7); }
.cell::-webkit-scrollbar-track { background-color: transparent; }
</style>
