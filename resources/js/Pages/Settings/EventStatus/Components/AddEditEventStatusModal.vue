<template>
    <ArtworkBaseModal
        :title="eventStatusToEdit ? 'Edit event status' : 'Create event status'"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <form id="eventStatusForm" class="space-y-4" @submit.prevent="addOrUpdateEventStatus">
            <div class="flex items-end gap-x-4">
                <div class="shrink-0">
                    <ColorPickerComponent :color="eventStatus.color" @update-color="setColor" />
                </div>
                <div class="w-full">
                    <BaseInput
                        id="eventStatusName"
                        v-model="eventStatus.name"
                        label="Name"
                        required
                        :error="eventStatus.errors.name"
                        autofocus
                    />
                </div>
            </div>
            <ArtworkBaseToggle
                id="eventStatusDefault"
                v-model="eventStatus.default"
                label="Should this status be set as the default?"
                use-translation
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="eventStatus.processing"
                @click="closeModal(false)"
            />
            <BaseUIButton
                type="submit"
                form="eventStatusForm"
                :label="eventStatusToEdit ? 'Save' : 'Create'"
                variant="primary"
                :icon="eventStatusToEdit ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="eventStatus.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from 'vue'
import {useForm} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseToggle from '@/Artwork/Toggles/ArtworkBaseToggle.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import ColorPickerComponent from '@/Components/Globale/ColorPickerComponent.vue'

defineOptions({name: 'AddEditEventStatusModal'})

const props = defineProps({
    eventStatusToEdit: {
        type: Object,
        required: false,
        default: null,
    },
})

// closeModal(true) nach erfolgreichem Speichern, sonst ohne Wert (Eltern zeigen nur bei true Feedback)
const emit = defineEmits(['closeModal'])

const eventStatus = useForm({
    id: props.eventStatusToEdit?.id ?? null,
    name: props.eventStatusToEdit?.name ?? '',
    color: props.eventStatusToEdit?.color ?? '#ccc',
    default: props.eventStatusToEdit ? Boolean(props.eventStatusToEdit.default) : false,
})

const canSubmit = computed(() => eventStatus.name.trim() !== '' && !eventStatus.processing)

function closeModal(saved) {
    if (saved) {
        emit('closeModal', true)
        return
    }
    emit('closeModal')
}

function setColor(color) {
    eventStatus.color = color
}

function addOrUpdateEventStatus() {
    if (!canSubmit.value) {
        return
    }
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            eventStatus.reset()
            closeModal(true)
        },
    }

    if (props.eventStatusToEdit) {
        eventStatus.patch(route('event_status.update', {eventStatus: eventStatus.id}), options)
        return
    }
    eventStatus.post(route('event_status.store'), options)
}
</script>
