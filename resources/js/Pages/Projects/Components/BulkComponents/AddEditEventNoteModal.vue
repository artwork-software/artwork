<template>
    <ArtworkBaseModal @close="$emit('close')" :title="$t('Edit Event description')" :description="$t('Edit the event description below.')">

        <BaseAlertComponent
            type="info"
            :message="$t('This description is displayed in the calendar or when editing the event.')"
            class="mb-5"   />

        <form @submit.prevent>
           <BaseTextarea
               id="description" v-model="eventNoteForm.description" label="Description"/>

            <div class="mt-5 flex items-center justify-between">
                <BaseUIButton @click="$emit('close')" label="No, not really" use-translation icon="IconCancel" />
                <BaseUIButton @click="addEditNote" type="submit" :label="event.description ? $t('Update') : $t('Save')" is-add-button />
            </div>
        </form>
    </ArtworkBaseModal>

</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close']);

const eventNoteForm = useForm({
    description: props.event.description
})


const addEditNote = () => {
    eventNoteForm.patch(route('event.update.description', props.event.id), {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            emit('close');
        }
    })
}

</script>

<style scoped>

</style>
