<template>
    <ArtworkBaseModal
        :title="$t('Import timeline preset')"
        :description="$t('Select a timeline preset to import into the event.')"
        @close="$emit('close')"
    >
        <!-- Search -->
        <div class="mb-4">
            <BaseInput
                id="timelinePresetSearch"
                v-model="searchQuery"
                :label="$t('Search for timeline preset')"
                class="w-full"
            />
        </div>

        <!-- Presets Grid -->
        <div v-if="filteredPresets.length > 0" class="max-h-[350px] overflow-y-auto p-1">
            <div class="grid gap-3" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                <button
                    v-for="preset in filteredPresets"
                    :key="preset.id"
                    type="button"
                    @click="selectPreset(preset)"
                    class="text-left"
                >
                    <div
                        class="rounded-xl bg-white ring-1 ring-gray-200 p-3 hover:shadow-md transition-all duration-200"
                        :class="[selectedPreset?.id === preset.id ? 'ring-2 !ring-blue-500 shadow-md' : '']"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="min-w-0 truncate text-[13px] font-semibold text-gray-900">
                                {{ preset.name }}
                            </div>
                            <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-700">
                                {{ preset.times_count }} {{ $t('Points') }}
                            </span>
                        </div>
                    </div>
                </button>
            </div>
        </div>

        <div v-else-if="!isLoading" class="text-sm text-gray-500 text-center py-6">
            {{ $t('No timeline presets found.') }}
        </div>

        <div v-if="isLoading" class="text-sm text-gray-500 text-center py-6">
            {{ $t('Loading...') }}
        </div>

        <!-- Import Button -->
        <div class="flex items-center justify-center mt-4">
            <FormButton
                :text="$t('Import timeline preset')"
                @click="importTimelinePreset"
                :disabled="!selectedPreset"
            />
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import ArtworkBaseModal from "@/Artwork/Modals/ArtworkBaseModal.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import FormButton from "@/Layouts/Components/General/Buttons/FormButton.vue";
import {computed, onMounted, ref} from "vue";
import {router} from "@inertiajs/vue3";
import axios from "axios";

const props = defineProps({
    event: {
        type: Object,
        required: true
    }
})

const emit = defineEmits(['close'])

const searchQuery = ref('')
const allPresets = ref([])
const selectedPreset = ref(null)
const isLoading = ref(true)

const filteredPresets = computed(() => {
    const q = (searchQuery.value || '').toLowerCase().trim()
    if (!q) return allPresets.value
    return allPresets.value.filter(p => p.name.toLowerCase().includes(q))
})

const selectPreset = (preset) => {
    selectedPreset.value = selectedPreset.value?.id === preset.id ? null : preset
}

const importTimelinePreset = () => {
    if (!selectedPreset.value) return
    router.post(route('timeline-preset.import', {
        shiftPresetTimeline: selectedPreset.value.id,
        event: props.event.id
    }), {}, {
        preserveScroll: true,
        preserveState: false,
        onSuccess: () => {
            emit('close');
        }
    })
}

onMounted(async () => {
    try {
        const response = await axios.get(route('timeline-presets.all'))
        allPresets.value = response.data
    } catch (error) {
        console.error('Error loading timeline presets:', error)
    } finally {
        isLoading.value = false
    }
})
</script>
