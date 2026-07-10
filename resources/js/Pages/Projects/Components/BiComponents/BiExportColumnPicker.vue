<template>
    <div class="space-y-3">
        <div class="flex flex-wrap items-end gap-3">
            <ArtworkBaseListbox
                class="w-64"
                :model-value="selectedPreset"
                @update:model-value="applyPreset"
                :items="presetList"
                by="id"
                option-label="name"
                :label="$t('Column preset')"
                :placeholder="$t('Select preset')"
            />
            <BaseInput
                :id="idPrefix + '_preset_name'"
                v-model="newPresetName"
                :label="$t('Save as preset')"
                class="w-56"
            />
            <button
                class="text-sm text-blue-600 hover:underline pb-2 disabled:opacity-40"
                :disabled="!newPresetName || modelValue.length === 0"
                @click="savePreset"
            >
                {{ $t('Save preset') }}
            </button>
            <button
                v-if="selectedPreset"
                class="text-sm text-rose-600 hover:underline pb-2"
                @click="deletePreset"
            >
                {{ $t('Delete preset') }}
            </button>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-medium text-gray-700">
                    {{ $t('Columns') }}
                    <span class="text-xs font-normal text-gray-400">({{ modelValue.length }}/{{ columns.length }})</span>
                </h4>
                <div class="flex gap-3 text-xs">
                    <button class="text-blue-600 hover:underline" @click="selectAll">
                        {{ $t('Select all') }}
                    </button>
                    <button class="text-gray-500 hover:underline" @click="$emit('update:modelValue', [])">
                        {{ $t('Clear') }}
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2 overflow-y-auto" :class="maxHeightClass">
                <BaseCheckbox
                    v-for="col in columns"
                    :key="col.key"
                    :model-value="modelValue.includes(col.key)"
                    @update:model-value="v => toggleColumn(col.key, v)"
                    :label="col.translate === false ? col.label : $t(col.label)"
                    description=""
                />
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';

const props = defineProps({
    // [{ key, label, translate?: false }]
    columns: { type: Array, required: true },
    modelValue: { type: Array, required: true },
    // Wenn null, lädt die Komponente die Presets selbst nach
    presets: { type: Array, default: null },
    idPrefix: { type: String, default: 'bi_export' },
    maxHeightClass: { type: String, default: 'max-h-64' },
});

const emit = defineEmits(['update:modelValue']);

const presetList = ref(props.presets ? [...props.presets] : []);
const selectedPreset = ref(null);
const newPresetName = ref('');

onMounted(async () => {
    if (props.presets !== null) return;
    try {
        const response = await axios.get(route('bi.export.presets.index'));
        presetList.value = response.data;
    } catch (error) {
        console.error('Error loading presets', error);
    }
});

const toggleColumn = (key, checked) => {
    if (checked) {
        if (!props.modelValue.includes(key)) {
            emit('update:modelValue', [...props.modelValue, key]);
        }
    } else {
        emit('update:modelValue', props.modelValue.filter(k => k !== key));
    }
};

const selectAll = () => {
    emit('update:modelValue', props.columns.map(c => c.key));
};

const applyPreset = (preset) => {
    selectedPreset.value = preset;
    if (preset?.columns) {
        const valid = new Set(props.columns.map(c => c.key));
        emit('update:modelValue', preset.columns.filter(key => valid.has(key)));
    }
};

const savePreset = async () => {
    if (!newPresetName.value || props.modelValue.length === 0) return;
    try {
        const response = await axios.post(route('bi.export.presets.store'), {
            name: newPresetName.value,
            columns: props.modelValue,
        });
        presetList.value.push(response.data);
        selectedPreset.value = response.data;
        newPresetName.value = '';
    } catch (error) {
        console.error('Error saving preset', error);
    }
};

const deletePreset = async () => {
    if (!selectedPreset.value) return;
    try {
        await axios.delete(route('bi.export.presets.destroy', selectedPreset.value.id));
        presetList.value = presetList.value.filter(p => p.id !== selectedPreset.value.id);
        selectedPreset.value = null;
    } catch (error) {
        console.error('Error deleting preset', error);
    }
};
</script>
