<template>
    <div ref="rowRootEl" class="print:w-full group w-full">
        <!-- Row-Wrapper mit modernem Card-Look + kontextabhängigen Outlines -->
        <div class="flex items-center gap-4 bg-white/70 backdrop-blur transition px-3 py-2 rounded-lg"
            :class="[ event?.isNew ? 'outline-2 outline-pink-400/60 outline-dashed' : '',
                lastEditEventIds.includes(event.id)
                  ? 'outline-2 outline-accent-600 outline-dashed' : '',
                (event.isSelectedForMultiEdit && effectiveMultiEdit) ? 'ring-2 ring-success-border' : ''
              ]"
        >
            <div
                v-if="event.is_planning"
                class="absolute left-0 top-1 bottom-1 w-1.5 rounded-full bg-gradient-to-b from-accent-500 to-accent-600"
                aria-hidden="true"
            />
            <div
                v-if="event.isSelectedForMultiEdit && effectiveMultiEdit"
                class="absolute inset-0 bg-success pointer-events-none"
                aria-hidden="true"
            />

            <!-- Checkbox bei Multi-Edit -->
            <div class="flex items-center justify-center pr-2 pl-1" v-if="effectiveMultiEdit">
                <div class="flex gap-3">
                    <div class="flex h-6 shrink-0 items-center">
                        <div class="group grid size-4 grid-cols-1" :class="event.isSelectedForMultiEdit ? '' : ''" >
                            <input id="comments"  v-model="event.isSelectedForMultiEdit"
                                   aria-describedby="candidates-description"
                                   name="candidates"
                                   type="checkbox"
                                   :id="event.id"
                                   :disabled="!hasPermission" class="col-start-1 row-start-1 appearance-none rounded-sm border border-border bg-white checked:border-accent-600 checked:bg-accent-600 indeterminate:border-accent-600 indeterminate:bg-accent-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-accent-600 disabled:border-border disabled:bg-surface-sunken disabled:checked:bg-surface-sunken dark:border-white/10 dark:bg-white/5 dark:checked:border-accent-600 dark:checked:bg-accent-600 dark:indeterminate:border-accent-600 dark:indeterminate:bg-accent-600 dark:focus-visible:outline-accent-600 dark:disabled:border-white/5 dark:disabled:bg-white/10 dark:disabled:checked:bg-white/10 forced-colors:appearance-auto" />
                            <svg class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white group-has-disabled:stroke-text-subtle dark:group-has-disabled:stroke-white/25" viewBox="0 0 14 14" fill="none">
                                <path class="opacity-0 group-has-checked:opacity-100" d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                <path class="opacity-0 group-has-indeterminate:opacity-100" d="M3 7H11" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div :style="getColumnSize(1)" v-if="usePage().props.event_status_module">
                <ArtworkBaseListbox
                    v-model="event.status"
                    :items="eventStatuses"
                    :search-keys="['name','id']"
                    :placeholder="$t('Please select a Room')"
                    :disabled="canEditComponent === false || !hasPermission"
                    @update:model-value="updateEventInDatabase"
                    :show-color-indicator="true"
                    color-property="color"
                />
            </div>

            <!-- Type -->
            <div :style="getColumnSize(2)">
                <!--<BaseCombobox
                    v-model="event.type"
                    :items="sortedEventTypes"
                    :return-object="true"
                    @update:model-value="updateEventInDatabase"
                    by="id"
                    option-label="name"
                    option-key="id"
                    :placeholder="$t('Please select a Event Type')"
                    :search-fields="['name']"
                    coerce="number"
                />-->

                <ArtworkBaseListbox
                    v-model="event.type"
                    :items="sortedEventTypes"
                    :search-keys="['name','id']"
                    :disabled="canEditComponent === false || !hasPermission"
                    @update:model-value="onTypeChange"
                    :show-color-indicator="true"
                    color-property="hex_code"
                />
            </div>

            <!-- Name -->
            <div :style="getColumnSize(3)">
                <BaseInput
                    v-model="event.name"
                    type="text"
                    :id="'name-' + index"
                    :class="event.type?.individual_name && !event.name ? 'border-danger' : ''"
                    label="Name"
                    @mousedown="storeFocus('name-' + index)"
                    @focusout="updateEventInDatabase"
                    :disabled="canEditComponent === false || !hasPermission"
                />
                <div v-if="event.nameError && !event.name" class="text-xs mt-1 text-artwork-error">
                    {{ $t('Event name is mandatory') }}
                </div>
            </div>

            <!-- Room -->
            <div :style="getColumnSize(4)">
                <ArtworkBaseListbox
                    v-model="event.room"
                    :items="sortedRooms"
                    :search-keys="['name','id']"
                    :placeholder="$t('Please select a Room')"
                    :disabled="canEditComponent === false || !hasPermission"
                    @update:model-value="onRoomChange"
                />
                <!--<BaseCombobox
                    v-model="event.room"
                    :items="sortedRooms"
                    :return-object="true"
                    @update:model-value="updateEventInDatabase"
                    by="id"
                    option-label="name"
                    option-key="id"
                    :placeholder="$t('Please select a Room')"
                    :search-fields="['name']"
                    coerce="number"
                />-->

            </div>

            <!-- Start Day -->
            <div class="print:col-span-2" :style="getColumnSize(5)">
                <div class="relative flex items-center gap-1">
                    <BaseInput
                        v-model="draftStartDate"
                        type="date"
                        :id="'day-' + index"
                        :label="$t('Start date') + ' ' + dayString"
                        :disabled="canEditComponent === false || !hasPermission"
                        @mousedown="storeFocus('day-' + index)"
                        @focusout="onStartDateFocusOut"
                        class="min-w-0 flex-1"
                    />

                </div>
            </div>

            <!-- Start time -->
            <div class="col-span-1" :style="getColumnSize(6)">
                <div class="flex items-center gap-1" >
                    <ToolTipComponent
                        v-if="event.is_series || event.series_id"
                        :icon="IconRepeat"
                        icon-size="size-4"
                        :tooltip-text="$t('Recurring event')"
                        direction="bottom"
                        class="shrink-0"
                    />
                    <BaseInput
                        v-model="draftStartTime"
                        v-if="timeArray"
                        type="time"
                        :id="'start-time-' + index"
                        :label="$t('Start time')"
                        class="print:border-0 min-w-0 flex-1"
                        :disabled="canEditComponent === false || !hasPermission"
                        @mousedown="storeFocus('start-time-' + index)"
                        @focusout="onStartTimeFocusOut"
                    />
                </div>
            </div>

            <!-- End Day (optional) -->
            <div v-if="showEndDate" class="print:col-span-2" :style="getColumnSize(5)">
                <div class="relative">
                    <BaseInput
                        v-model="event.end_day"
                        type="date"
                        :id="'end-day-' + index"
                        :label="$t('End date')"
                        :disabled="canEditComponent === false || !hasPermission"
                        @mousedown="storeFocus('end-day-' + index)"
                        @focusout="updateEventInDatabase"
                    />
                </div>
            </div>

            <!-- End time -->
            <div class="col-span-1" :style="getColumnSize(6)">
                <div class="flex items-center" v-if="timeArray">
                    <BaseInput
                        v-model="draftEndTime"
                        type="time"
                        :id="'end_time-' + index"
                        :label="$t('End time')"
                        class="print:border-0"
                        :disabled="canEditComponent === false || !hasPermission"
                        @focusout="onEndTimeFocusOut"
                        @mousedown="storeFocus('end_time-' + index)"
                    />
                </div>
            </div>

            <!-- Actions -->
            <div
                v-if="canEditComponent && hasPermission"
                class="flex items-center col-span-1 print:hidden"
            >
                <div class="flex items-center gap-x-3">
                    <ToolTipComponent
                        :icon="IconNote"
                        v-if="!isInModal"
                        :tooltip-text="$t('Edit the description')"
                        stroke="1.5"
                        @click="openNoteModal = true"
                        icon-size="size-5"
                        :classes-button="(event?.description && event.description.toString().trim().length > 0) ? 'ui-button !border-black border-2' : 'ui-button'"
                    />
                    <ToolTipComponent
                        v-if="event.start_time && event.end_time && !event.copy && !isInModal"
                        :tooltip-text="$t('Set the event to all-day')"
                        icon="IconClock24"
                        icon-size="size-5"
                        stroke="1.5"
                        @click="removeTime"
                        classes-button="ui-button"
                    />

                    <!-- Copy Menu -->
                    <BaseMenu show-custom-icon dots-color="!text-artwork-buttons-context" classes-button="ui-button" stroke-width="1.5" dots-size="size-5" classes="mr-3"
                              :icon="IconCopy" translation-key="Copy" menu-width="w-fit" white-menu-background>
                        <div class="flex items-center gap-x-2 p-3">
                            <IconCirclePlus class="w-6 h-6 min-w-6 min-h-6 text-artwork-buttons-context" stroke-width="2" />
                            <BaseInput
                                type="number"
                                label="Anzahl"
                                v-model="event.copyCount"
                                min="1"
                                minlength="1"
                                max="1000"
                                id="amount"
                            />
                            <Listbox as="div" class="relative" v-model="event.copyType" id="room">
                                <ListboxButton class="menu-button rounded-lg">
                                    <div class="flex-grow flex text-left xsDark !w-12 truncate">
                                        {{ event.copyType?.name }}
                                    </div>
                                    <IconChevronDown stroke-width="1.5" class="h-5 w-5 text-primary" aria-hidden="true" />
                                </ListboxButton>
                                <ListboxOptions
                                    class="w-44 rounded-xl border border-border-subtle bg-white backdrop-blur max-h-32 overflow-y-auto text-sm absolute z-30 shadow-lg">
                                    <ListboxOption
                                        v-for="copyType in copyTypes"
                                        :key="copyType.name"
                                        :value="copyType"
                                        v-slot="{ selected }"
                                        class="hover:bg-accent-700 text-secondary cursor-pointer px-3 py-2 flex justify-between">
                                        <div :class="[selected ? 'text-artwork-buttons-create' : 'text-text', 'flex']">
                                            {{ copyType.name }}
                                        </div>
                                        <IconCheck stroke-width="1.5" v-if="selected" class="h-5 w-5 text-success" aria-hidden="true" />
                                    </ListboxOption>
                                </ListboxOptions>
                            </Listbox>
                            <IconCircleCheckFilled
                                @click="createCopyByEventWithData(event)"
                                class="w-8 h-8 min-w-6 min-h-6 text-artwork-buttons-create cursor-pointer hover:brightness-110 transition"
                                stroke-width="2"
                            />
                        </div>
                    </BaseMenu>

                    <!-- Edit/Delete Menu -->
                    <BaseMenu has-no-offset white-menu-background menu-width="!w-fit" v-if="!isInModal">
                        <BaseMenuItem white-menu-background :icon="IconEdit" title="Edit" @click="$emit('editEvent', event)" />
                        <BaseMenuItem
                            v-if="(index > 0 && !event.copy) || !isInModal"
                            white-menu-background
                            :icon="IconTrash"
                            title="Put in the trash"
                            @click="openDeleteEventConfirmModal"
                        />
                        <BaseMenuItem
                            v-if="(event.is_series || event.series_id) && hasAdminRole()"
                            white-menu-background
                            :icon="IconTrash"
                            title="Delete all series events"
                            @click="showDeleteSeriesModal = true"
                        />
                        <BaseMenuItem
                            v-if="(event.is_series || event.series_id) && hasAdminRole()"
                            white-menu-background
                            :icon="IconEdit"
                            title="Edit all series events"
                            @click="showEditSeriesModal = true"
                        />
                        <BaseMenuItem
                            v-if="event.id && (can('create events without request') || hasAdminRole())"
                            white-menu-background
                            :icon="IconDeviceFloppy"
                            title="Save timeline as preset"
                            @click="openSaveTimelinePresetModal"
                        />
                        <BaseMenuItem
                            v-if="event.id"
                            white-menu-background
                            :icon="IconFileImport"
                            title="Import timeline preset"
                            @click="showSearchTimelinePresetModal = true"
                        />
                    </BaseMenu>
                </div>
            </div>
        </div>

        <!-- Inline description row -->
        <div
            v-if="showDescriptionInBulk"
            class="border-t border-b border-dashed border-border border-l-2 border-l-zinc-300 ml-6 bg-surface-sunken rounded-b-lg px-3 py-1.5"
        >
            <div v-if="!editingDescription" @click="startEditDescription"
                 class="min-h-[24px] flex items-center"
                 :class="canEditRow ? 'cursor-pointer' : 'cursor-default'">
                <template v-if="event.description && event.description.toString().trim().length > 0">
                    <span class="text-sm text-text-muted whitespace-pre-line break-words">{{ event.description }}</span>
                </template>
                <template v-else-if="canEditRow">
                    <IconNote class="size-4 text-text-subtle mr-1.5" stroke-width="1.5" />
                    <span class="text-sm text-text-subtle italic">{{ $t('Add description') }}</span>
                </template>
            </div>
            <div v-else>
                <textarea
                    ref="descriptionTextarea"
                    v-model="draftDescription"
                    @focusout="saveDescription"
                    @keydown.enter.ctrl="saveDescription"
                    maxlength="250"
                    rows="2"
                    class="w-full border border-artwork-buttons-context/30 rounded-lg text-sm px-2 py-1 resize-none focus:outline-none focus:ring-1 focus:ring-artwork-buttons-context/50"
                />
            </div>
        </div>

        <!-- Delete confirmation -->
        <confirmation-component
            v-if="showDeleteEventConfirmModal"
            :confirm="$t('Delete')"
            :titel="$t('Delete event')"
            :description="$t('Are you sure you want to put the selected appointments in the recycle bin? All sub-events will also be deleted.')"
            @closed="onCloseDeleteEventConfirmModal"
        />

        <!-- Notes Modal -->
        <AddEditEventNoteModal :event="event" v-if="openNoteModal" @close="openNoteModal = false" />

        <!-- Delete Series Events Modal -->
        <ConfirmDeleteModal
            v-if="showDeleteSeriesModal"
            :title="$t('Delete all series events')"
            :description="$t('Do you really want to delete all events of this series?')"
            @closed="showDeleteSeriesModal = false"
            @delete="confirmDeleteSeriesEvents"
        />

        <!-- Edit Series Events Modal -->
        <EditSeriesEventsModal
            v-if="showEditSeriesModal"
            :event="event"
            :rooms="rooms"
            @close="showEditSeriesModal = false"
        />

        <CreateTimelinePresetFormEvent
            v-if="showCreateTimelinePresetModal"
            :event="event"
            @close="showCreateTimelinePresetModal = false"
        />

        <SearchTimelinePresetModal
            v-if="showSearchTimelinePresetModal"
            :event="event"
            @close="showSearchTimelinePresetModal = false"
        />
    </div>
</template>


<script setup>
import {
    IconCheck,
    IconChevronDown,
    IconCircleCheckFilled, IconCopy, IconDeviceFloppy, IconEdit, IconFileImport, IconNote,
    IconCirclePlus, IconRepeat, IconTrash,
} from "@tabler/icons-vue";
import {
    Listbox,
    ListboxButton,
    ListboxOption,
    ListboxOptions,
} from "@headlessui/vue";
import {router, usePage} from "@inertiajs/vue3";
import ToolTipDefault from "@/Components/ToolTips/ToolTipDefault.vue";
import ConfirmationComponent from "@/Layouts/Components/ConfirmationComponent.vue";
import {computed, defineAsyncComponent, inject, nextTick, onMounted, ref, watch} from "vue";
import ToolTipComponent from "@/Components/ToolTips/ToolTipComponent.vue";
import AddEditEventNoteModal from "@/Pages/Projects/Components/BulkComponents/AddEditEventNoteModal.vue";
import BaseMenu from "@/Components/Menu/BaseMenu.vue";
import BaseMenuItem from "@/Components/Menu/BaseMenuItem.vue";
import {Float} from "@headlessui-float/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseCombobox from "@/Artwork/Inputs/BaseCombobox.vue";
import axios from "axios";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import ConfirmDeleteModal from "@/Layouts/Components/ConfirmDeleteModal.vue";
import EditSeriesEventsModal from "@/Components/Calendar/Elements/Events/EditSeriesEventsModal.vue";
import {usePermission} from "@/Composeables/Permission.js";

const CreateTimelinePresetFormEvent = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/CreateTimelinePresetFormEvent.vue'),
    delay: 200,
    timeout: 5000
})

const SearchTimelinePresetModal = defineAsyncComponent({
    loader: () => import('@/Pages/Projects/Components/TimelineComponents/SearchTimelinePresetModal.vue'),
    delay: 200,
    timeout: 5000
})

const focusRegistry  = inject('focusRegistry');      // { id, type }
const storeFocus     = inject('storeFocusGlobal');
// B: Multi-Edit-Status zentral injizieren (siehe BulkBody provide('bulkMultiEdit', ...)).
// Fallback auf die Prop, falls die Komponente ohne Provider verwendet wird.
const injectedMultiEdit = inject('bulkMultiEdit', null);
const {hasAdminRole, can} = usePermission(usePage().props);

const props = defineProps({
    event: { type: Object, required: true },
    timeArray: { type: Boolean, required: true },
    event_types: { type: Object, required: true },
    rooms: { type: Object, required: true },
    copyTypes: { type: Array, required: true },
    index: { type: Number, required: true },
    isInModal: { type: Boolean, required: false, default: false },
    canEditComponent: { type: Boolean, required: true },
    eventStatuses: { type: Object, required: true },
    multiEdit: { type: Boolean, required: false, default: false },
    hasPermission: { type: Boolean, required: false, default: true },
    lastEditEventIds: { type: Array, required: false, default: () => [] },
    showEndDate: { type: Boolean, required: false, default: false },
});

const emit = defineEmits(['deleteCurrentEvent', 'createCopyByEventWithData', 'openEventComponent', 'editEvent']);
const openEventComponent = (payload) => emit('openEventComponent', payload);

// B: Bevorzugt den zentral bereitgestellten Multi-Edit-Status, sonst die Prop.
const effectiveMultiEdit = computed(() => injectedMultiEdit ? injectedMultiEdit.value : props.multiEdit);

// Sammel-Gate für alle Bearbeitungen einer Zeile: Tab-Schreibrecht UND Event-Berechtigung.
const canEditRow = computed(() => props.canEditComponent !== false && props.hasPermission);

const showMenu = ref(false);
const dayString = ref(null);
const openNoteModal = ref(false);
const event_properties = inject('event_properties');
const showDeleteEventConfirmModal = ref(false);
const showDeleteSeriesModal = ref(false);
const showEditSeriesModal = ref(false);
const showCreateTimelinePresetModal = ref(false);
const showSearchTimelinePresetModal = ref(false);

// Inline description
const showDescriptionInBulk = inject('showDescriptionInBulk', ref(false));
const editingDescription = ref(false);
const draftDescription = ref(props.event.description || '');
const descriptionTextarea = ref(null);

const startEditDescription = () => {
    if (!canEditRow.value) return;
    draftDescription.value = props.event.description || '';
    editingDescription.value = true;
    nextTick(() => {
        descriptionTextarea.value?.focus();
    });
};

const saveDescription = async () => {
    if (draftDescription.value !== (props.event.description || '')) {
        if (props.event.id) {
            await axios.patch(route('event.update.description', props.event.id), {
                description: draftDescription.value
            });
        }
        props.event.description = draftDescription.value;
    }
    editingDescription.value = false;
};

const openSaveTimelinePresetModal = async () => {
    try {
        const response = await axios.get(route('events.timelines', {event: props.event.id}));
        const timelines = response.data.timelines || [];
        if (timelines.length > 0) {
            showCreateTimelinePresetModal.value = true;
        }
    } catch (error) {
        console.error('Error checking timelines:', error);
    }
};

// Local draft state for start date to prevent immediate re-sorting while typing
const draftStartDate = ref(props.event.day);

// Local draft state for times to prevent immediate re-sorting while typing
const draftStartTime = ref(props.event.start_time || '');
const draftEndTime = ref(props.event.end_time || '');

// Wurzel-Element der Zeile, um beim focusout zu erkennen, ob der Fokus in ein
// anderes Feld derselben Event-Zeile wandert.
const rowRootEl = ref(null);

// True, wenn der Fokus beim focusout in ein anderes Eingabefeld derselben Zeile
// wandert (Feld-zu-Feld-Wechsel). Dann wird die Speicherung/das Autofill aufgeschoben,
// damit kein Patch/Re-Sort/Autofill den Cursor aus dem gerade angeklickten Feld reißt.
// Klicks auf Buttons/Menüs zählen NICHT – die sollen ganz normal speichern.
const focusStaysInRow = (e) => {
    const rt = e?.relatedTarget;
    if (!rt || !rowRootEl.value || !rowRootEl.value.contains(rt)) return false;
    return rt.tagName === 'INPUT' || rt.tagName === 'SELECT' || rt.tagName === 'TEXTAREA';
};

const parseISODateToUTCMidnight = (iso) => {
    if (!iso) return null;
    const s = String(iso).slice(0, 10);
    const [y, m, d] = s.split('-').map(Number);
    if (!y || !m || !d) return null;
    return new Date(Date.UTC(y, m - 1, d));
};

const formatUTCDateToISO = (date) => {
    const y = date.getUTCFullYear();
    const m = String(date.getUTCMonth() + 1).padStart(2, '0');
    const d = String(date.getUTCDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

const addDaysISO = (iso, days) => {
    const base = parseISODateToUTCMidnight(iso);
    if (!base) return iso;
    base.setUTCDate(base.getUTCDate() + Number(days || 0));
    return formatUTCDateToISO(base);
};

const diffDaysISO = (fromISO, toISO) => {
    const from = parseISODateToUTCMidnight(fromISO);
    const to = parseISODateToUTCMidnight(toISO);
    if (!from || !to) return 0;
    const ms = to.getTime() - from.getTime();
    return Math.round(ms / (1000 * 60 * 60 * 24));
};

const getColumnSize = (column) => ({
    minWidth: usePage().props.auth.user.bulk_column_size[column] + 'px',
    width: usePage().props.auth.user.bulk_column_size[column] + 'px',
    maxWidth: usePage().props.auth.user.bulk_column_size[column] + 'px'
});
const getColumnTextSize = (column) => ({
    minWidth: parseInt(usePage().props.auth.user.bulk_column_size[column]) - 50 + 'px',
    width: parseInt(usePage().props.auth.user.bulk_column_size[column]) - 50 + 'px',
    maxWidth: parseInt(usePage().props.auth.user.bulk_column_size[column]) - 50 + 'px'
});

const createCopyByEventWithData = (event) => emit('createCopyByEventWithData', event);
const openDeleteEventConfirmModal = () => showDeleteEventConfirmModal.value = true;
const onCloseDeleteEventConfirmModal = (closedOnPurpose) => { if (closedOnPurpose) emit('deleteCurrentEvent', props.event); showDeleteEventConfirmModal.value = false; };

const confirmDeleteSeriesEvents = () => {
    router.delete(route('events.series.delete', props.event.id), {
        preserveScroll: true,
        preserveState: false,
        onFinish: () => {
            showDeleteSeriesModal.value = false;
            window.location.reload();
        }
    });
};

// helpers for snapshot comparison
const getComparableEvent = (ev) => ({
    id: ev.id,
    name: ev.name ?? null,
    typeId: ev.type?.id ?? null,
    roomId: ev.room?.id ?? null,
    day: ev.day ?? null,
    end_day: ev.end_day ?? null,
    start_time: ev.start_time ?? null,
    end_time: ev.end_time ?? null,
    is_planning: ev.is_planning ?? false,
    statusId: ev.status?.id ?? null,
});
const isEqual = (a, b) => JSON.stringify(a) === JSON.stringify(b);

const isUpdating = ref(false);

const updateEventInDatabase = async () => {
    await nextTick(); // v-model Werte sicherstellen

    // Autofill direkt auf props.event anwenden
    if (props.event.start_time && !props.event.end_time) {
        const s = new Date(`01/01/2000 ${props.event.start_time}`);
        s.setMinutes(s.getMinutes() + 30);
        props.event.end_time = s.toTimeString().slice(0,5);
    }
    if (!props.event.start_time && props.event.end_time) {
        const e = new Date(`01/01/2000 ${props.event.end_time}`);
        e.setMinutes(e.getMinutes() - 30);
        props.event.start_time = e.toTimeString().slice(0,5);
    }

    // Validierung
    if (props.event.type?.individual_name && !props.event.name) {
        props.event.nameError = true;
        return;
    }
    props.event.nameError = false;

    // Snapshot-Vergleich auf Basis der aktuellen Props
    if (!window.__bulkEventSnapshots) window.__bulkEventSnapshots = {};
    const snapshotKey = `event-snapshot-${props.event.id}`;
    const currentComparable = getComparableEvent(props.event);
    const lastComparable = window.__bulkEventSnapshots[snapshotKey] ?? null;

    // Wenn nichts geändert wurde, abbrechen
    if (lastComparable && isEqual(currentComparable, lastComparable)) {
        return;
    }

    if (!props.event.id || isUpdating.value) return;
    isUpdating.value = true;

    try {
        // Payload normalisieren
        const payload = JSON.parse(JSON.stringify(props.event));
        if (payload.room && typeof payload.room === 'object' && payload.room.id) payload.room = { id: payload.room.id };
        if (payload.type && typeof payload.type === 'object' && payload.type.id) payload.type = { id: payload.type.id };
        if (payload.status && typeof payload.status === 'object' && payload.status.id) payload.status = { id: payload.status.id };

        await axios.patch(route('event.update.single.bulk', { event: props.event.id }), { data: payload });

        // Snapshot nach erfolgreichem Patch aktualisieren
        window.__bulkEventSnapshots[snapshotKey] = getComparableEvent(props.event);
    } catch (err) {
        console.error('bulk:patch-failed', props.event.id, err);
        await nextTick();
        setTimeout(() => {
            const { id, type } = focusRegistry || {};
            if (id) {
                const field = document.getElementById(id);
                if (field) type === 'listbox' ? field.click() : field.focus();
            }
        }, 300);
    } finally {
        isUpdating.value = false;
    }
};


const removeTime = () => {
    props.event.start_time = null;
    props.event.end_time = null;
    draftStartTime.value = '';
    draftEndTime.value = '';
    updateEventInDatabase();
};

const onStartDateFocusOut = async () => {
    // Commit draft to actual event object only on focusout
    const oldStart = props.event.day;
    const newStart = draftStartDate.value;
    const deltaDays = diffDaysISO(oldStart, newStart);

    // Keep the existing duration by shifting end_day by the same delta.
    // If end_day is missing, treat it as equal to the old start day.
    const oldEnd = props.event.end_day || oldStart;
    const newEndDay = addDaysISO(oldEnd, deltaDays);

    // Prepare payload BEFORE reactive changes to avoid component unmount issues during sorting
    if (props.event.id) {
        const payload = JSON.parse(JSON.stringify(props.event));
        payload.day = newStart;
        payload.end_day = newEndDay;
        if (payload.room && typeof payload.room === 'object' && payload.room.id) payload.room = { id: payload.room.id };
        if (payload.type && typeof payload.type === 'object' && payload.type.id) payload.type = { id: payload.type.id };
        if (payload.status && typeof payload.status === 'object' && payload.status.id) payload.status = { id: payload.status.id };

        // Send API request before reactive update
        axios.patch(route('event.update.single.bulk', { event: props.event.id }), { data: payload })
            .then(() => {
                // Update snapshot after successful patch
                if (!window.__bulkEventSnapshots) window.__bulkEventSnapshots = {};
                const snapshotKey = `event-snapshot-${props.event.id}`;
                window.__bulkEventSnapshots[snapshotKey] = getComparableEvent({ ...props.event, day: newStart, end_day: newEndDay });
            })
            .catch(err => console.error('bulk:patch-failed', props.event.id, err));
    }

    // Now apply reactive changes (may trigger re-sort and component re-render)
    props.event.day = newStart;
    props.event.end_day = newEndDay;
    dayString.value = getDayOfWeek(props.event.day).replace('.', '');
};

const onStartTimeFocusOut = (e) => {
    // Draft immer lokal übernehmen, damit der Wert nicht verloren geht ...
    props.event.start_time = draftStartTime.value;
    // ... aber Speicherung/Autofill aufschieben, wenn der Fokus in ein anderes Feld
    // derselben Zeile geht (z.B. Endzeit). Sonst füllt das Autofill die Endzeit vor
    // und der Cursor springt aus dem gerade angeklickten Feld.
    if (focusStaysInRow(e)) return;
    updateEventInDatabase();
};

const onEndTimeFocusOut = (e) => {
    // Commit draft end time to actual event object only on focusout
    props.event.end_time = draftEndTime.value;
    if (focusStaysInRow(e)) return;
    updateEventInDatabase();
};

const onRoomChange = (newRoom) => {
    // Send API request BEFORE reactive change to avoid component unmount during sort by room
    if (props.event.id) {
        const payload = JSON.parse(JSON.stringify(props.event));
        payload.room = newRoom ? { id: newRoom.id } : null;
        if (payload.type && typeof payload.type === 'object' && payload.type.id) payload.type = { id: payload.type.id };
        if (payload.status && typeof payload.status === 'object' && payload.status.id) payload.status = { id: payload.status.id };

        axios.patch(route('event.update.single.bulk', { event: props.event.id }), { data: payload })
            .then(() => {
                if (!window.__bulkEventSnapshots) window.__bulkEventSnapshots = {};
                const snapshotKey = `event-snapshot-${props.event.id}`;
                window.__bulkEventSnapshots[snapshotKey] = getComparableEvent({ ...props.event, room: newRoom });
            })
            .catch(err => console.error('bulk:patch-failed', props.event.id, err));
    }
    // Reactive change already applied by v-model
};

const onTypeChange = (newType) => {
    // Send API request BEFORE reactive change to avoid component unmount during sort by type
    if (props.event.id) {
        const payload = JSON.parse(JSON.stringify(props.event));
        payload.type = newType ? { id: newType.id } : null;
        if (payload.room && typeof payload.room === 'object' && payload.room.id) payload.room = { id: payload.room.id };
        if (payload.status && typeof payload.status === 'object' && payload.status.id) payload.status = { id: payload.status.id };

        axios.patch(route('event.update.single.bulk', { event: props.event.id }), { data: payload })
            .then(() => {
                if (!window.__bulkEventSnapshots) window.__bulkEventSnapshots = {};
                const snapshotKey = `event-snapshot-${props.event.id}`;
                window.__bulkEventSnapshots[snapshotKey] = getComparableEvent({ ...props.event, type: newType });
            })
            .catch(err => console.error('bulk:patch-failed', props.event.id, err));
    }
    // Reactive change already applied by v-model
};

// Keep draft in sync when event is updated externally (e.g. broadcast refresh)
watch(() => props.event.day, (v) => {
    if (v && v !== draftStartDate.value) {
        draftStartDate.value = v;
    }
    // Wochentags-Label mitziehen, wenn sich das Datum extern ändert.
    dayString.value = getDayOfWeek(v).replace('.', '');
});

watch(() => props.event.start_time, (v) => {
    const newVal = v || '';
    if (newVal !== draftStartTime.value) {
        draftStartTime.value = newVal;
    }
});

watch(() => props.event.end_time, (v) => {
    const newVal = v || '';
    if (newVal !== draftEndTime.value) {
        draftEndTime.value = newVal;
    }
});

const sortedRooms = computed(() => props.rooms.sort((a,b) => a.name.localeCompare(b.name)));
const sortedEventTypes = computed(() => props.event_types.sort((a,b) => a.name.localeCompare(b.name)));


const getDayOfWeek = (date) => {
    const days = ['So.', 'Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.'];
    // Datum in UTC parsen und mit getUTCDay auslesen, damit der Wochentag zum
    // angezeigten Kalenderdatum passt – unabhängig von der Zeitzone des Nutzers.
    // (new Date('2026-04-09') wird als UTC-Mitternacht geparst, .getDay() liest
    //  aber lokal → je nach Offset Off-by-one, z.B. "Fr" statt "Do".)
    const d = parseISODateToUTCMidnight(date);
    if (!d) return '';
    return days[d.getUTCDay()];
};

onMounted(() => {
    dayString.value = getDayOfWeek(props.event.day).replace('.', '');
    // ensure end_day initialized
    if (!props.event.end_day) props.event.end_day = props.event.day;
    // initialize snapshot so first change is detected
    if (!window.__bulkEventSnapshots) window.__bulkEventSnapshots = {};
    const snapshotKey = `event-snapshot-${props.event.id}`;
    window.__bulkEventSnapshots[snapshotKey] = getComparableEvent(props.event);
});
</script>
