<template>
    <ArtworkBaseModal
        v-if="isVisible"
        :title="title || 'Success'"
        modal-size="sm:max-w-lg"
        @close="closeModal(false)"
    >
        <div class="flex items-start gap-3">
            <span class="flex size-8 shrink-0 items-center justify-center rounded-md bg-success-surface text-success">
                <PropertyIcon name="IconCircleCheck" class="size-5" stroke-width="1.75" aria-hidden="true" />
            </span>
            <!-- Beschreibung kommt fertig übersetzt oder als Flash-Text, daher kein $t -->
            <p class="min-w-0 flex-1 pt-1.5 text-sm text-text">{{ description }}</p>
        </div>
        <template #footer>
            <BaseUIButton
                :label="buttonLabel"
                variant="primary"
                hide-icon
                @click="closeModal(true)"
            />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import {computed} from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import PropertyIcon from '@/Artwork/Icon/PropertyIcon.vue'

defineOptions({name: 'SuccessModal'})

const props = defineProps({
    title: {type: String, default: null},
    description: {type: String, default: ''},
    button: {type: String, default: null},
    is_budget: {type: Boolean, default: false},
    // Einige Aufrufer steuern die Sichtbarkeit über :show bzw. :open statt v-if
    show: {type: Boolean, default: null},
    open: {type: Boolean, default: null},
})

const emit = defineEmits(['closed', 'delete'])

const isVisible = computed(() => props.show !== false && props.open !== false)

const buttonLabel = computed(() => props.button || 'Close')

function closeModal(confirmed) {
    emit('closed', confirmed)
}
</script>
