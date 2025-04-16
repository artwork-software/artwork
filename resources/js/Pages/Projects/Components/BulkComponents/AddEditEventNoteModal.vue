<template>
    <BaseModal @closed="$emit('close')">
        <ModalHeader
            :title="$t('Edit Event description')"
            :description="$t('Edit the event description below.')"
        />


        <form @submit.prevent class="mt-10">
           <BaseTextarea
               id="description" v-model="eventNoteForm.description" label="Description"/>

            <div class="my-5 flex items-center justify-center">
                <FormButton @click="addEditNote" type="submit" :text="event.description ? $t('Update') : $t('Save')" />
            </div>
        </form>
    </BaseModal>

</template>

<script setup>

import BaseModal from "@/Components/Modals/BaseModal.vue";
import ModalHeader from "@/Components/Modals/ModalHeader.vue";
import TextareaComponent from "@/Components/Inputs/TextareaComponent.vue";
import {useForm} from "@inertiajs/vue3";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";

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