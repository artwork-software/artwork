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
                    <BaseInput
                        id="name"
                        v-model="eventStatus.name"
                        label="Name"
                        :show-label="true"
                        required
                        no-margin-top
                    />
                </div>
            </div>

            <div class="flex items-center gap-x-2 mt-4">
                <Switch v-model="eventStatus.default" :class="[eventStatus.default ? 'bg-artwork-buttons-create' : 'bg-gray-200', 'relative inline-flex h-6 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-artwork-buttons-create focus:ring-offset-2']">
                    <span :class="[eventStatus.default ? 'translate-x-4' : 'translate-x-0', 'pointer-events-none relative inline-block size-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out']">
                      <span :class="[eventStatus.default ? 'opacity-0 duration-100 ease-out' : 'opacity-100 duration-200 ease-in', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-4 text-gray-400" fill="none" viewBox="0 0 12 12">
                          <path d="M4 8l2-2m0 0l2-2M6 6L4 4m2 2l2 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                      </span>
                      <span :class="[eventStatus.default? 'opacity-100 duration-200 ease-in' : 'opacity-0 duration-100 ease-out', 'absolute inset-0 flex size-full items-center justify-center transition-opacity']" aria-hidden="true">
                        <svg class="size-4 text-artwork-buttons-create" fill="currentColor" viewBox="0 0 12 12">
                          <path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
                        </svg>
                      </span>
                    </span>
                </Switch>
                <div>
                    <p class="xsDark">{{ $t('Should this status be set as the default?') }}</p>
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
import {Switch} from "@headlessui/vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";

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
    color: props.eventStatusToEdit ? props.eventStatusToEdit.color : '#ccc',
    default: props.eventStatusToEdit ? props.eventStatusToEdit.default : false
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