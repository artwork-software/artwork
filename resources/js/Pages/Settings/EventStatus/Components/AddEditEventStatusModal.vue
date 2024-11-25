<template>
    <BaseModal @closed="$emit('closeModal')">
        <ModalHeader
            :title="eventStatusToEdit ? $t('Edit event status') : $t('Create event status')"
        />

        <form @submit.prevent="addOrUpdateEventStatus" class="mt-5">
            <div class="flex items-center gap-x-4">
                <div class="col-span-1">
                    <ColorPickerComponent :color="eventStatus?.color" @updateColor="setColor" />
                </div>
                <div class="w-full">
                    <TextInputComponent
                        id="name"
                        v-model="eventStatus.name"
                        label="Name"
                        :show-label="true"
                        required
                        no-margin-top
                    />
                </div>
            </div>

            <div class="flex items-center justify-center mt-5">
                <FormButton type="submit" :text="eventStatusToEdit ? $t('Save') : $t('Create')" />
            </div>
        </form>
    </BaseModal>
</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import ColorPickerComponent from "@/Components/Globale/ColorPickerComponent.vue";
import {useForm} from "@inertiajs/vue3";
import TextInputComponent from "@/Components/Inputs/TextInputComponent.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";

const props = defineProps({
    eventStatusToEdit: {
        type: Object,
        required: false,
        default: null
    }
})

const eventStatus = useForm({
    id: props.eventStatusToEdit ? props.eventStatusToEdit.id : null,
    name: props.eventStatusToEdit ? props.eventStatusToEdit.name : '',
    color: props.eventStatusToEdit ? props.eventStatusToEdit.color : '#ccc'
})
const emits = defineEmits(['closeModal'])
const setColor = (color) => {
    eventStatus.color = color
}

const addOrUpdateEventStatus = () => {
    if (props.eventStatusToEdit) {
        eventStatus.patch(route('event_status.update', {eventStatus: eventStatus.id}), {
            preserveScroll: true,
            onSuccess: () => {
                eventStatus.reset()
                emits('closeModal', true)
            }
        })
    } else {
        eventStatus.post(route('event_status.store'), {
            preserveScroll: true,
            onSuccess: () => {
                eventStatus.reset()
                emits('closeModal', true)
            },
        })
    }
}
</script>

<style scoped>

</style>