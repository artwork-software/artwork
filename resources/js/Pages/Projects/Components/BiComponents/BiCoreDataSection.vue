<template>
    <div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            <BaseCheckbox
                :model-value="localData.is_new_production"
                @update:model-value="v => onToggle('is_new_production', v)"
                :label="$t('New production')"
                description=""
                :disabled="!canEdit"
            />
            <BaseCheckbox
                :model-value="localData.is_co_production"
                @update:model-value="v => onToggle('is_co_production', v)"
                :label="$t('Co-production')"
                description=""
                :disabled="!canEdit"
            />
            <BaseCheckbox
                :model-value="localData.is_own_production"
                @update:model-value="v => onToggle('is_own_production', v)"
                :label="$t('Own production')"
                description=""
                :disabled="!canEdit"
            />
            <BaseCheckbox
                :model-value="localData.is_germany_premiere"
                @update:model-value="v => onToggle('is_germany_premiere', v)"
                :label="$t('Germany premiere')"
                description=""
                :disabled="!canEdit"
            />
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
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';

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

const onToggle = (key, value) => {
    localData.value[key] = value;
    save();
};

const save = async () => {
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), localData.value);
        emit('updated');
    } catch (error) {
        console.error('Error saving core data', error);
    }
};
</script>
