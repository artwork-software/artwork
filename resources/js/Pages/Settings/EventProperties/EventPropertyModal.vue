<template>
    <ArtworkBaseModal
        :title="eventPropertyToEdit ? 'Edit event property' : 'Create event property'"
        modal-size="sm:max-w-lg"
        @close="closeModal"
    >
        <form id="eventPropertyForm" @submit.prevent="addOrUpdateEventProperty">
            <div class="flex items-end gap-x-4">
                <div class="shrink-0">
                    <IconSelector
                        :current-icon="eventPropertyToEdit ? eventPropertyToEdit.icon : null"
                        @update:model-value="addIconToForm"
                    />
                </div>
                <div class="w-full">
                    <BaseInput
                        id="eventPropertyName"
                        v-model="eventPropertyForm.name"
                        label="Name"
                        required
                        :error="eventPropertyForm.errors.name"
                        autofocus
                    />
                </div>
            </div>
            <p
                v-for="(error, field) in otherErrors"
                :key="field"
                class="mt-2 text-xs text-danger"
            >
                {{ error }}
            </p>
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="eventPropertyForm.processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="eventPropertyForm"
                :label="eventPropertyToEdit ? 'Save' : 'Create'"
                variant="primary"
                :icon="eventPropertyToEdit ? 'IconDeviceFloppy' : 'IconCirclePlus'"
                :processing="eventPropertyForm.processing"
                :disabled="!canSubmit"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from 'vue'
import {useForm} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import IconSelector from '@/Components/Icon/IconSelector.vue'

defineOptions({name: 'EventPropertyModal'})

const props = defineProps({
    eventPropertyToEdit: {
        type: Object,
        required: false,
        default: null,
    },
})

const emit = defineEmits(['closeModal'])

const eventPropertyForm = useForm({
    id: props.eventPropertyToEdit?.id ?? null,
    name: props.eventPropertyToEdit?.name ?? '',
    icon: props.eventPropertyToEdit?.icon ?? null,
})

const canSubmit = computed(() => eventPropertyForm.name.trim() !== '' && !eventPropertyForm.processing)

// Fehler, die nicht am Namensfeld hängen (z. B. Icon)
const otherErrors = computed(() => Object.fromEntries(
    Object.entries(eventPropertyForm.errors).filter(([field]) => field !== 'name')
))

function closeModal() {
    emit('closeModal')
}

function addIconToForm(icon) {
    eventPropertyForm.icon = icon
}

function addOrUpdateEventProperty() {
    if (!canSubmit.value) {
        return
    }
    const options = {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            eventPropertyForm.reset()
            closeModal()
        },
    }

    if (props.eventPropertyToEdit === null) {
        eventPropertyForm.post(route('event_settings.event_properties.store'), options)
        return
    }

    eventPropertyForm.patch(
        route('event_settings.event_properties.update', {id: props.eventPropertyToEdit.id}),
        options
    )
}
</script>
