<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader title="Löschen"
        description="Alle Einträge (inklusive Schichten) für die ausgewählten Termine löschen?"/>

        <div class="flex items-center justify-center gap-4">
            <div>
                <button type="button" @click="submitForm(false)" class="cursor-pointer bg-artwork-messages-error hover:bg-artwork-messages-error/90 rounded-md px-14 py-3 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
                    {{ $t('Delete Entries') }}
                </button>
            </div>
            <div>
                <button type="button" @click="submitForm(true)" class="cursor-pointer bg-artwork-buttons-create hover:bg-artwork-buttons-create/90 rounded-md px-14 py-3 text-sm font-semibold text-white shadow-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-artwork-buttons-create">
                    {{ $t('Delete Entries & new Entries') }}
                </button>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import {useForm} from "@inertiajs/vue3";
import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";

const props = defineProps({
    multiEditCellByDayAndUser: {
        type: Object,
        required: true,
    },
})

const emit = defineEmits(['close'])

const multiEditCellForm = useForm({
    entities: props.multiEditCellByDayAndUser,
})

const submitForm = (boolean) => {
    multiEditCellForm.post(route('multi-edit.cell.delete'), {
        preserveScroll: true,
        preserveState: boolean,
        onSuccess: () => {
            emit('close', boolean)
        }
    })
}

</script>

<style scoped>

</style>