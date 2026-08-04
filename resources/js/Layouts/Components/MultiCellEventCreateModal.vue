<template>
    <ArtworkBaseModal
        title="Create event in selected cells"
        description="The event is created as a separate event in each selected cell (day and room)."
        modal-size="max-w-4xl"
        @close="$emit('closed', false)"
    >
        <div class="space-y-4">

            <!-- Basics (wie EventComponent) -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-accent-500"></span>
                    <h3 class="ui-card-title">{{ $t('Basics') }}</h3>
                </header>

                <div class="ui-grid-2">
                    <ArtworkBaseListbox
                        v-model="selectedEventType"
                        :items="eventTypesList"
                        by="id"
                        option-label="name"
                        option-key="id"
                        label="Event type"
                        :use-translations="false"
                        :show-color-indicator="true"
                        color-property="hex_code"
                    />
                    <ArtworkBaseListbox
                        v-if="statusModule && statusList.length > 0"
                        v-model="selectedEventStatus"
                        :items="statusList"
                        by="id"
                        option-label="name"
                        option-key="id"
                        label="Event Status"
                        :use-translations="false"
                        :show-color-indicator="true"
                        color-property="color"
                    />
                    <div :class="statusModule && statusList.length > 0 ? '' : 'pt-5'">
                        <BaseInput
                            v-model="eventName"
                            id="multiCellEventTitle"
                            :label="selectedEventType?.individual_name ? $t('Event name') + '*' : $t('Event name')"
                            class="ui-input"
                        />
                    </div>
                </div>

                <div class="ui-grid-2 mt-0.5">
                    <span />
                    <p class="ui-error" v-if="fieldErrors.eventName">{{ fieldErrors.eventName }}</p>
                </div>
            </section>

            <!-- Zeit (Datum kommt aus den Zellen) -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-info"></span>
                    <h3 class="ui-card-title">{{ $t('Time') }}</h3>
                </header>

                <label class="inline-flex items-center gap-2">
                    <input type="checkbox" v-model="allDayEvent" class="ui-checkbox" />
                    <span class="text-[13px] text-text-muted">{{ $t('Full day') }}</span>
                </label>

                <div v-if="!allDayEvent" class="ui-grid-2 mt-2">
                    <BaseInput
                        type="time"
                        id="multiCellStartTime"
                        v-model="startTime"
                        :label="$t('Start time')"
                        class="ui-input"
                        @change="endAutoFilled && setEndFromDuration()"
                    />
                    <BaseInput
                        type="time"
                        id="multiCellEndTime"
                        v-model="endTime"
                        :label="$t('End time')"
                        class="ui-input"
                        @change="endAutoFilled = false"
                    />
                </div>

                <!-- Dauer-Shortcuts wie im EventComponent -->
                <div v-if="!allDayEvent && startTime" class="flex flex-wrap items-center gap-2 mt-4">
                    <div class="ui-card-title">{{ $t('Duration Shortcuts:') }}</div>
                    <button
                        v-for="m in quickDurations"
                        :key="m"
                        type="button"
                        class="px-2.5 py-1.5 text-xs rounded-md border border-border-subtle bg-white hover:bg-surface-sunken"
                        @click="applyQuickDuration(m)"
                    >
                        {{ m }} {{ $t('min') }}
                    </button>
                    <button
                        v-if="defaultDurationMin > 0 && !quickDurations.includes(defaultDurationMin)"
                        type="button"
                        class="px-2.5 py-1.5 text-xs rounded-md border border-accent-200 bg-accent-50 hover:bg-accent-100 text-accent-700"
                        @click="applyQuickDuration(defaultDurationMin)"
                    >
                        Standard: {{ defaultDurationMin }} {{ $t('min') }}
                    </button>
                </div>

                <p class="ui-error mt-1" v-if="fieldErrors.time">{{ fieldErrors.time }}</p>
            </section>

            <!-- Zellen-Übersicht (ersetzt Raum- und Datumseingabe) -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-danger"></span>
                    <h3 class="ui-card-title">{{ $t('Selected cells') }}</h3>
                </header>

                <span class="ui-hint">{{ $t('The event will be created on these days in these rooms.') }}</span>

                <div class="mt-2 max-h-48 space-y-1.5 overflow-y-auto pr-1">
                    <div
                        v-for="[day, roomNames] in cellsByDay"
                        :key="day"
                        class="flex flex-wrap items-center gap-1.5 rounded-md border border-border-subtle bg-surface-sunken px-2.5 py-2"
                    >
                        <span class="text-[13px] font-medium text-text whitespace-nowrap">
                            {{ formatDayLabel(day) }}
                        </span>
                        <span
                            v-for="(roomName, roomIdx) in roomNames"
                            :key="roomIdx"
                            class="inline-flex items-center rounded-full border border-border-subtle bg-white px-2.5 py-0.5 text-[12.5px] text-text"
                        >
                            {{ roomName }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Projekt (wie EventComponent, nur bestehende Projekte) -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-success"></span>
                    <h3 class="ui-card-title">{{ $t('Project') }}</h3>
                </header>

                <div class="mt-1">
                    <p class="ui-error" v-if="fieldErrors.project">{{ fieldErrors.project }}</p>
                </div>

                <label for="multiCellShowProjectInfo" class="flex items-center gap-2 mt-1 cursor-pointer">
                    <input id="multiCellShowProjectInfo" type="checkbox" v-model="showProjectInfo" class="ui-checkbox" />
                    <span class="text-[13px] text-text-muted">{{ $t('Enable project assignment') }}</span>
                </label>

                <div v-if="showProjectInfo" class="mt-2 space-y-2">
                    <template v-if="selectedProject?.id">
                        <div class="ui-project-chip">
                            <div class="min-w-0">
                                <span class="truncate xsDark text-text">{{ selectedProject?.name }}</span>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0">
                                <button type="button" class="ui-icon-btn" @click="selectedProject = null" :aria-label="$t('Remove project')">
                                    <IconCircleX class="size-4" />
                                </button>
                            </div>
                        </div>
                    </template>
                    <template v-else>
                        <ProjectSearch
                            :label="$t('Search for projects')"
                            @project-selected="selectedProject = $event"
                        />
                        <LastedProjects :limit="10" @select="selectedProject = $event" />
                    </template>
                </div>
            </section>

            <!-- Beschreibung -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-violet-400"></span>
                    <h3 class="ui-card-title">{{ $t('Description') }}</h3>
                </header>

                <BaseTextarea
                    :label="$t('What do I need to bear in mind for the event?')"
                    id="multiCellDescription"
                    v-model="description"
                    rows="4"
                />
            </section>

            <div v-if="requestError" class="ui-error">{{ requestError }}</div>

            <!-- Sticky Action Bar -->
            <div class="ui-footer">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="ui-btn-secondary" @click="$emit('closed', false)">
                        {{ $t('Cancel') }}
                    </button>
                    <FormButton
                        :disabled="submitting"
                        @click="submit"
                        :text="$t('Create event in {0} cells', [cells.length])"
                    />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import axios from "axios";
import { useI18n } from "vue-i18n";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import ProjectSearch from "@/Components/SearchBars/ProjectSearch.vue";
import LastedProjects from "@/Artwork/LastedProjects.vue";
import { IconCircleX } from "@tabler/icons-vue";

const { t } = useI18n(), $t = t;
const page = usePage();

const props = defineProps({
    // Liste der gewählten Zellen: [{ day: 'YYYY-MM-DD', room_id: number }]
    cells: { type: Array, required: true },
    eventTypes: { type: [Array, Object], required: true },
    eventStatuses: { type: [Array, Object], default: null },
    rooms: { type: [Array, Object], default: () => [] },
    isPlanning: { type: Boolean, default: false },
});

const emit = defineEmits(["closed"]);

const statusModule = computed(() => page.props.event_status_module);
const eventTypesList = computed(() => {
    const types = props.eventTypes;
    if (!types) return [];
    return Array.isArray(types) ? types : Object.values(types);
});
const statusList = computed(() => {
    const statuses = props.eventStatuses;
    if (!statuses) return [];
    return Array.isArray(statuses) ? statuses : Object.values(statuses);
});
const roomsList = computed(() => (Array.isArray(props.rooms) ? props.rooms : Object.values(props.rooms || {})));

// --- State (Defaults wie im EventComponent)
const selectedEventType = ref(eventTypesList.value[0] ?? null);
const selectedEventStatus = ref(statusList.value.find((status) => status.default) ?? statusList.value[0] ?? null);
const selectedProject = ref(null);
const showProjectInfo = ref(false);
const eventName = ref("");
const description = ref("");
const allDayEvent = ref(!!page.props.event_all_day_default);
const startTime = ref(page.props.event_start_time || "09:00");
const endTime = ref("");
const endAutoFilled = ref(true);
const quickDurations = [30, 60, 90];
const defaultDurationMin = computed(() => {
    const raw = Number(page.props.event_time_length_minutes);
    return Number.isFinite(raw) && raw > 0 ? raw : 60;
});

const fieldErrors = ref({});
const requestError = ref("");
const submitting = ref(false);

// Endzeit = Startzeit + Minuten (Datum kommt aus den Zellen; Überlauf über
// Mitternacht behandelt das Backend als Termin bis in den Folgetag)
const addMinutesToTime = (time, minutes) => {
    const [hour, minute] = time.split(":").map(Number);
    const total = ((hour * 60 + minute + minutes) % 1440 + 1440) % 1440;
    return `${String(Math.floor(total / 60)).padStart(2, "0")}:${String(total % 60).padStart(2, "0")}`;
};
const setEndFromDuration = () => {
    if (!startTime.value || allDayEvent.value) return;
    endTime.value = addMinutesToTime(startTime.value, defaultDurationMin.value);
    endAutoFilled.value = true;
};
const applyQuickDuration = (minutes) => {
    if (!startTime.value || allDayEvent.value) return;
    endTime.value = addMinutesToTime(startTime.value, minutes);
    endAutoFilled.value = true;
};
setEndFromDuration();

// --- Zellen gruppiert nach Tag für die Übersicht
const roomNameById = computed(() => {
    const map = new Map();
    for (const room of roomsList.value) {
        map.set(Number(room.roomId ?? room.id), room.name);
    }
    return map;
});
const cellsByDay = computed(() => {
    const map = new Map();
    for (const cell of props.cells) {
        if (!map.has(cell.day)) map.set(cell.day, []);
        map.get(cell.day).push(roomNameById.value.get(Number(cell.room_id)) ?? `#${cell.room_id}`);
    }
    return Array.from(map.entries()).sort((a, b) => a[0].localeCompare(b[0]));
});
const formatDayLabel = (isoDay) => {
    const parts = (isoDay ?? "").split("-");
    if (parts.length !== 3) return isoDay ?? "";
    const weekday = new Date(`${isoDay}T00:00:00`).toLocaleDateString(undefined, { weekday: "short" });
    return `${weekday} ${parts[2]}.${parts[1]}.${parts[0]}`;
};

const submit = () => {
    fieldErrors.value = {};
    requestError.value = "";

    if (!selectedEventType.value) {
        requestError.value = $t("Event type") + " *";
        return;
    }
    if (selectedEventType.value.individual_name && !eventName.value?.trim()) {
        fieldErrors.value.eventName = $t("Please enter an event name for this event type.");
        return;
    }
    if (selectedEventType.value.project_mandatory && !selectedProject.value?.id) {
        fieldErrors.value.project = $t("Please assign a project for this event type.");
        showProjectInfo.value = true;
        return;
    }
    if (!allDayEvent.value && (!startTime.value || !endTime.value)) {
        fieldErrors.value.time = $t("Please enter start and end time or select full day.");
        return;
    }

    submitting.value = true;
    axios.post(route("events.multi-cell.create"), {
        cells: props.cells,
        event_type_id: selectedEventType.value.id,
        event_status_id: statusModule.value ? (selectedEventStatus.value?.id ?? null) : null,
        project_id: showProjectInfo.value ? (selectedProject.value?.id ?? null) : null,
        name: eventName.value || null,
        description: description.value || null,
        start_time: allDayEvent.value ? null : startTime.value,
        end_time: allDayEvent.value ? null : endTime.value,
        is_planning: props.isPlanning,
    }).then(() => {
        emit("closed", true);
    }).catch((error) => {
        requestError.value = error.response?.data?.message ?? $t("An error has occurred");
    }).finally(() => {
        submitting.value = false;
    });
};
</script>
