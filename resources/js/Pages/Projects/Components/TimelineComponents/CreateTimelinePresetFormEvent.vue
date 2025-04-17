<template>
    <BaseModal @closed="$emit('close')">
        <div>
            <ModalHeader
                :title="$t('Create timeline preset form Event')"
                :description="$t('Create a new timeline preset for the event')"
            />
        </div>

        <div>
            <BaseInput
                v-model="timelineForm.name"
                label="Enter the name of the timeline preset"
                id="name"
            />
        </div>

        <div class="flex items-center justify-center mt-5">
            <FormButton
                :text="$t('Create timeline preset')"
                @click="createTimelinePreset" />
        </div>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import {useForm} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

const emit = defineEmits([
    'close'
])

const timelineForm = useForm({
    name: '',
})

const createTimelinePreset = () => {
    timelineForm.post(route('timeline-preset.store.form.event', {event: props.event.id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('close')
        }
    })
}
</script>

<style scoped>

</style>