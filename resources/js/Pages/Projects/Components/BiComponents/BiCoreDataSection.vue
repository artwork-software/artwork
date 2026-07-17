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
                @change="onPremiereDateCommit"
                @focusout="onPremiereDateCommit"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import { useBiSaveFeedback } from '@/Composeables/BiSaveFeedback.js';

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

// Browser feuern bei type=date schon während des Tippens im Jahres-Segment change-Events
// (z.B. Jahr "0002"). Ein Save dabei triggert fetchData im Parent und der biData-Watcher
// setzt das Feld mitten in der Eingabe zurück — daher nur vollständige Daten speichern.
const isCompleteDate = (value) => /^\d{4}-\d{2}-\d{2}$/.test(value) && Number(value.slice(0, 4)) >= 1000;

const onPremiereDateCommit = () => {
    const value = localData.value.premiere_date || null;
    if (value && !isCompleteDate(value)) {
        return;
    }
    const serverValue = props.biData?.premiere_date ? props.biData.premiere_date.substring(0, 10) : null;
    if (value === serverValue) {
        return;
    }
    save();
};

const biSave = useBiSaveFeedback();

const save = async () => {
    const ok = await biSave.run(
        () => axios.put(route('projects.bi.update-data', props.projectId), localData.value)
    );
    if (ok) {
        emit('updated');
    }
};
</script>
