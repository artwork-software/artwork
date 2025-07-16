<template>
    <ArtworkBaseModal
        title="Select Commit Date"
        description="Select the date for the commit."
        @close="$emit('close')">

        <div v-if="isShiftCommitWorkFlowActive">
            <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg mb-4">
                <p class="text-xs font-lexend">
                    {{ $t('Direct approval is currently not possible as the approval workflow is active. Please send a release request to the responsible users.') }}
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

        <div class="mt-5 flex items-center justify-between">
            <ArtworkBaseModalButton variant="danger" @click="$emit('close')">
                {{ $t('Cancel') }}
            </ArtworkBaseModalButton>
            <ArtworkBaseModalButton variant="primary" @click="requestShiftCommitForm" v-if="isShiftCommitWorkFlowActive">
                {{ $t('Request a firm commitment') }}
            </ArtworkBaseModalButton>
            <ArtworkBaseModalButton variant="primary" @click="commentShiftCommitForm" v-else>
                {{ $t('Lock all shifts') }}
            </ArtworkBaseModalButton>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>


import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import ArtworkBaseModalButton from "@/Artwork/Buttons/ArtworkBaseModalButton.vue";

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

const commentShiftCommitForm = () => {
    if (shiftCommitForm.start && shiftCommitForm.end) {
        // Validate the date range
        if (new Date(shiftCommitForm.start) > new Date(shiftCommitForm.end)) {
            console.error('Start date cannot be after end date.');
            return;
        }

        // Emit the event with the selected dates
        shiftCommitForm.post(route('shifts.commit'), {
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                console.error('Error submitting dates:', errors);
            }
        });
    } else {
        console.error('Please fill in both start and end dates.');
    }
}

const requestShiftCommitForm = () => {
    // Emit the event to request a firm commitment
    if (shiftCommitForm.start && shiftCommitForm.end) {
        // Validate the date range
        if (new Date(shiftCommitForm.start) > new Date(shiftCommitForm.end)) {
            console.error('Start date cannot be after end date.');
            return;
        }

        shiftCommitForm.post(route('shifts.requestCommit'), {
            onSuccess: () => {
                emit('close');
            },
            onError: (errors) => {
                console.error('Error requesting commitment:', errors);
            }
        });
    } else {
        console.error('Please fill in both start and end dates.');
    }
}

</script>

<style scoped>

</style>
