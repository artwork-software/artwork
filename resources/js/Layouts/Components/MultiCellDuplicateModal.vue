<template>
    <ArtworkBaseModal
        title="Duplicate events into cells"
        description="Times are kept, day and room come from the respective cell."
        modal-size="sm:max-w-lg"
        @close="$emit('closed', false)"
    >
        <div class="space-y-4">
            <div class="rounded-md bg-accent-50 px-3 py-2 text-sm text-accent-700">
                {{ $t('{0} event(s) will be duplicated into {1} cells. This creates {2} new event(s).', [eventIds.length, cells.length, eventIds.length * cells.length]) }}
            </div>

            <!-- Ziel-Zellen gruppiert nach Tag -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-danger"></span>
                    <h3 class="ui-card-title">{{ $t('Selected cells') }}</h3>
                </header>

                <div class="max-h-48 space-y-1.5 overflow-y-auto pr-1">
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

            <div v-if="requestError" class="ui-error">{{ requestError }}</div>

            <div class="ui-footer">
                <div class="flex items-center justify-end gap-2">
                    <BaseUIButton type="button" hide-icon @click="$emit('closed', false)">
                        {{ $t('Cancel') }}
                    </BaseUIButton>
                    <FormButton
                        :disabled="submitting"
                        @click="submit"
                        :text="$t('Duplicate')"
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
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const { t } = useI18n(), $t = t;

const props = defineProps({
    eventIds: { type: Array, required: true },
    // Liste der gewählten Zellen: [{ day: 'YYYY-MM-DD', room_id: number }]
    cells: { type: Array, required: true },
    rooms: { type: [Array, Object], default: () => [] },
    // Im Planungskalender erzeugte Duplikate werden als geplante Termine angelegt
    isPlanning: { type: Boolean, default: false },
});

const emit = defineEmits(["closed"]);

const requestError = ref("");
const submitting = ref(false);

const roomsList = computed(() => (Array.isArray(props.rooms) ? props.rooms : Object.values(props.rooms || {})));
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
    requestError.value = "";
    submitting.value = true;
    axios.post(route("events.multi-cell.duplicate"), {
        events: props.eventIds,
        cells: props.cells,
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
