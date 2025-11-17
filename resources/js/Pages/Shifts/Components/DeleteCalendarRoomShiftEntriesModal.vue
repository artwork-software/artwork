<template>
    <ArtworkBaseModal @close="$emit('close', false)"  :title="$t('Delete')" :description="$t('Delete all shifts for the selected rooms and days? Only universal shifts are deleted.')">
        <div class="flex items-center justify-center gap-4">

            <BaseUIButton
                :label="$t('Delete Entries')"
                is-delete-button
                @click="handleSubmit(false)"
            />

            <BaseUIButton
                :label="$t('Delete Entries & new Entries')"
                is-add-button
                @click="handleSubmit(true)"
            />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import {router, useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

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

const handleSubmit = (preserveState) => {
    multiEditCellForm.post(route("multi-edit.calendar.cell.delete"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit("close", preserveState)
        },
        onFinish: () => {
            emit("close", preserveState)
        }
    });
};
</script>

<style scoped>
</style>
