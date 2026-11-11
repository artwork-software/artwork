<script setup>
import {toRef} from "vue";
import {useForm} from "@inertiajs/vue3";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import LegalBreakHint from "@/Components/Inputs/LegalBreakHint.vue";
import {useAutoBreak} from "@/Composeables/useAutoBreak";

defineOptions({name: 'AddEditShiftTimePreset'})

const emit = defineEmits(['closed'])

const props = defineProps({
    timePreset: {
        type: Object,
        required: false
    },
})

const newTimePreset = useForm({
    name: props.timePreset ? props.timePreset.name : '',
    start_time: props.timePreset ? props.timePreset.start_time : '',
    end_time: props.timePreset ? props.timePreset.end_time : '',
    id: props.timePreset ? props.timePreset.id : null,
    break_time: props.timePreset ? props.timePreset.break_time : 0,
})

// Auto-Pause nach ArbZG (leeres Feld befüllen, nicht-manuelle Werte anheben,
// manuelle Werte nie still überschreiben).
const autoBreak = useAutoBreak(
    toRef(newTimePreset, 'start_time'),
    toRef(newTimePreset, 'end_time'),
    toRef(newTimePreset, 'break_time')
)

const closeModal = () => {
    if (newTimePreset.processing) {
        return
    }
    emit('closed')
}

const saveTimePreset = () => {
    if (newTimePreset.processing) {
        return
    }
    // Erst nach Erfolg schließen, damit Validierungsfehler sichtbar bleiben
    const options = {
        preserveScroll: true,
        onSuccess: () => emit('closed'),
    }
    if (newTimePreset.id) {
        newTimePreset.patch(route('shift-time-preset.update', newTimePreset.id), options)
    } else {
        newTimePreset.post(route('shift-time-preset.store'), options)
    }
}
</script>

<template>
    <ArtworkBaseModal
        :title="newTimePreset.id ? 'Edit Time Preset' : 'Create Time Preset'"
        modal-size="sm:max-w-xl"
        @close="closeModal"
    >
        <form id="shiftTimePresetForm" class="grid grid-cols-1 gap-4 md:grid-cols-2" @submit.prevent="saveTimePreset">
            <BaseInput
                id="shiftTimePresetName"
                v-model="newTimePreset.name"
                label="Name"
                required
                :error="newTimePreset.errors.name"
                class="md:col-span-2"
            />
            <BaseInput
                id="shiftTimePresetStartTime"
                v-model="newTimePreset.start_time"
                type="time"
                label="Start-Time"
                required
                :error="newTimePreset.errors.start_time"
            />
            <BaseInput
                id="shiftTimePresetEndTime"
                v-model="newTimePreset.end_time"
                type="time"
                label="End-Time"
                required
                :error="newTimePreset.errors.end_time"
            />
            <div class="md:col-span-2">
                <BaseInput
                    id="shiftTimePresetBreakTime"
                    v-model="newTimePreset.break_time"
                    type="number"
                    label="Length of break in minutes"
                    required
                    :min="0"
                    :max="1000"
                    :error="newTimePreset.errors.break_time"
                    @input="autoBreak.markManual()"
                />
                <LegalBreakHint
                    :break-minutes="newTimePreset.break_time"
                    :legal-minutes="autoBreak.legalMinutes.value"
                    :has-times="autoBreak.hasTimes.value"
                    @reset="autoBreak.resetToLegal()"
                />
            </div>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="newTimePreset.processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="shiftTimePresetForm"
                :label="newTimePreset.id ? 'Save' : 'Create'"
                variant="primary"
                :icon="newTimePreset.id ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="newTimePreset.processing"
            />
        </template>
    </ArtworkBaseModal>
</template>
