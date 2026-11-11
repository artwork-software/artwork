<template>
    <ArtworkBaseModal
        title="Edit Event description"
        description="Edit the event description below."
        modal-size="sm:max-w-xl"
        @close="emit('close')"
    >
        <BaseAlertComponent
            type="info"
            message="The event description can be displayed in the calendar and can be viewed by all users after clicking on the event."
            use-translation
        />

        <form id="addEditEventNoteForm" @submit.prevent="addEditNote">
            <BaseTextarea
                id="description"
                v-model="description"
                label="Description"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="No, not really"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="addEditEventNoteForm"
                :label="event.description ? 'Update' : 'Save'"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {inject, ref} from "vue";
import axios from "axios";
import BaseTextarea from "@/Artwork/Inputs/BaseTextarea.vue";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseAlertComponent from "@/Components/Alerts/BaseAlertComponent.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'AddEditEventNoteModal'})

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close']);

const description = ref(props.event.description ?? '')
const processing = ref(false)

// Wie der Inline-Beschreibungs-Editor in BulkSingleEvent: per axios patchen (der
// Endpunkt liefert JSON, keine Inertia-Response), Zeile lokal aktualisieren und die
// "zuletzt bearbeitet"-Markierung sofort aus der Response setzen.
const markRowEdited = inject('bulkMarkRowEdited', () => {});

const addEditNote = async () => {
    if (processing.value) {
        return
    }
    processing.value = true
    try {
        const {data} = await axios.patch(route('event.update.description', props.event.id), {
            description: description.value,
        });
        props.event.description = description.value;
        markRowEdited(props.event.id, data?.event?.updated_at);
        emit('close');
    } catch (e) {
        // Fehler meldet der globale Toast, das Modal bleibt offen
        console.error('bulk:description-patch-failed', props.event.id, e);
    } finally {
        processing.value = false
    }
}
</script>
