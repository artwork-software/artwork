<template>
    <ArtworkBaseModal
        title="Create timeline preset form Event"
        description="Create a new timeline preset for the event"
        modal-size="sm:max-w-lg"
        @close="emit('close')"
    >
        <form id="createTimelinePresetFromEventForm" @submit.prevent="createTimelinePreset">
            <BaseInput
                id="timeline_preset_name"
                v-model="timelineForm.name"
                label="Enter the name of the timeline preset"
                required
                :error="timelineForm.errors.name"
                autofocus
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="timelineForm.processing"
                @click="emit('close')"
            />
            <BaseUIButton
                type="submit"
                form="createTimelinePresetFromEventForm"
                label="Create timeline preset"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="timelineForm.processing"
                :disabled="!timelineForm.name.trim()"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {useForm} from "@inertiajs/vue3";
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";

defineOptions({name: 'CreateTimelinePresetFormEvent'})

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
    if (timelineForm.processing || !timelineForm.name.trim()) {
        return
    }
    timelineForm.post(route('timeline-preset.store.form.event', {event: props.event.id}), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            emit('close')
        }
    })
}
</script>
