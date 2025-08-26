<template>
    <BaseModal @closed="$emit('close', {closing: false})">
        <ModalHeader
            title="Löschen"
            description="Alle Einträge (inklusive Schichten) für die ausgewählten Termine löschen?"
        />
        <div class="flex items-center justify-center gap-4">
            <button
                type="button"
                @click="handleSubmit(false)"
                class="bg-artwork-error hover:bg-artwork-error/90 rounded-md px-14 py-3 text-sm font-semibold text-white shadow-sm focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create"
            >
                {{ $t('Delete Entries') }}
            </button>
            <button
                type="button"
                @click="handleSubmit(true)"
                class="bg-artwork-buttons-create hover:bg-artwork-buttons-create/90 rounded-md px-14 py-3 text-sm font-semibold text-white shadow-sm focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create"
            >
                {{ $t('Delete Entries & new Entries') }}
            </button>
        </div>
    </BaseModal>
</template>

<script setup>
import {router, useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";

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

const handleSubmit = (preserveState) => {
    multiEditCellForm.post(route("multi-edit.cell.delete"), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            router.reload({
                only: ['shiftPlan'],
                onSuccess: () => {
                    emit("close", preserveState)
                }
            })
        },
    });
};
</script>

<style scoped>
</style>
