<template>
    <ArtworkBaseModal
        modal-size="max-w-3xl"
        :title="$t('Filter contacts')"
        :description="$t('Filter contacts by their properties.')"
        @close="$emit('close')"
        full-modal
    >
        <div>
            <!-- Active Filters Section -->
            <div class="mb-4 pb-4 border-b-2 border-dashed border-gray-300">
                <div class="flex items-start justify-between mb-3">
                    <BasePageTitle
                        :title="$t('Active filters')"
                        :description="$t('Your active filters. Click on a filter to remove it.')"
                    />
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <template v-for="prop in filterableProperties" :key="'tag-' + prop.id">
                        <div
                            v-if="isFilterActive(prop.id)"
                            class="group block cursor-pointer shrink-0 bg-blue-50 w-fit px-2 py-1.5 rounded-full border border-blue-200"
                            @click="removeFilter(prop.id)"
                        >
                            <div class="flex items-center">
                                <div class="mx-2">
                                    <p class="text-blue-500 text-xs group-hover:text-blue-600">
                                        {{ prop.name }}: {{ formatFilterLabel(prop) }}
                                    </p>
                                </div>
                                <XIcon class="size-4 text-blue-500 hover:text-error" />
                            </div>
                        </div>
                    </template>
                    <p v-if="!hasActiveFilters" class="text-gray-400 text-sm">{{ $t('No active filters') }}</p>
                </div>
            </div>

            <!-- Filter Inputs -->
            <div class="space-y-4">
                <div v-for="prop in filterableProperties" :key="prop.id">
                    <label class="block text-sm font-medium text-gray-700 mb-1">{{ prop.name }}</label>

                    <!-- Text / Textarea / Link -->
                    <BaseInput
                        v-if="prop.type === 'text' || prop.type === 'textarea' || prop.type === 'link'"
                        :id="'filter-' + prop.id"
                        v-model="localFilters[prop.id]"
                        :placeholder="prop.name"
                    />

                    <!-- Number -->
                    <BaseInput
                        v-else-if="prop.type === 'number'"
                        type="number"
                        :id="'filter-' + prop.id"
                        v-model="localFilters[prop.id]"
                        :placeholder="prop.name"
                    />

                    <!-- Date -->
                    <BaseInput
                        v-else-if="prop.type === 'date'"
                        type="date"
                        :id="'filter-' + prop.id"
                        v-model="localFilters[prop.id]"
                    />

                    <!-- Checkbox (tri-state: All / Yes / No) -->
                    <select
                        v-else-if="prop.type === 'checkbox'"
                        v-model="localFilters[prop.id]"
                        class="block w-full rounded-md border border-gray-200 shadow-sm focus:outline-none focus:ring-1 focus:ring-artwork-buttons-create focus:border-artwork-buttons-create transition-[box-shadow,border-color] duration-150 ease-in-out text-sm"
                    >
                        <option value="">{{ $t('All') }}</option>
                        <option value="1">{{ $t('Yes') }}</option>
                        <option value="0">{{ $t('No') }}</option>
                    </select>

                    <!-- Select (checkboxes per option, OR logic) -->
                    <div v-else-if="prop.type === 'select'" class="flex flex-wrap gap-3">
                        <label
                            v-for="option in (prop.select_values ?? [])"
                            :key="option"
                            class="flex items-center gap-1.5 text-sm cursor-pointer"
                        >
                            <input
                                type="checkbox"
                                :value="option"
                                v-model="localFilters[prop.id]"
                                class="rounded border-gray-300 text-indigo-600 h-4 w-4"
                            />
                            {{ option }}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="py-4">
            <div class="flex items-center justify-between">
                <div>
                    <BaseUIButton @click="resetFilters" type="button" label="Reset" use-translation icon="IconRestore" />
                </div>
                <div class="flex items-center gap-4">
                    <BaseUIButton @click="applyFilters" type="button" label="Apply" use-translation is-add-button icon="IconCircleCheck" />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { reactive, computed } from 'vue'
import { XIcon } from '@heroicons/vue/outline'
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue'
import BasePageTitle from '@/Artwork/Titles/BasePageTitle.vue'
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue'
import BaseInput from '@/Artwork/Inputs/BaseInput.vue'

const props = defineProps({
    filterableProperties: { type: Array, required: true },
    currentFilters: { type: Object, default: () => ({}) },
})

const emit = defineEmits(['close', 'apply'])

// Initialize local filters from currentFilters
const buildInitialFilters = () => {
    const filters = {}
    for (const prop of props.filterableProperties) {
        const current = props.currentFilters[prop.id]
        if (prop.type === 'select') {
            filters[prop.id] = Array.isArray(current) ? [...current] : []
        } else {
            filters[prop.id] = current ?? ''
        }
    }
    return filters
}

const localFilters = reactive(buildInitialFilters())

const isFilterActive = (propId) => {
    const val = localFilters[propId]
    return Array.isArray(val) ? val.length > 0 : val !== null && val !== '' && val !== undefined
}

const hasActiveFilters = computed(() =>
    props.filterableProperties.some(p => isFilterActive(p.id))
)

const formatFilterLabel = (prop) => {
    const val = localFilters[prop.id]
    if (prop.type === 'checkbox') {
        return val === '1' ? 'Ja' : 'Nein'
    }
    if (Array.isArray(val)) {
        return val.join(', ')
    }
    return val
}

const removeFilter = (propId) => {
    const prop = props.filterableProperties.find(p => p.id === propId)
    if (prop?.type === 'select') {
        localFilters[propId] = []
    } else {
        localFilters[propId] = ''
    }
}

const resetFilters = () => {
    for (const prop of props.filterableProperties) {
        if (prop.type === 'select') {
            localFilters[prop.id] = []
        } else {
            localFilters[prop.id] = ''
        }
    }
}

const applyFilters = () => {
    emit('apply', { ...localFilters })
    emit('close')
}
</script>
