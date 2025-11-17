<template>
    <div :class="!isInModal ? 'my-10' : ''" class="relative">
        <!-- Loading -->
        <div class="absolute w-full h-full bg-artwork-buttons-context/50 top-0 z-50" v-if="isLoading">
            <div class="h-full flex items-center justify-center text-white">
                {{ $t('Data is currently loaded. Please wait') }}
            </div>
        </div>

        <!-- Permission warning -->
        <div v-if="!hasCreateEventsPermission" class="mb-4 w-full">
            <AlertComponent
                :text="$t('You do not have the permission to plan events without a request')"
                type="warning"
                show-icon icon-size="h-5 w-5"
                classes="!items-center"
            />
        </div>

        <!-- Top bar -->
        <div class="flex items-center justify-between gap-x-4 print:hidden" v-if="!isInModal">
            <div
                class="flex items-center gap-5 sm:gap-6 text-[11px] sm:text-xs text-zinc-600"
                role="list"
            >
                <!-- Last edited -->
                <div class="flex items-center gap-2" role="listitem">
                    <span
                        aria-hidden="true"
                        class="h-4 w-10 rounded-full border-2 border-dashed border-blue-500/70 bg-blue-50/40"
                    ></span>
                                    <span class="uppercase tracking-wide font-medium">
                      {{ $t('Last edited events') }}
                    </span>
                </div>

                <!-- Most recently created -->
                <div class="flex items-center gap-2" role="listitem">
                    <span
                        aria-hidden="true"
                        class="h-4 w-10 rounded-full border-2 border-dashed border-pink-500/70 bg-pink-50/40"
                    ></span>
                                    <span class="uppercase tracking-wide font-medium">
                      {{ $t('Most recently created events') }}
                    </span>
                                </div>

                                <!-- Planned Event -->
                                <div class="flex items-center gap-2" role="listitem">
                    <span
                        aria-hidden="true"
                        class="block h-4 w-1.5 rounded-full bg-gradient-to-b from-blue-400 to-blue-600"
                    ></span>
                                    <span class="uppercase tracking-wide font-medium">
                      {{ $t('Planned Event') }}
                    </span>
                </div>
            </div>

            <div class="flex items-center justify-end gap-x-4 print:hidden">
                <MultiEditSwitch
                    :multi-edit="multiEdit"
                    :room-mode="false"
                    @update:multi-edit="UpdateMultiEditEmits"
                    :disabled="!hasCreateEventsPermission"
                />

                <div class="flex items-center gap-x-2">
                    <PlanningSwitch
                        :planning="isPlanningEvent"
                        @update:planning="isPlanningEvent = $event"
                        :disabled="!hasCreateEventsPermission"
                    />
                </div>

                <FunctionBarFilter
                    :user_filters="usePage().props.user_filters"
                    :personal-filters="usePage().props.personalFilters"
                    :filter-options="usePage().props.filterOptions"
                    :filter-type="'calendar_filter'"
                />

                <ToolTipComponent
                    :icon="IconCircuitCapacitorPolarized"
                    icon-size="size-5"
                    :tooltip-text="$t('Customize column size')"
                    direction="bottom"
                    @click="hasCreateEventsPermission ? showIndividualColumnSizeConfigModal = true : null"
                    :class="!hasCreateEventsPermission ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer'"
                    classes-button="ui-button"
                />
                <ToolTipComponent
                    :icon="IconFileExport"
                    icon-size="size-5"
                    :tooltip-text="$t('Export project list')"
                    direction="bottom"
                    @click="showExportModal = true"
                    classes-button="ui-button"
                />
                <ToolTipComponent
                    :icon="IconCalendarMonth"
                    icon-size="size-5"
                    :tooltip-text="$t('Show project period in calendar')"
                    direction="bottom"
                    @click="useProjectTimePeriodAndRedirect()"
                    classes-button="ui-button"
                />

                <BaseMenu show-sort-icon dots-size="size-5" menu-width="w-72" class="!w-fit ui-button" :disabled="!hasCreateEventsPermission">
                    <MenuItem v-slot="{ active }">
                        <div @click="hasCreateEventsPermission ? updateUserSortId(1) : null"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased', hasCreateEventsPermission ? 'cursor-pointer' : 'cursor-not-allowed opacity-50']">
                            {{ $t('Sort by room') }}
                            <IconCheck class="w-5 h-5" v-if="usePage().props.auth.user.bulk_sort_id === 1"/>
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <div @click="hasCreateEventsPermission ? updateUserSortId(2) : null"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased', hasCreateEventsPermission ? 'cursor-pointer' : 'cursor-not-allowed opacity-50']">
                            {{ $t('Sort by appointment type') }}
                            <IconCheck class="w-5 h-5" v-if="usePage().props.auth.user.bulk_sort_id === 2"/>
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <div @click="hasCreateEventsPermission ? updateUserSortId(3) : null"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased', hasCreateEventsPermission ? 'cursor-pointer' : 'cursor-not-allowed opacity-50']">
                            {{ $t('Sort by day') }}
                            <IconCheck class="w-5 h-5" v-if="usePage().props.auth.user.bulk_sort_id === 3"/>
                        </div>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <div @click="hasCreateEventsPermission ? updateUserSortId(0) : null"
                             :class="[active ? 'bg-artwork-navigation-color/10 text-artwork-buttons-hover' : 'text-secondary', 'group flex items-center justify-between px-4 py-2 text-sm subpixel-antialiased', hasCreateEventsPermission ? 'cursor-pointer' : 'cursor-not-allowed opacity-50']">
                            {{ $t('Reset sorting') }}
                        </div>
                    </MenuItem>
                </BaseMenu>
            </div>
        </div>

        <!-- Header + Events -->
        <div :class="isInModal ? 'overflow-x-auto relative w-full' : 'overflow-x-auto relative w-max'">
            <BulkHeader v-model="timeArray" v-model:showEndDate="showEndDate" :is-in-modal="isInModal" :multi-edit="multiEdit"/>
            <div :class="isInModal ? 'min-h-96 max-h-96 overflow-y-scroll w-max' : ''">
                <div v-if="sortedEvents.length > 0 && showEvents">
                    <!-- Render events by groups -->
                    <div v-for="(group, groupIndex) in getEventGroups()" :key="group.key" class="mb-6">
                        <!-- Group Divider -->
                        <DividerChip
                            v-if="group.label && usePage().props.auth.user.bulk_sort_id !== 0"
                            class="mb-6"
                            variant="brand"
                            :label="group.label"
                        />

                        <!-- Events in this group -->
                        <div v-for="(event, eventIndex) in group.events" :key="event.id ?? `tmp-${event.index || eventIndex}`" class="mb-2">
                            <div :id="eventIndex" class="mx-1">
                                <BulkSingleEvent
                                    :can-edit-component="canEditComponent && hasCreateEventsPermission"
                                    :rooms="rooms"
                                    :event_types="eventTypes"
                                    :time-array="timeArray"
                                    :event="event"
                                    :copy-types="copyTypes"
                                    :index="eventIndex"
                                    :is-in-modal="isInModal"
                                    @open-event-component="onOpenEventComponent"
                                    @edit-event="onOpenEventComponent"
                                    @delete-current-event="deleteCurrentEvent"
                                    @create-copy-by-event-with-data="createCopyByEventWithData"
                                    :event-statuses="eventStatuses"
                                    :multi-edit="multiEdit"
                                    :has-permission="hasCreateEventsPermission"
                                    :last-edit-event-ids="lastEditEventIds"
                                    :show-end-date="showEndDate"
                                />
                            </div>
                        </div>

                        <!-- Add Event Button for this group -->
                        <div v-if="canEditComponent && hasCreateEventsPermission && !multiEdit" class="flex justify-center mt-4 mb-2">
                            <IconCirclePlus
                                @click="addEmptyEventForGroup(group)"
                                class="w-8 h-8 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                                stroke-width="2"
                            />
                        </div>
                    </div>
                </div>

                <div v-else class="flex items-center h-24 print:hidden">
                    <AlertComponent
                        :text="$t('No events found. Click on the plus (+) icon to create an event')"
                        type="info"
                        show-icon icon-size="h-5 w-5"
                        classes="!items-center"
                    />
                    <!-- Add Event Button when no events exist -->
                    <div v-if="canEditComponent && hasCreateEventsPermission && !multiEdit" class="flex justify-center mt-4">
                        <IconCirclePlus
                            @click="addEmptyEvent"
                            class="w-8 h-8 text-artwork-buttons-context cursor-pointer hover:text-artwork-buttons-hover transition-all duration-150 ease-in-out"
                            stroke-width="2"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom actions -->
        <div class="flex items-center justify-end print:hidden" v-if="!multiEdit">
            <div class="flex items-center gap-x-4">
                <div v-if="invalidEvents.length > 0" class="text-artwork-messages-error text-xs">
                    {{ $t('The name is not given for {0} event(s)', [invalidEvents.length]) }}
                </div>
                <BaseUIButton
                    v-if="isInModal"
                    @click="submit"
                    :disabled="!hasCreateEventsPermission"
                    :label="$t('Create')"
                    is-add-button
                />
            </div>
        </div>

        <!-- Multi-Edit Action Bar -->
        <div v-else class="fixed inset-x-0 bottom-3 z-40 print:hidden">
            <div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8">
                <div class="relative">
                    <!-- top fade -->

                    <div
                        class="flex items-center justify-between gap-4
               rounded-2xl border border-zinc-200/80 dark:border-zinc-800/80
               bg-white/80 dark:bg-zinc-900/80 backdrop-blur-xl
               shadow-lg shadow-zinc-900/5
               px-4 sm:px-6 py-3 sm:py-4">
                        <!-- left: label + selected count -->
                        <div class="flex items-center gap-3 min-w-0">
                          <span class="inline-flex items-center rounded-full bg-zinc-100 dark:bg-zinc-800 px-2.5 py-1 text-xs font-medium text-zinc-700 dark:text-zinc-300 ring-1 ring-inset ring-zinc-200 dark:ring-zinc-700">
                            {{ $t('Multi-Edit') }}
                          </span>
                            <span class="text-sm text-zinc-600 dark:text-zinc-300 truncate">
                                {{ getEventIdsWhereSelectedForMultiEdit().length }} {{ $t('selected') }}
                            </span>
                        </div>

                        <!-- right: actions -->
                        <div class="flex items-center gap-2 sm:gap-3">
                            <BaseUIButton :label="$t('Edit')" is-add-button
                                @click="hasCreateEventsPermission ? openMultiEditModal() : null"
                                :disabled="getEventIdsWhereSelectedForMultiEdit().length === 0 || !hasCreateEventsPermission"
                            />
                            <BaseUIButton :label="$t('Delete')" is-delete-button
                                @click="hasCreateEventsPermission ? (showConfirmDeleteModal = true) : null"
                                :disabled="getEventIdsWhereSelectedForMultiEdit().length === 0 || !hasCreateEventsPermission"
                            />
                        </div>
                    </div>
                </div>
            </div>
            <!-- iOS safe area -->
            <div class="pb-[env(safe-area-inset-bottom)]"></div>
        </div>

    </div>

    <!-- Modals -->
    <event-component
        v-if="eventComponentIsVisible && eventToEdit"
        :showHints="$page.props?.can?.show_hints"
        :eventTypes="eventTypes"
        :rooms="rooms"
        :project="project"
        :event="eventToEdit"
        :wantedRoomId="null"
        :isAdmin="hasAdminRole()"
        :roomCollisions="roomCollisions"
        :first_project_calendar_tab_id="first_project_calendar_tab_id"
        :used-in-bulk-component="true"
        @closed="onEventComponentClosed"
        :event-statuses="eventStatuses"
    />
    <export-modal v-if="showExportModal"
                  @close="showExportModal = false"
                  :enums="[
                      exportTabEnums.EXCEL_EVENT_LIST_EXPORT,
                      exportTabEnums.EXCEL_CALENDAR_EXPORT,
                      exportTabEnums.EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT
                  ]"
                  :configuration="getExportModalConfiguration()"/>

    <BulkMultiEditModal
        v-if="showMultiEditModal"
        :event-statuses="eventStatuses"
        :event-types="eventTypes"
        :rooms="rooms"
        :event-ids="eventIdsForMultiEdit"
        @close="showMultiEditModal = false"
    />

    <ConfirmDeleteModal
        v-if="showConfirmDeleteModal"
        @close="showConfirmDeleteModal = false"
        @delete="deleteSelectedEvents"
        :title="$t('Do you really want to delete the selected events?')"
        :description="$t('This action cannot be undone.')"
        @closed="showConfirmDeleteModal = false"
    />
    <IndividualColumnSizeConfigModal
        v-if="showIndividualColumnSizeConfigModal"
        @close="showIndividualColumnSizeConfigModal = false"
    />
</template>

<script setup>
import BulkSingleEvent from "@/Pages/Projects/Components/BulkComponents/BulkSingleEvent.vue";
import BaseButton from "@/Layouts/Components/General/Buttons/BaseButton.vue";
import {
    IconCalendarMonth,
    IconCheck,
    IconCirclePlus,
    IconCircuitCapacitorPolarized,
    IconFileExport
} from "@tabler/icons-vue";
import BulkHeader from "@/Pages/Projects/Components/BulkComponents/BulkHeader.vue";
import {onMounted, ref, watch, provide, computed} from "vue";
import {router, usePage} from "@inertiajs/vue3";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import {MenuItem} from "@headlessui/vue";
import PlanningSwitch from "@/Components/Calendar/Elements/PlanningSwitch.vue";
import AlertComponent from "@/Components/Alerts/AlertComponent.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import EventComponent from "@/Layouts/Components/EventComponent.vue";
import {usePermission} from "@/Composeables/Permission.js";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import ExportModal from "@/Layouts/Components/Export/Modals/ExportModal.vue";
import {useExportTabEnums} from "@/Layouts/Components/Export/Enums/ExportTabEnum.js";
import MultiEditSwitch from "@/Components/Calendar/Elements/MultiEditSwitch.vue";
import BulkMultiEditModal from "@/Pages/Projects/Components/BulkComponents/BulkMultiEditModal.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import IndividualColumnSizeConfigModal from "@/Pages/Projects/Components/BulkComponents/IndividualColumnSizeConfigModal.vue";
import DividerChip from "@/Artwork/Divider/DividerChip.vue";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";
import { useBulkEventsBroadcastUpdater } from '@/Composeables/Listener/useBulkEventsBroadcastUpdater.js';
import FunctionBarFilter from "@/Artwork/Filter/FunctionBarFilter.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import axios from 'axios';

const exportTabEnums = useExportTabEnums();
const {hasAdminRole, can} = usePermission(usePage().props);
const $t = useTranslation();

const props = defineProps({
    project: { type: Object, required: true },
    eventTypes: { type: Array, required: true },
    rooms: { type: Array, required: true },
    isInModal: { type: Boolean, default: false },
    eventsInProject: { type: Array, default: () => [] },
    canEditComponent: { type: Boolean, required: true },
    first_project_calendar_tab_id: { type: Number, required: false },
    eventStatuses: { type: Array, default: () => [] },
    event_properties: { type: Array, default: () => [] },
});

const emits = defineEmits(['closed']);

let showEvents = ref(true);
const hasCreateEventsPermission = ref(can('create events without request') || hasAdminRole());
const roomCollisions = ref([]);
const timeArray = ref(true);

// Persisted user preference for showing End date column in Bulk
const showEndDateStorageKey = computed(() => `bulk_show_end_date_user_${usePage().props.auth.user.id}`);
const showEndDate = ref(localStorage.getItem(showEndDateStorageKey.value) === 'true');

const isPlanningEvent = ref((() => {
    const storedValue = localStorage.getItem(`isPlanningEvent_${props.project.id}`);
    return storedValue !== null ? storedValue === 'true' : (props.project?.state?.is_planning || false);
})());

const invalidEvents = ref([]);
const multiEdit = ref(false);
const eventIdsForMultiEdit = ref([]);
const showMultiEditModal = ref(false);
const showConfirmDeleteModal = ref(false);
const showIndividualColumnSizeConfigModal = ref(false);
const lastUsedCopyCount = ref(1);
const isLoading = ref(true);
const eventComponentIsVisible = ref(false);
const eventToEdit = ref(null);
const showExportModal = ref(false);
const lastEditEventIds = ref(usePage()?.props?.headerObject?.project.lastEditEventIds || []);
const isLoadingBulkData = ref(false);
const loadBulkDataError = ref('');

const copyTypes = ref([
    { id: 1, name: 'Täglich', type: 'daily' },
    { id: 2, name: 'Wöchentlich', type: 'weekly' },
    { id: 3, name: 'Monatlich', type: 'monthly' },
    { id: 4, name: 'am gleichen Tag', type: 'same_day' },
]);

// BESSER: ref statt reactive([]) für zuverlässiges Re-Rendering bei Reassign/Filter
const events = ref([]);

// --- BulkEventsBroadcastUpdater Integration
useBulkEventsBroadcastUpdater(events, {
    onEvent: (event, action) => {
        // add event id if not existing in lastEditEventIds
        if (action === 'updated') {
            lastEditEventIds.value = [];

            // add all event ids in lastEditEventIds where are the same update_at timestamp as the updated event
            const sameUpdatedEvents = events.value.filter(e => e.updated_at === event.updated_at);
            sameUpdatedEvents.forEach(e => {
                if (!lastEditEventIds.value.includes(e.id)) {
                    lastEditEventIds.value.push(e.id);
                }
            });
        }
    }
});

// globally provided
const focusRegistry = ref({ id: null, type: null });
const storeFocus = (id, type = null) => {
    focusRegistry.value.id = id;
    focusRegistry.value.type = type;
};
provide('focusRegistry', focusRegistry);
provide('storeFocusGlobal', storeFocus);
provide('event_properties', props.event_properties);

// --- Helpers
const toISO = (d) => d.toISOString().split('T')[0];
const formatFullDate = (iso) => new Date(iso).toLocaleDateString('de-DE', {
    weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
});

// Group events by current sorting criteria
const getEventGroups = () => {
    const groups = [];
    const sortId = usePage().props.auth.user?.bulk_sort_id;

    if (sortedEvents.value.length === 0) return groups;

    if (sortId === 0 || !sortId) {
        // No sorting - all events in one group
        groups.push({
            key: 'all',
            label: null,
            events: [...sortedEvents.value]
        });
    } else if (sortId === 1) {
        // Group by room
        let currentRoomId = null;
        let currentGroup = null;

        sortedEvents.value.forEach(event => {
            if (event.room?.id !== currentRoomId) {
                if (currentGroup) groups.push(currentGroup);
                currentRoomId = event.room?.id;
                currentGroup = {
                    key: `room_${currentRoomId}`,
                    label: event.room?.name,
                    events: []
                };
            }
            currentGroup.events.push(event);
        });
        if (currentGroup) groups.push(currentGroup);
    } else if (sortId === 2) {
        // Group by event type
        let currentTypeId = null;
        let currentGroup = null;

        sortedEvents.value.forEach(event => {
            if (event.type?.id !== currentTypeId) {
                if (currentGroup) groups.push(currentGroup);
                currentTypeId = event.type?.id;
                currentGroup = {
                    key: `type_${currentTypeId}`,
                    label: event.type?.name,
                    events: []
                };
            }
            currentGroup.events.push(event);
        });
        if (currentGroup) groups.push(currentGroup);
    } else if (sortId === 3) {
        // Group by day
        let currentDay = null;
        let currentGroup = null;

        sortedEvents.value.forEach(event => {
            if (event.day !== currentDay) {
                if (currentGroup) groups.push(currentGroup);
                currentDay = event.day;
                currentGroup = {
                    key: `day_${currentDay}`,
                    label: currentDay ? new Date(currentDay).toLocaleDateString('de-DE', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}) : null,
                    events: []
                };
            }
            currentGroup.events.push(event);
        });
        if (currentGroup) groups.push(currentGroup);
    }

    return groups;
};

// Nur bei Sortierung nach Tag (3) lokal sortieren – sonst Server-Reihenfolge belassen.
const sortedEvents = computed(() => {
    const list = [...events.value];
    if (usePage().props.auth.user?.bulk_sort_id === 3) {
        list.sort((a, b) => {
            if ((a.day || '') === (b.day || '')) {
                const as = a.start_time || '';
                const bs = b.start_time || '';
                if (as === bs) {
                    const ar = a.room?.position ?? 0;
                    const br = b.room?.position ?? 0;
                    return ar - br;
                }
                return as.localeCompare(bs);
            }
            return (a.day || '').localeCompare(b.day || '');
        });
    }
    return list;
});

const getEventIdsWhereSelectedForMultiEdit = () =>
    events.value.filter(e => e.isSelectedForMultiEdit).map(e => e.id);

// --- Actions
const UpdateMultiEditEmits = (value) => { multiEdit.value = value; };

const mapBulkEventToModalEvent = (e) => {
    if (!e || typeof e !== 'object') return null;
    const day = e.day ?? null;
    const endDay = e.end_day ?? day;
    const startTime = e.start_time ?? null;
    const endTime = e.end_time ?? null;
    const hasTimes = Boolean(startTime && endTime);
    const start = day ? `${day}T${hasTimes ? startTime : '00:00'}` : null;
    const end = endDay ? `${endDay}T${hasTimes ? endTime : '23:59'}` : null;

    return {
        id: e.id,
        title: e.name ?? '',
        eventName: e.name ?? '',
        start,
    end,
    allDay: !hasTimes,
        eventType: e.type ?? e.eventType ?? null,
        eventTypeId: e.type?.id ?? e.eventTypeId ?? e.eventType?.id ?? null,
        eventStatus: e.status ?? e.eventStatus ?? null,
        eventStatusId: e.status?.id ?? e.eventStatusId ?? e.eventStatus?.id ?? null,
        roomId: e.room?.id ?? e.roomId ?? null,
        isPlanning: e.is_planning ?? e.isPlanning ?? false,
        project: e.project ?? props.project ?? null,
        description: e.description ?? null,
        created_by: e.created_by ?? e.creator ?? null,
        eventProperties: e.eventProperties ?? e.event_properties ?? [],
    };
};

const onOpenEventComponent = async (payload) => {
    // Resolve the event id first
    const id = (payload && typeof payload === 'object') ? payload.id : payload;
    const fallbackModel = () => {
        if (payload && typeof payload === 'object') {
            return (payload.start || payload.end) ? payload : mapBulkEventToModalEvent(payload);
        }
        const found = events.value.find(e => e.id == id) ?? null; // loose equality to handle string/number
        return found ? ((found.start || found.end) ? found : mapBulkEventToModalEvent(found)) : null;
    };

    try {
        isLoading.value = true;
        if (!id) throw new Error('Missing event id');
        const { data } = await axios.get(route('events.show.json', { event: id }));
        // Laravel JSON Resource may wrap payload under data
        const payloadData = data?.data ?? data;
        if (props.project) payloadData.project = props.project;
        // Fix: ensure multi-day end date is respected when opening modal from bulk list
        try {
            const fb = fallbackModel();
            if (fb?.start && fb?.end) {
                const sd = String(fb.start).slice(0,10);
                const ed = String(fb.end).slice(0,10);
                if (sd && ed && sd !== ed) {
                    payloadData.start = fb.start;
                    payloadData.end = fb.end;
                    payloadData.allDay = fb.allDay;
                }
            }
        } catch { /* ignore */ }
        eventToEdit.value = payloadData;
        eventComponentIsVisible.value = true;
    } catch (e) {
        const model = fallbackModel();
        if (model && props.project) model.project = props.project;
        eventToEdit.value = model;
        eventComponentIsVisible.value = !!model;
    } finally {
        isLoading.value = false;
    }
};

const onEventComponentClosed = () => {
    eventComponentIsVisible.value = false;
    eventToEdit.value = null;
};

const addEmptyEvent = () => {
    //isLoading.value = true;

    events.value.forEach(e => { e.isNew = false; });

    let newDate = new Date();
    if (events.value.length > 0) {
        const last = events.value[events.value.length - 1];
        newDate = new Date(last.day);
        newDate.setDate(newDate.getDate() + 1);
    }

    const base = {
        index: events.value.length + 1,
        status: props.eventStatuses?.find(s => s.default) ?? null,
        type: props.eventTypes?.[0] ?? null,
        name: props.isInModal ? '' : 'Blocker',
        room: props.rooms?.[0] ?? null,
        day: toISO(newDate),
        end_day: toISO(newDate),
        start_time: '',
        end_time: '',
        copy: false,
        copyCount: 1,
        copyType: copyTypes.value[0],
        description: '',
        isNew: true,
        is_planning: isPlanningEvent.value,
    };

    if (props.isInModal) {
        events.value.push(base);
        isLoading.value = false;
        return;
    }

    // Persist, wenn nicht im Modal
    /*router.post(route('event.store.bulk.single', {project: props.project}), { event: base }, {
        preserveState: false,
        preserveScroll: true,
        onFinish: () => { isLoading.value = false; },
    });*/

    axios.post(route('event.store.bulk.single', {project: props.project}), { event: base })
        .finally(() => {
            //isLoading.value = false;
        });
};

const addEmptyEventForGroup = (group) => {
    events.value.forEach(e => { e.isNew = false; });

    let newDate = new Date();
    let baseEvent = null;

    if (group.events.length > 0) {
        // Find the last event in this group (last in the array)
        const lastEventInGroup = group.events[group.events.length - 1];
        baseEvent = lastEventInGroup;
        newDate = new Date(lastEventInGroup.day);

        // When sorting by day, create event on the same day, otherwise add +1 day
        const sortId = usePage().props.auth.user?.bulk_sort_id;
        if (sortId !== 3) {
            newDate.setDate(newDate.getDate() + 1);
        }
    }

    const base = {
        index: events.value.length + 1,
        status: baseEvent?.status || props.eventStatuses?.find(s => s.default) || null,
        type: baseEvent?.type || props.eventTypes?.[0] || null,
        name: props.isInModal ? '' : 'Blocker',
        room: baseEvent?.room || props.rooms?.[0] || null,
        day: toISO(newDate),
        end_day: baseEvent?.end_day || toISO(newDate),
        start_time: baseEvent?.start_time || '',
        end_time: baseEvent?.end_time || '',
        copy: false,
        copyCount: 1,
        copyType: copyTypes.value[0],
        description: baseEvent?.description || '',
        isNew: true,
        is_planning: isPlanningEvent.value,
    };

    if (props.isInModal) {
        events.value.push(base);
        return;
    }

    // Persist, wenn nicht im Modal
    axios.post(route('event.store.bulk.single', {project: props.project}), { event: base })
        .finally(() => {
            //isLoading.value = false;
        });
};

const deleteCurrentEvent = (event) => {
    if (event.id) {
        /*router.delete(route('event.bulk.delete', {event: event.id}), {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isLoading.value = false;
            },
        });*/

        // new in axios
        axios.delete(route('event.bulk.delete', {event: event.id}))
            .finally(() => {
                //isLoading.value = false;
            });
    }
};

const createCopyByEventWithData = (event) => {
    //isLoading.value = true;
    lastUsedCopyCount.value = event.copyCount;

    let cursor = new Date(event.day);
    const createdEvents = [];
    const spanDays = (() => {
        try {
            const d1 = new Date(event.day);
            const d2 = new Date(event.end_day ?? event.day);
            return Math.max(0, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)));
        } catch { return 0; }
    })();

    for (let i = 0; i < event.copyCount; i++) {
        if (event.copyType.type === 'daily') cursor.setDate(cursor.getDate() + 1);
        else if (event.copyType.type === 'weekly') cursor.setDate(cursor.getDate() + 7);
        else if (event.copyType.type === 'monthly') cursor.setMonth(cursor.getMonth() + 1);
        else if (event.copyType.type === 'same_day') cursor = new Date(event.day);

        const endCursor = new Date(cursor);
        endCursor.setDate(endCursor.getDate() + spanDays);

        const clone = {
            index: events.value.length + 1,
            status: event.status,
            type: event.type,
            name: event.name,
            room: event.room,
            day: toISO(cursor),
            end_day: toISO(endCursor),
            start_time: event.start_time,
            end_time: event.end_time,
            copy: false,
            copyCount: 1,
            copyType: copyTypes.value[0],
            description: event.description,
            isNew: true,
            is_planning: isPlanningEvent.value,
        };
        createdEvents.push(clone);
    }

    event.copy = false;
    event.copyCount = 1;
    event.copyType = copyTypes.value[0];

    // Only send request if we have events to create
    if (props.isInModal) {
        // In modal: append copies locally so the user sees them immediately
        events.value.push(...createdEvents);
        return;
    }

    if (createdEvents.length > 0 && !props.isInModal) {
        /*router.post(route('events.bulk.store', {project: props.project}), { events: createdEvents }, {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => { isLoading.value = false; },
        });*/

        axios.post(route('events.bulk.store', {project: props.project}), { events: createdEvents })
            .finally(() => {
                //isLoading.value = false;
            });
    } else {
       // isLoading.value = false;
    }
};

const submit = () => {
    events.value.forEach(e => {
        e.nameError = false;
        if (!e.id && e.is_planning === undefined) {
            e.is_planning = isPlanningEvent.value;
        }
    });

    invalidEvents.value = events.value.filter(e => e?.type?.individual_name && !e.name);

    if (invalidEvents.value.length > 0) {
        invalidEvents.value.forEach(e => e.nameError = true);
        return;
    }
    showEvents.value = false;
    axios.post(route('events.bulk.store', {project: props.project}), { events: events.value })
        .then(() => { emits('closed'); })
        .catch((e) => {
            console.error('Bulk create failed', e);
            showEvents.value = true;
        });
};

const updateUserSortId = (id) => {
    isLoading.value = true;
    router.patch(
        route('user.update_bulk_sort_id', {user: usePage().props.auth.user.id}),
        { bulk_sort_id: id },
        {
            preserveScroll: true,
            preserveState: false,
            onFinish: () => { isLoading.value = false; }
        }
    );
};

const deleteSelectedEvents = () => {
    isLoading.value = true;
    const selectedIds = getEventIdsWhereSelectedForMultiEdit();

    router.delete(route('event.bulk.multi-edit.delete'), {
        data: { eventIds: selectedIds },
        preserveScroll: true,
        preserveState: false,
        onFinish: () => { isLoading.value = false; }
    });

    // FIX: wirklich lokal entfernen
    events.value = events.value.filter(e => !e.isSelectedForMultiEdit);
};

const openMultiEditModal = () => {
    eventIdsForMultiEdit.value = getEventIdsWhereSelectedForMultiEdit();
    showMultiEditModal.value = true;
};

const getExportModalConfiguration = () => {
    const cfg = {};
    cfg[exportTabEnums.EXCEL_EVENT_LIST_EXPORT] = {
        show_artists: usePage().props.createSettings?.show_artists,
        project: props.project,
    };
    cfg[exportTabEnums.EXCEL_CALENDAR_EXPORT] = { project: props.project };
    return cfg;
};

const useProjectTimePeriodAndRedirect = () => {
    router.patch(
        route('user.calendar_settings.toggle_calendar_settings_use_project_period'),
        { use_project_time_period: true, project_id: props.project.id }
    );
};

// Lifecycle
async function fetchBulkEditData() {
    const projectId = props.project?.id;
    if (!projectId) {
        return;
    }

    isLoadingBulkData.value = true;
    loadBulkDataError.value = '';

    try {
        const { data } = await axios.get(
            route('projects.tabs.bulk-edit', { project: projectId })
        );

        if (data?.events && Array.isArray(data.events)) {
            events.value = [];
            data.events.forEach(event => {
                events.value.push({
                    id: event.id,
                    project_id: event.project_id || projectId,
                    type: props.eventTypes.find(type => type.id === event.event_type_id),
                    status: props.eventStatuses.find(status => status.id === event.event_status_id),
                    name: event.eventName || event.name,
                    room: props.rooms.find(room => room.id === event.room_id),
                    day: event.event_date_without_time?.start_clear || event.day,
                    end_day: event.event_date_without_time?.end_clear || event.end_day,
                    start_time: !event.allDay ? (event.start_time_without_day || event.start_time || '') : '',
                    end_time: !event.allDay ? (event.end_time_without_day || event.end_time || '') : '',
                    copy: false,
                    copyCount: 1,
                    copyType: copyTypes.value[0],
                    index: events.value.length + 1,
                    description: event.description,
                    isNew: false,
                    is_planning: event.is_planning
                });
            });
        }

        if (Array.isArray(data?.lastEditEventIds)) {
            lastEditEventIds.value = data.lastEditEventIds;
        }

        // Update Inertia shared props if available
        if (data?.user_filters) {
            usePage().props.user_filters = data.user_filters;
        }
        if (data?.personalFilters) {
            usePage().props.personalFilters = data.personalFilters;
        }
        if (data?.filterOptions) {
            usePage().props.filterOptions = data.filterOptions;
        }
    } catch (error) {
        console.error(error);
        loadBulkDataError.value = 'Unable to load bulk edit data.';
    } finally {
        isLoadingBulkData.value = false;
    }
}

onMounted(async () => {
    // persist showEndDate changes
    watch(showEndDate, (v) => {
        localStorage.setItem(showEndDateStorageKey.value, v ? 'true' : 'false');
    });

    // Try to fetch data first, fallback to props
    if (props.eventsInProject.length === 0) {
        await fetchBulkEditData();
    }

    if (props.eventsInProject.length > 0) {
        // FIX: kein splice(0,1)
        props.eventsInProject.forEach(event => {
            events.value.push({
                id: event.id,
                project_id: event.projectId,
                type: props.eventTypes.find(type => type.id === event.event_type_id),
                status: props.eventStatuses.find(status => status.id === event.event_status_id),
                name: event.eventName,
                room: props.rooms.find(room => room.id === event.room_id),
                day: event.event_date_without_time.start_clear,
                end_day: event.event_date_without_time.end_clear ?? event.event_date_without_time.end_clear,
                start_time: !event.allDay ? (event.start_time_without_day || '') : '',
                end_time: !event.allDay ? (event.end_time_without_day || '') : '',
                copy: false,
                copyCount: 1,
                copyType: copyTypes.value[0],
                index: events.value.length + 1,
                description: event.description,
                isNew: false,
                is_planning: event.is_planning
            });
        });

        // Zuletzt kopierte als "neu" markieren
        if (events.value.length > 0) {
            events.value.forEach(e => e.isNew = false);
            const copyCount = lastUsedCopyCount.value;
            for (let i = 0; i < copyCount; i++) {
                const idx = events.value.length - 1 - i;
                if (idx >= 0) events.value[idx].isNew = true;
            }
        }
    }

    // ursprüngliches Verhalten: initial sortieren nur bei Tag
    if (usePage().props.auth.user.bulk_sort_id === 3) {
        // sortedEvents kümmert sich darum – kein Mutieren von events nötig
    }

    if (props.isInModal && events.value.length === 0) {
        addEmptyEvent();
    }

    isLoading.value = false;
});

// reactive fixes
watch(() => events.value, (newEvents) => {
    newEvents.forEach(e => {
        if (e.name) {
            invalidEvents.value = invalidEvents.value.filter(inv => inv !== e);
        }
    });
}, {deep: true});

watch(isPlanningEvent, (newValue) => {
    localStorage.setItem(`isPlanningEvent_${props.project.id}`, newValue.toString());
});
</script>
