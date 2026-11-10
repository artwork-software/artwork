<template>
    <ArtworkBaseModal
        title="Reject Event Verification Request"
        description="Are you sure you want to reject this event verification request?"
        modal-size="sm:max-w-lg"
        @close="$emit('close')"
    >
        <form id="rejectEventVerificationForm" @submit.prevent="rejectRequest">
            <BaseTextarea
                id="rejection_reason"
                v-model="rejectForm.rejection_reason"
                label="Rejection Reason"
                autofocus
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="rejectForm.processing"
                @click="$emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="rejectEventVerificationForm"
                label="Reject Request"
                variant="danger"
                icon="IconCircleX"
                :processing="rejectForm.processing"
                :disabled="rejectForm.processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    eventVerification: {
        type: Object,
        required: false
    },
    event: {
        type: Object,
        required: false,
        default: []
    },
    eventIds: {
        type: Array,
        required: false,
        default: []
    }
})

const rejectForm = useForm({
    rejection_reason: '',
    events: props.eventIds ? props.eventIds : [],
    event: props.event ? props.event.id : null,
})


const emits = defineEmits(['close'])

const rejectRequest = () => {
    if (rejectForm.processing) {
        return
    }
    if (props.event && props.event.id) {
        rejectForm.post(route('event-verifications.reject-by-event', props.event.id),{
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emits('close')
            },
        })
    } else if(props.eventIds.length > 0){
        rejectForm.post(route('event-verifications.reject-by-events'), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                emits('close')
            },
        })
    } else if (props.eventVerification?.id) {
        rejectForm.post(route('event-verifications.rejected', props.eventVerification.id), {
            preserveScroll: true,
            preserveState: false,
            onSuccess: () => {
                emits('close')
            },
        })
    }
}
</script>