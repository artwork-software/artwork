<template>
    <ArtworkBaseModal
        title="Duplicate events"
        description="Creates a copy of every selected event - optionally in another room, shifted by an offset or on a fixed date."
        modal-size="max-w-2xl"
        @close="$emit('closed', false)"
    >
        <div class="space-y-4">

            <div class="rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-900">
                {{ $t('{0} event(s) will be duplicated. The originals remain unchanged.', [checkedEvents.length]) }}
                <template v-if="isPlanning">
                    {{ $t('The copies are created as planned events in the planning calendar.') }}
                </template>
            </div>

            <!-- Raum -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-rose-400"></span>
                    <h3 class="ui-card-title">{{ $t('Room') }}</h3>
                </header>

                <span class="ui-hint">
                    {{ $t('The copies are placed in the chosen room. Without a selection, each copy stays in the room of its original.') }}
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
                    <span class="ui-dot bg-sky-400"></span>
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
                        :class="timeMode === mode.key
                            ? 'border-indigo-200 bg-indigo-50 text-indigo-700'
                            : 'border-zinc-200 bg-white hover:bg-zinc-50 text-zinc-700'"
                        @click="timeMode = mode.key"
                    >
                        {{ $t(mode.label) }}
                    </button>
                </div>

                <!-- Versatz -->
                <div v-if="timeMode === 'offset'" class="mt-3 space-y-2">
                    <span class="ui-hint">
                        {{ $t('Each copy is shifted relative to its original - e.g. "1 week later" creates copies one week after their originals.') }}
                    </span>

                    <div class="flex flex-wrap items-end gap-2">
                        <!-- Richtung -->
                        <div class="inline-flex rounded-md border border-zinc-200 overflow-hidden" role="radiogroup" :aria-label="$t('Direction')">
                            <button
                                type="button"
                                role="radio"
                                :aria-checked="direction === 'later'"
                                class="px-3 py-2.5 text-xs font-medium"
                                :class="direction === 'later' ? 'bg-indigo-50 text-indigo-700' : 'bg-white text-zinc-600 hover:bg-zinc-50'"
                                @click="direction = 'later'"
                            >
                                + {{ $t('later') }}
                            </button>
                            <button
                                type="button"
                                role="radio"
                                :aria-checked="direction === 'earlier'"
                                class="px-3 py-2.5 text-xs font-medium border-l border-zinc-200"
                                :class="direction === 'earlier' ? 'bg-indigo-50 text-indigo-700' : 'bg-white text-zinc-600 hover:bg-zinc-50'"
                                @click="direction = 'earlier'"
                            >
                                &minus; {{ $t('earlier') }}
                            </button>
                        </div>

                        <div class="w-24">
                            <BaseInput
                                type="number"
                                id="multiDuplicateOffsetValue"
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
                            class="px-2.5 py-1.5 text-xs rounded-md border border-zinc-200 bg-white hover:bg-zinc-50"
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
                        {{ $t('All copies are placed on this date, keeping their start time. Multi-day copies will end on the chosen day.') }}
                    </span>
                    <BaseInput
                        type="date"
                        id="multiDuplicateFixedDate"
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
                    <span class="ui-dot bg-emerald-400"></span>
                    <h3 class="ui-card-title">{{ $t('Summary') }}</h3>
                </header>

                <ul class="space-y-1 text-[13px] text-zinc-800">
                    <li class="flex items-start gap-2">
                        <IconCopy class="size-4 shrink-0 mt-0.5 text-zinc-400" />
                        <span>{{ $t('{0} new event(s) will be created.', [checkedEvents.length]) }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <IconDoorEnter class="size-4 shrink-0 mt-0.5 text-zinc-400" />
                        <span v-if="selectedRoom?.id">{{ $t('All copies are placed in room "{0}".', [selectedRoom.name]) }}</span>
                        <span v-else>{{ $t('Each copy stays in the room of its original.') }}</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <IconCalendarTime class="size-4 shrink-0 mt-0.5 text-zinc-400" />
                        <span>{{ timeSummary }}</span>
                    </li>
                    <li v-if="isPlanning" class="flex items-start gap-2">
                        <IconClipboardList class="size-4 shrink-0 mt-0.5 text-zinc-400" />
                        <span>{{ $t('The copies are created as planned events in the planning calendar.') }}</span>
                    </li>
                </ul>
            </section>

            <div v-if="requestError" class="ui-error">{{ requestError }}</div>

            <div class="ui-footer">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="ui-btn-secondary" @click="$emit('closed', false)">
                        {{ $t('Cancel') }}
                    </button>
                    <FormButton
                        :disabled="!isValid || submitting"
                        @click="save"
                        :text="$t('Duplicate {0} event(s)', [checkedEvents.length])"
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
import { IconCalendarTime, IconClipboardList, IconCopy, IconDoorEnter } from "@tabler/icons-vue";

const { t } = useI18n(), $t = t;

const props = defineProps({
    checkedEvents: { type: Array, required: true },
    rooms: { type: [Array, Object], default: () => [] },
    // Im Planungskalender erzeugte Duplikate werden als geplante Termine angelegt
    isPlanning: { type: Boolean, default: false },
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
            ? $t('The copies are created {0} {1} after their originals.', [offsetNumber.value, unitLabel])
            : $t('The copies are created {0} {1} before their originals.', [offsetNumber.value, unitLabel]);
    }
    if (timeMode.value === "date") {
        return fixedDate.value
            ? $t('All copies are placed on {0}.', [formatDate(fixedDate.value)])
            : $t("Please choose a date.");
    }
    return $t("The copies are created at the same time as their originals.");
});

// --- Validierung & Speichern (Duplizieren ohne Änderung ist erlaubt: Kopien am selben Ort)
const isValid = computed(() => {
    if (timeMode.value === "offset" && offsetInvalid.value) return false;
    if (timeMode.value === "date" && !fixedDate.value) return false;
    return true;
});

const requestError = ref("");
const submitting = ref(false);

const save = () => {
    requestError.value = "";
    submitting.value = true;
    axios.patch(route("multi-duplicate.save"), {
        events: props.checkedEvents,
        newRoomId: selectedRoom.value?.id ? selectedRoom.value.id : null,
        calculationType: direction.value === "later" ? 1 : 2,
        value: timeMode.value === "offset" ? offsetNumber.value : 0,
        type: selectedUnit.value?.id ?? 2,
        date: timeMode.value === "date" ? fixedDate.value : null,
        isPlanning: props.isPlanning,
    }).then(() => {
        emit("closed", true);
    }).catch((error) => {
        requestError.value = error.response?.data?.message ?? $t("An error has occurred");
    }).finally(() => {
        submitting.value = false;
    });
};
</script>
