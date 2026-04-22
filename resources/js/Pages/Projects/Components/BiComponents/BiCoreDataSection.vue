<template>
    <div class="mb-8 border-b border-dashed border-gray-400 pb-6">
        <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ $t('Production data') }}</h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="localData.is_new_production" :disabled="!canEdit" @change="save" class="rounded border-gray-300" />
                {{ $t('New production') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="localData.is_co_production" :disabled="!canEdit" @change="save" class="rounded border-gray-300" />
                {{ $t('Co-production') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="localData.is_own_production" :disabled="!canEdit" @change="save" class="rounded border-gray-300" />
                {{ $t('Own production') }}
            </label>
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" v-model="localData.is_germany_premiere" :disabled="!canEdit" @change="save" class="rounded border-gray-300" />
                {{ $t('Germany premiere') }}
            </label>
        </div>
        <div class="max-w-xs">
            <BaseInput
                type="date"
                id="premiere_date"
                v-model="localData.premiere_date"
                :label="$t('Premiere date')"
                :disabled="!canEdit"
                @change="save"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';

const props = defineProps({
    biData: { type: Object, default: null },
    canEdit: { type: Boolean, default: false },
    projectId: { type: Number, required: true },
});

const emit = defineEmits(['updated']);

const localData = ref({
    is_new_production: false,
    is_co_production: false,
    is_own_production: false,
    is_germany_premiere: false,
    premiere_date: null,
});

watch(() => props.biData, (val) => {
    if (val) {
        localData.value = {
            is_new_production: val.is_new_production ?? false,
            is_co_production: val.is_co_production ?? false,
            is_own_production: val.is_own_production ?? false,
            is_germany_premiere: val.is_germany_premiere ?? false,
            premiere_date: val.premiere_date ? val.premiere_date.substring(0, 10) : null,
        };
    }
}, { immediate: true });

const save = async () => {
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), localData.value);
        emit('updated');
    } catch (error) {
        console.error('Error saving core data', error);
    }
};
</script>
