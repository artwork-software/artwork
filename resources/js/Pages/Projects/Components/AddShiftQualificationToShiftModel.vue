<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                title="Add Qualification to Shift"
                description="Füge eine Qualifikation zu dieser Schicht hinzu."
            />
            <p>
                Schichtinformation:
                {{ shift.craft.abbreviation }}
                {{ shift.formatted_dates.start }} {{ shift.start }} -
                {{ shift.formatted_dates.end }} {{ shift.end }}
            </p>
        </div>

        <div class="mt-5">
            <h3 class="font-semibold mb-2">
                Wähle eine Qualifikation die du hinzufügen möchtest:
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="qualification in shiftQualifications" :key="qualification.id">
                    <button @click="addQualificationToShift(qualification.id)" class="text-center bg-artwork-buttons-create text-white hover:bg-artwork-buttons-hover transition-colors duration-300 ease-in-out w-full rounded-md px-3 py-2">
                        <span class="flex items-center justify-center">
                            {{ qualification.name }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {router} from "@inertiajs/vue3";

const props = defineProps({
    shift: {
        type: Object,
        required: true
    },
    shiftQualifications: {
        type: Object,
        required: true
    },
})

const emits = defineEmits([
    'close'
]);

const addQualificationToShift = (qualificationId) => {
    router.patch(route('shifts.qualifications.add', {shift: props.shift.id}), {
        qualification_id: qualificationId
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emits('close');
        }
    });
}

</script>

<style scoped>

</style>