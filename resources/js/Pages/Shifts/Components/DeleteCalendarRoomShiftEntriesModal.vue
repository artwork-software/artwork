<template>
    <ArtworkBaseModal
        title="Delete"
        modal-size="sm:max-w-xl"
        @close="closeWithoutDeleting"
    >
        <p class="text-sm text-text">
            {{ $t('Delete all shifts for the selected rooms and days? Only universal shifts are deleted.') }}
        </p>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="multiEditCellForm.processing"
                @click="closeWithoutDeleting"
            />
            <BaseUIButton
                label="Delete Entries"
                variant="danger"
                icon="IconTrash"
                :processing="multiEditCellForm.processing && pendingPreserveState === false"
                :disabled="multiEditCellForm.processing"
                @click="handleSubmit(false)"
            />
            <BaseUIButton
                label="Delete Entries & new Entries"
                variant="primary"
                icon="IconTrash"
                :processing="multiEditCellForm.processing && pendingPreserveState === true"
                :disabled="multiEditCellForm.processing"
                @click="handleSubmit(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from "vue";
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'DeleteCalendarRoomShiftEntriesModal'})

const props = defineProps({
    multiEditCellByRoomAndDates: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close"]);

const multiEditCellForm = useForm({
    entities: props.multiEditCellByRoomAndDates,
});

const pendingPreserveState = ref(null);

const closeWithoutDeleting = () => {
    if (multiEditCellForm.processing) {
        return;
    }
    emit('close', false);
};

const handleSubmit = (preserveState) => {
    if (multiEditCellForm.processing) {
        return;
    }
    pendingPreserveState.value = preserveState;
    multiEditCellForm.post(route("multi-edit.calendar.cell.delete"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit("close", preserveState)
        },
        onFinish: () => {
            pendingPreserveState.value = null;
        },
    });
};
</script>
