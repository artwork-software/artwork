<template>
    <!-- Sammeländerung der ausgewählten Räume: Kategorien oder Eigenschaften zuweisen bzw. entfernen, Areal wechseln -->
    <ArtworkBaseModal
        modal-size="sm:max-w-lg"
        :title="title"
        :description="$t('The change applies to the {count} selected rooms.', { count: roomIds.length })"
        @close="$emit('close')"
    >
        <div class="mt-2 space-y-4">
            <ArtworkBaseListbox
                v-if="mode === 'move'"
                v-model="selectedArea"
                :items="areas"
                by="id"
                option-label="name"
                label="Room area"
                show-color-indicator
            />
            <RoomPropertyCheckboxGroup
                v-else
                :label="isCategoryMode ? $t('Room categories') : $t('Room properties')"
                :items="isCategoryMode ? categories : attributes"
                v-model="selectedIds"
                :empty-text="isCategoryMode ? $t('No room categories created yet') : $t('No room properties created yet')"
            />

            <p v-if="errorMessage" class="text-sm text-danger" role="alert">{{ errorMessage }}</p>
        </div>
        <template #footer>
            <BaseUIButton label="Cancel" is-cancel-button hide-icon :disabled="processing" @click="$emit('close')" />
            <BaseUIButton label="Apply" variant="primary" hide-icon :processing="processing" :disabled="!canSubmit || processing" @click="submit" />
        </template>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, ref } from 'vue'
import axios from 'axios'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import RoomPropertyCheckboxGroup from '@/Pages/Areas/Components/RoomPropertyCheckboxGroup.vue'
import { useTranslation } from '@/Composeables/Translation.js'

defineOptions({ name: 'RoomBulkEditModal' })

const $t = useTranslation()

const props = defineProps({
    // 'add_categories' | 'remove_categories' | 'add_attributes' | 'remove_attributes' | 'move'
    mode: { type: String, required: true },
    roomIds: { type: Array, required: true },
    categories: { type: Array, default: () => [] },
    attributes: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] },
})

const emit = defineEmits(['close', 'done'])

const title = computed(() => ({
    add_categories: $t('Assign categories'),
    remove_categories: $t('Remove categories'),
    add_attributes: $t('Assign properties'),
    remove_attributes: $t('Remove properties'),
    move: $t('Move to another area'),
}[props.mode]))

const isCategoryMode = computed(() => props.mode.endsWith('categories'))

const selectedIds = ref([])
const selectedArea = ref(null)
const canSubmit = computed(() => props.mode === 'move' ? !!selectedArea.value?.id : selectedIds.value.length > 0)

const processing = ref(false)
const errorMessage = ref('')

const submit = () => {
    if (!canSubmit.value || processing.value) return
    processing.value = true
    errorMessage.value = ''

    const payload = { ids: props.roomIds, action: props.mode }
    if (props.mode === 'move') {
        payload.area_id = selectedArea.value.id
    } else if (isCategoryMode.value) {
        payload.category_ids = selectedIds.value
    } else {
        payload.attribute_ids = selectedIds.value
    }

    axios.post(route('rooms.bulk-update'), payload)
        .then(() => emit('done'))
        .catch((error) => {
            const errors = error?.response?.data?.errors
            errorMessage.value = (errors ? Object.values(errors).flat()[0] : null)
                ?? $t('The change could not be saved.')
        })
        .finally(() => {
            processing.value = false
        })
}
</script>
