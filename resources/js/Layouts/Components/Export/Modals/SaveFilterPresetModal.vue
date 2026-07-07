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
                <label class="block text-sm font-medium text-zinc-700 mb-2">
                    {{ $t('Selected filters') }}
                </label>
                <div class="flex flex-wrap gap-2 p-3 bg-zinc-50 rounded-lg border border-zinc-200 max-h-40 overflow-y-auto">
                    <div
                        v-for="(filter, index) in activeFilters"
                        :key="`${filter.id}-${index}`"
                        class="bg-blue-50 px-2 py-1 rounded-full border border-blue-200"
                    >
                        <span class="text-blue-500 text-xs">{{ filter.name }}</span>
                    </div>
                </div>
            </div>

            <div v-else class="p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                <p class="text-sm text-yellow-700">{{ $t('No filters selected') }}</p>
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
                    class="text-sm text-zinc-500 hover:text-zinc-700"
                    @click="$emit('close')"
                >
                    {{ $t('Cancel') }}
                </button>
                <button
                    type="button"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
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
