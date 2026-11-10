<template>
    <ArtworkBaseModal
        v-if="show"
        title="Shift-relevant events"
        description="Define the appointment types for which shifts are to be assigned in this project."
        modal-size="sm:max-w-xl"
        @close="closeModal"
    >
        <div class="space-y-3">
            <ArtworkBaseListbox
                v-model="selectedEventTypes"
                :items="accessibleEventTypes"
                by="id"
                option-label="name"
                multiple
                show-color-indicator
                label="Event type"
                placeholder="Select appointment properties"
            />
            <div v-if="selectedEventTypes.length > 0" class="flex flex-wrap gap-2">
                <BaseChip v-for="eventType in selectedEventTypes" :key="eventType.id">
                    {{ eventType.name }}
                </BaseChip>
            </div>
        </div>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal"
            />
            <BaseUIButton
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
                @click="changeShiftRelevantEventTypes"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed, ref, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseChip from '@/Artwork/Chips/BaseChip.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'ShiftRelevantEventTypeModal'})

const props = defineProps({
    show: {type: Boolean, default: false},
    project: {type: Object, default: null},
    eventTypes: {type: Array, default: () => []},
})

const emit = defineEmits(['closeModal'])

const selectedEventTypes = ref([])
// Bereits gespeicherte, hier nicht wählbare Typen bleiben beim Speichern erhalten
const hiddenRelevantIds = ref([])
const processing = ref(false)

// Termintyp 1 ist der Standardtyp und wird hier nicht angeboten
const accessibleEventTypes = computed(() => (props.eventTypes ?? []).filter((type) => type.id !== 1))

// Das Modal bleibt eingehängt: beim Öffnen frisch aus dem Projekt übernehmen
watch(() => props.show, (isOpen) => {
    if (!isOpen) {
        return
    }
    const relevantIds = (props.project?.shift_relevant_event_types ?? []).map((eventType) => eventType.id)
    const accessibleIds = accessibleEventTypes.value.map((eventType) => eventType.id)
    selectedEventTypes.value = accessibleEventTypes.value.filter((eventType) => relevantIds.includes(eventType.id))
    hiddenRelevantIds.value = relevantIds.filter((id) => !accessibleIds.includes(id))
}, {immediate: true})

function closeModal() {
    emit('closeModal')
}

function changeShiftRelevantEventTypes() {
    if (!props.project?.id || processing.value) {
        return
    }
    processing.value = true
    router.patch(route('projects.update.shift_event_types', {project: props.project.id}), {
        shiftRelevantEventTypeIds: [
            ...hiddenRelevantIds.value,
            ...selectedEventTypes.value.map((eventType) => eventType.id),
        ],
    }, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
