<template>
    <ArtworkBaseModal
        title="Move events into cell"
        description="Times are kept, the events are moved to the selected day and room."
        modal-size="sm:max-w-lg"
        @close="$emit('closed', false)"
    >
        <div class="space-y-4">
            <div class="rounded-md bg-accent-50 px-3 py-2 text-sm text-accent-700">
                {{ $t('{0} event(s) will be moved into the selected cell.', [eventIds.length]) }}
            </div>

            <!-- Ziel-Zelle -->
            <section class="ui-card">
                <header class="ui-card-header">
                    <span class="ui-dot bg-danger"></span>
                    <h3 class="ui-card-title">{{ $t('Target cell') }}</h3>
                </header>

                <div class="flex flex-wrap items-center gap-1.5 rounded-md border border-border-subtle bg-surface-sunken px-2.5 py-2">
                    <span class="text-[13px] font-medium text-text whitespace-nowrap">
                        {{ formatDayLabel(cell.day) }}
                    </span>
                    <span class="inline-flex items-center rounded-full border border-border-subtle bg-white px-2.5 py-0.5 text-[12.5px] text-text">
                        {{ roomName }}
                    </span>
                </div>
            </section>

            <div v-if="requestError" class="ui-error">{{ requestError }}</div>

            <div class="ui-footer">
                <div class="flex items-center justify-end gap-2">
                    <button type="button" class="ui-btn-secondary" @click="$emit('closed', false)">
                        {{ $t('Cancel') }}
                    </button>
                    <FormButton
                        :disabled="submitting"
                        @click="submit"
                        :text="$t('Move')"
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

const { t } = useI18n(), $t = t;

const props = defineProps({
    eventIds: { type: Array, required: true },
    // Ziel-Zelle: { day: 'YYYY-MM-DD', room_id: number }
    cell: { type: Object, required: true },
    rooms: { type: [Array, Object], default: () => [] },
});

const emit = defineEmits(["closed"]);

const requestError = ref("");
const submitting = ref(false);

const roomsList = computed(() => (Array.isArray(props.rooms) ? props.rooms : Object.values(props.rooms || {})));
const roomName = computed(() => {
    const room = roomsList.value.find((r) => Number(r.roomId ?? r.id) === Number(props.cell.room_id));
    return room?.name ?? `#${props.cell.room_id}`;
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
    axios.post(route("events.multi-cell.move"), {
        events: props.eventIds,
        cell: { day: props.cell.day, room_id: props.cell.room_id },
    }).then(() => {
        emit("closed", true);
    }).catch((error) => {
        requestError.value = error.response?.data?.message ?? $t("An error has occurred");
    }).finally(() => {
        submitting.value = false;
    });
};
</script>
