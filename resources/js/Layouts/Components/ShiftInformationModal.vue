<template>
    <ArtworkBaseModal
        v-if="show"
        title="Shift information"
        description="There is space for general information here. You can provide separate information for the individual shifts."
        modal-size="sm:max-w-xl"
        @close="closeModal"
    >
        <form id="shiftInformationForm" @submit.prevent="changeShiftDescription">
            <BaseTextarea
                id="shiftDescription"
                v-model="shiftDescription"
                label="What do I need to bear in mind with the shifts?"
                rows="4"
            />
        </form>
        <template #footer>
            <BaseUIButton
                label="Cancel"
                is-cancel-button
                hide-icon
                :disabled="processing"
                @click="closeModal"
            />
            <BaseUIButton
                type="submit"
                form="shiftInformationForm"
                label="Save"
                variant="primary"
                icon="IconDeviceFloppy"
                :processing="processing"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {ref, watch} from 'vue'
import {router} from '@inertiajs/vue3'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseTextarea from '@/Artwork/Inputs/BaseTextarea.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'

defineOptions({name: 'ShiftInformationModal'})

const props = defineProps({
    show: {type: Boolean, default: false},
    project: {type: Object, default: null},
})

const emit = defineEmits(['closeModal'])

// Lokale Kopie: die Eltern-Prop wird nicht mehr direkt verändert
const shiftDescription = ref('')
const processing = ref(false)

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        shiftDescription.value = props.project?.shiftDescription ?? ''
    }
}, {immediate: true})

function closeModal() {
    emit('closeModal')
}

function changeShiftDescription() {
    if (!props.project?.id || processing.value) {
        return
    }
    processing.value = true
    router.patch(route('projects.update.shift_description', {project: props.project.id}), {
        shiftDescription: shiftDescription.value,
    }, {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>
