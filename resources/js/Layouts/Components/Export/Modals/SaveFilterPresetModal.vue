<template>
    <ArtworkBaseModal
        @close="$emit('close')"
        :title="$t('Save filter preset')"
        :description="$t('Save the current filters as a preset for later use')"
        modal-size="sm:max-w-lg"
    >
        <div class="space-y-4">
            <!-- Ausgewählte Filter anzeigen -->
            <div v-if="activeFilters.length > 0">
                <label class="block text-sm font-medium text-text-muted mb-2">
                    {{ $t('Selected filters') }}
                </label>
                <div class="flex flex-wrap gap-2 p-3 bg-surface-sunken rounded-lg border border-border-subtle max-h-40 overflow-y-auto">
                    <div
                        v-for="(filter, index) in activeFilters"
                        :key="`${filter.id}-${index}`"
                        class="bg-accent-50 px-2 py-1 rounded-full border border-accent-200"
                    >
                        <span class="text-accent-600 text-xs">{{ filter.name }}</span>
                    </div>
                </div>
            </div>

            <div v-else class="p-3 bg-warning-surface rounded-lg border border-warning-border">
                <p class="text-sm text-warning">{{ $t('No filters selected') }}</p>
            </div>

            <!-- Name Input -->
            <BaseInput
                id="filterPresetName"
                v-model="presetName"
                :label="$t('Preset name')"
                :error="nameError"
            />

            <!-- Buttons -->
            <div class="flex justify-between items-center pt-4">
                <button
                    type="button"
                    class="text-sm text-text-subtle hover:text-text-muted"
                    @click="$emit('close')"
                >
                    {{ $t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="px-4 py-2 bg-accent-600 text-white text-sm font-medium rounded-lg hover:bg-accent-700 disabled:bg-surface-canvas disabled:border-border-subtle disabled:text-text-subtle disabled:cursor-not-allowed"
                    :disabled="!canSave || saving"
                    @click="savePreset"
                >
                    {{ saving ? $t('Saving...') : $t('Save') }}
                </button>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { ref, computed } from 'vue'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'
import { useTranslation } from '@/Composeables/Translation.js'

const $t = useTranslation()

const props = defineProps({
    activeFilters: {
        type: Array,
        required: true
    },
    filterData: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close', 'saved'])

const presetName = ref('')
const nameError = ref('')
const saving = ref(false)

const canSave = computed(() => {
    return presetName.value.trim().length > 0 && props.activeFilters.length > 0
})

const savePreset = async () => {
    if (!canSave.value) return

    nameError.value = ''
    saving.value = true

    try {
        const response = await axios.post(route('pdf-export-user-filters.store'), {
            name: presetName.value.trim(),
            filters: props.filterData
        })

        if (response.data.ok) {
            emit('saved', response.data.filter)
            emit('close')
        }
    } catch (error) {
        if (error.response?.status === 422) {
            nameError.value = error.response.data.errors?.name?.[0] || $t('Validation error')
        } else {
            nameError.value = $t('An error occurred while saving')
        }
    } finally {
        saving.value = false
    }
}
</script>
