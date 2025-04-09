<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="$t('Reject Event Verification Request')"
                :description="$t('Are you sure you want to reject this event verification request?')"
            />
        </div>

        <form @submit.prevent="rejectRequest">
            <div class="grid grid-cols-1 gap-y-2">
                <TextareaComponent
                    id="rejection_reason" v-model="rejectForm.rejection_reason" :label="$t('Rejection Reason')" />
            </div>

            <div class="flex items-center justify-between mt-5">
                <FormButton
                    class="bg-red-500 hover:bg-red-600"
                    @click="rejectRequest()"
                    :text="$t('Reject Request')"
                />
                <p class="cursor-pointer text-sm mt-3 text-secondary" @click="$emit('close')">
                    {{ $t('No, not really') }}
                </p>
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm} from "@inertiajs/vue3";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    eventVerification: {
        type: Object,
        required: true
    }
})

const rejectForm = useForm({
    rejection_reason: '',
})

const emits = defineEmits(['close'])

const rejectRequest = () => {
    rejectForm.post(route('event-verifications.rejected', props.eventVerification.id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            emits('close')
        },
    })
}
</script>

<style scoped>

</style>