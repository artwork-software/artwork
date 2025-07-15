<template>
    <ArtworkBaseModal
        title="Select Commit Date"
        description="Select the date for the commit."
        @close="$emit('close')">

        <div v-if="isShiftCommitWorkFlowActive">
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg mb-4">
                <p class="text-xs font-lexend">
                    Die direkte Freigabe ist aktuell nicht möglich, da der Freigabe-Workflow aktiv ist. Bitte sende eine Freigabeanfrage an die verantwortlichen Nutzer*innen.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseInput
                v-model="shiftCommitForm.start"
                type="date"
                label="Commit Date"
                id="commit_date" />

            <BaseInput
                v-model="shiftCommitForm.end"
                type="date"
                label="End Date"
                id="end_date" />

        </div>
    </ArtworkBaseModal>
</template>

<script setup>


import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";

const props = defineProps({
    dateArray : {
        type: Array,
        required: true
    },
})

const emit = defineEmits(['close']);

const isShiftCommitWorkFlowActive = ref(usePage().props.shiftCommitWorkflow ?? false);

const shiftCommitForm = useForm({
    start: props.dateArray[0] || '',
    end: props.dateArray[1] || '',
})

</script>

<style scoped>

</style>
