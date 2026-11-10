<template>
    <ArtworkBaseModal
        title="Delete"
        modal-size="sm:max-w-xl"
        z-index="150"
        @close="closeWithoutDeleting"
    >
        <p class="text-sm text-text">
            {{ $t('Delete all entries (including shifts) for the selected events?') }}
        </p>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeWithoutDeleting"
            />
            <BaseUIButton
                label="Delete Entries"
                variant="danger"
                icon="IconTrash"
                :processing="processing && pendingPreserveState === false"
                :disabled="processing"
                @click="handleSubmit(false)"
            />
            <BaseUIButton
                label="Delete Entries & new Entries"
                variant="primary"
                icon="IconTrash"
                :processing="processing && pendingPreserveState === true"
                :disabled="processing"
                @click="handleSubmit(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref} from "vue";
import {router, useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'DeleteEntriesModal'})

const props = defineProps({
    multiEditCellByDayAndUser: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["close"]);

const multiEditCellForm = useForm({
    entities: props.multiEditCellByDayAndUser,
});

// Bleibt bis zum Ende des Nachladens gesetzt, damit nicht doppelt gelöscht wird
const processing = ref(false);
const pendingPreserveState = ref(null);

// Payload wie bisher beim Schließen über das X des alten Modals
const closeWithoutDeleting = () => {
    if (processing.value) {
        return;
    }
    emit('close', {closing: false});
};

const resetProcessing = () => {
    processing.value = false;
    pendingPreserveState.value = null;
};

const handleSubmit = (preserveState) => {
    if (processing.value) {
        return;
    }
    processing.value = true;
    pendingPreserveState.value = preserveState;
    let succeeded = false;
    multiEditCellForm.post(route("multi-edit.cell.delete"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            succeeded = true;
            router.reload({
                only: ['shiftPlan'],
                onSuccess: () => {
                    emit("close", preserveState)
                },
                onFinish: resetProcessing,
            })
        },
        // Auch bei Serverfehler oder abgebrochener Anfrage (nicht nur bei Validierungsfehlern) wieder freigeben,
        // sonst lässt sich das Fenster nicht mehr schließen
        onFinish: () => {
            if (!succeeded) {
                resetProcessing();
            }
        },
    });
};
</script>
