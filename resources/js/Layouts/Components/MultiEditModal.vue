<template>
    <ArtworkBaseModal
        title="Move events"
        description="Choose a target room, a time offset or a fixed date - the change applies to all selected events."
        modal-size="max-w-2xl"
        @close="$emit('closed', false)"
    >
        <div class="space-y-4">

            <div class="rounded-md bg-accent-50 px-3 py-2 text-sm text-accent-700">
                {{ $t('{0} event(s) selected. The events themselves are moved - nothing is copied.', [checkedEvents.length]) }}
            </div>

            <!-- Raum -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-danger"></span>
                    <h3 class="ui-card-title">{{ $t('Room') }}</h3>
                </header>

                <span class="ui-hint">
                    {{ $t('All selected events are moved into the chosen room. Without a selection, each event stays in its current room.') }}
                </span>

                <div class="mt-2">
                    <ArtworkBaseListbox
                        v-model="selectedRoom"
                        :items="roomItems"
                        by="id"
                        option-label="name"
                        option-key="id"
                        :label="$t('Target room')"
                        :use-translations="false"
                    />
                </div>
            </section>

            <!-- Datum & Uhrzeit -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-info"></span>
                    <h3 class="ui-card-title">{{ $t('Date & time') }}</h3>
                </header>

                <!-- Modus -->
                <div class="flex flex-wrap gap-2" role="radiogroup" :aria-label="$t('Date & time')">
                    <button
                        v-for="mode in timeModes"
                        :key="mode.key"
                        type="button"
                        role="radio"
                        :aria-checked="timeMode === mode.key"
                        class="px-2.5 py-1.5 text-xs rounded-md border"
                        :class="timeMode === mode.key ? 'border-accent-200 bg-accent-50 text-accent-700'
                            : 'border-border-subtle bg-white hover:bg-surface-sunken text-text-muted'"
                        @click="timeMode = mode.key"
                    >
                        {{ $t(mode.label) }}
                    </button>
                </div>

                <!-- Versatz -->
                <div v-if="timeMode === 'offset'" class="mt-3 space-y-2">
                    <span class="ui-hint">
                        {{ $t('Each event is shifted relative to its current date - e.g. "2 days later" moves every event two days into the future.') }}
                    </span>

                    <div class="flex flex-wrap items-end gap-2">
                        <!-- Richtung -->
                        <div class="inline-flex rounded-md border border-border-subtle overflow-hidden" role="radiogroup" :aria-label="$t('Direction')">
                            <button
                                type="button"
                                role="radio"
                                :aria-checked="direction === 'later'"
                                class="px-3 py-2.5 text-xs font-medium"
                                :class="direction === 'later' ? 'bg-accent-50 text-accent-700' : 'bg-white text-text-muted hover:bg-surface-sunken'"
                                @click="direction = 'later'"
                            >
                                + {{ $t('later') }}
                            </button>
                            <button
                                type="button"
                                role="radio"
                                :aria-checked="direction === 'earlier'"
                                class="px-3 py-2.5 text-xs font-medium border-l border-border-subtle"
                                :class="direction === 'earlier' ? 'bg-accent-50 text-accent-700' : 'bg-white text-text-muted hover:bg-surface-sunken'"
                                @click="direction = 'earlier'"
                            >
                                &minus; {{ $t('earlier') }}
                            </button>
                        </div>

                        <div class="w-24">
                            <BaseInput
                                type="number"
                                id="multiEditOffsetValue"
                                v-model="offsetValue"
                                :label="$t('Value')"
                            />
                        </div>
                        <div class="w-40">
                            <ArtworkBaseListbox
                                v-model="selectedUnit"
                                :items="units"
                                by="id"
                                option-label="label"
                                option-key="id"
                                :label="$t('Unit')"
                                :use-translations="false"
                                :enable-search="false"
                            />
                        </div>
                    </div>

                    <!-- Schnellauswahl -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="ui-hint">{{ $t('Quick select:') }}</span>
                        <button
                            v-for="preset in offsetPresets"
                            :key="preset.label"
                            type="button"
                            class="px-2.5 py-1.5 text-xs rounded-md border border-border-subtle bg-white hover:bg-surface-sunken"
                            @click="applyPreset(preset)"
                        >
                            {{ $t(preset.label) }}
                        </button>
                    </div>

                    <p class="ui-error" v-if="offsetInvalid">{{ $t('Please enter an offset greater than 0.') }}</p>
                </div>

                <!-- Festes Datum -->
                <div v-if="timeMode === 'date'" class="mt-3 space-y-2">
                    <span class="ui-hint">
                        {{ $t('All selected events are placed on this date, keeping their start time. Multi-day events will end on the chosen day.') }}
                    </span>
                    <BaseInput
                        type="date"
                        id="multiEditFixedDate"
                        v-model="fixedDate"
                        :label="$t('Date')"
                        class="ui-input"
                    />
                    <p class="ui-error" v-if="timeMode === 'date' && !fixedDate">{{ $t('Please choose a date.') }}</p>
                </div>
            </section>

            <!-- Zusammenfassung -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-success"></span>
                    <h3 class="ui-card-title">{{ $t('Summary') }}</h3>
                </header>

                <ul class="space-y-1 text-[13px] text-text">
                    <li class="flex items-start gap-2">
                        <IconDoorEnter class="size-4 shrink-0 mt-0.5 text-text-subtle" />
                        <span v-if="selectedRoom?.id">{{ $t('All events are moved to room "{0}".', [selectedRoom.name]) }}</span>
                        <span v-else>{{ $t('Rooms remain unchanged.') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <IconCalendarTime class="size-4 shrink-0 mt-0.5 text-text-subtle" />
                        <span>{{ timeSummary }}</span>
                    </li>
                </ul>

                <p class="ui-error mt-2" v-if="!hasChange">
                    {{ $t('Select at least a target room, a time offset or a date - otherwise there is nothing to move.') }}
                </p>
            </section>

            <div v-if="requestError" class="ui-error">{{ requestError }}</div>

            <div class="ui-footer">
                <div class="flex items-center justify-end gap-2">
                    <BaseUIButton type="button" hide-icon @click="$emit('closed', false)">
                        {{ $t('Cancel') }}
                    </BaseUIButton>
                    <FormButton
                        :disabled="!isValid || submitting"
                        @click="save"
                        :text="$t('Move {0} event(s)', [checkedEvents.length])"
                    />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from "vue";
import axios from "axios";
import { useI18n } from "vue-i18n";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import { IconCalendarTime, IconDoorEnter } from "@tabler/icons-vue";

const { t } = useI18n(), $t = t;

const props = defineProps({
    checkedEvents: { type: Array, required: true },
    rooms: { type: [Array, Object], default: () => [] },
});

const emit = defineEmits(["closed"]);

// --- Raum (id 0 = Sentinel für "Räume beibehalten")
const roomsList = computed(() => (Array.isArray(props.rooms) ? props.rooms : Object.values(props.rooms || {})));
const roomItems = computed(() => [
    { id: 0, name: $t("Keep current rooms") },
    ...roomsList.value.map((room) => ({ id: room.roomId ?? room.id, name: room.name })),
]);
const selectedRoom = ref(null);

// --- Zeit-Modus
const timeModes = [
    { key: "none", label: "No time change" },
    { key: "offset", label: "Shift by offset" },
    { key: "date", label: "Move to fixed date" },
];
const timeMode = ref("none");

// --- Versatz (Payload-Werte wie im Backend: calculationType 1=+/2=−, type 1..5)
const direction = ref("later");
const offsetValue = ref("1");
const units = [
    { id: 1, label: $t("Hour(s)") },
    { id: 2, label: $t("Day(s)") },
    { id: 3, label: $t("Week(s)") },
    { id: 4, label: $t("Month(s)") },
    { id: 5, label: $t("Year(s)") },
];
const selectedUnit = ref(units[1]);
const offsetPresets = [
    { label: "1 day", value: 1, unitId: 2 },
    { label: "1 week", value: 1, unitId: 3 },
    { label: "2 weeks", value: 2, unitId: 3 },
    { label: "4 weeks", value: 4, unitId: 3 },
];
const applyPreset = (preset) => {
    offsetValue.value = String(preset.value);
    selectedUnit.value = units.find((unit) => unit.id === preset.unitId);
};
const offsetNumber = computed(() => {
    const parsed = Number.parseInt(offsetValue.value, 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 0;
});
const offsetInvalid = computed(() => offsetNumber.value <= 0);

// --- Festes Datum
const fixedDate = ref(null);

const formatDate = (value) => {
    if (!value) return "-";
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;
    return `${String(date.getDate()).padStart(2, "0")}.${String(date.getMonth() + 1).padStart(2, "0")}.${date.getFullYear()}`;
};

const timeSummary = computed(() => {
    if (timeMode.value === "offset") {
        if (offsetInvalid.value) return $t("Please enter an offset greater than 0.");
        const unitLabel = selectedUnit.value?.label ?? "";
        return direction.value === "later"
            ? $t('All events are moved {0} {1} later.', [offsetNumber.value, unitLabel])
            : $t('All events are moved {0} {1} earlier.', [offsetNumber.value, unitLabel]);
    }
    if (timeMode.value === "date") {
        return fixedDate.value
            ? $t('All events are placed on {0}.', [formatDate(fixedDate.value)])
            : $t("Please choose a date.");
    }
    return $t("Date and time remain unchanged.");
});

// --- Validierung & Speichern
const hasChange = computed(() =>
    Boolean(selectedRoom.value?.id) ||
    (timeMode.value === "offset" && offsetNumber.value > 0) ||
    (timeMode.value === "date" && Boolean(fixedDate.value))
);
const isValid = computed(() => {
    if (!hasChange.value) return false;
    if (timeMode.value === "offset" && offsetInvalid.value) return false;
    if (timeMode.value === "date" && !fixedDate.value) return false;
    return true;
});

const requestError = ref("");
const submitting = ref(false);

const save = () => {
    requestError.value = "";
    submitting.value = true;
    axios.patch(route("multi-edit.save"), {
        events: props.checkedEvents,
        newRoomId: selectedRoom.value?.id ? selectedRoom.value.id : null,
        calculationType: direction.value === "later" ? 1 : 2,
        value: timeMode.value === "offset" ? offsetNumber.value : 0,
        type: selectedUnit.value?.id ?? 2,
        date: timeMode.value === "date" ? fixedDate.value : null,
    }).then(() => {
        emit("closed", true);
    }).catch((error) => {
        requestError.value = error.response?.data?.message ?? $t("An error has occurred");
    }).finally(() => {
        submitting.value = false;
    });
};
</script>
