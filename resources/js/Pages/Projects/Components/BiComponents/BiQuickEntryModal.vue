<template>
    <ArtworkBaseModal
        :title="$t('Enter BI figures')"
        :description="projectName"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <div v-for="metric in metrics" :key="metric.key" class="flex items-end gap-3">
                <BaseInput
                    type="number"
                    :id="'bi_quick_' + metric.key"
                    v-model.number="values[metric.key]"
                    :label="$t(metric.label)"
                    :min="0"
                    :step="metric.key === 'revenue' ? 0.01 : 1"
                    :disabled="notApplicable[metric.key]"
                    class="flex-1"
                />
                <label class="flex items-center gap-1.5 pb-2.5 text-xs text-gray-400 cursor-pointer select-none whitespace-nowrap">
                    <input
                        type="checkbox"
                        class="input-checklist !h-3.5 !w-3.5"
                        v-model="notApplicable[metric.key]"
                    />
                    {{ $t('Not relevant') }}
                </label>
            </div>

            <p class="text-xs text-gray-400">
                {{ $t('Totals for the whole project. Per-event entry is available in the project view.') }}
            </p>

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link
                    :href="projectLink"
                    class="text-xs text-indigo-600 hover:underline"
                >
                    {{ $t('Open project') }}
                </Link>
                <div class="flex items-center gap-3">
                    <span v-if="saveError" class="text-sm text-rose-600">{{ $t('Saving failed. Please try again.') }}</span>
                    <button @click="$emit('close')" class="text-sm text-gray-500 hover:text-gray-700">{{ $t('Cancel') }}</button>
                    <BaseUIButton :label="$t('Save')" @click="save" :disabled="saving || !hasInput" hide-icon />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';

const props = defineProps({
    projectId: { type: Number, required: true },
    projectName: { type: String, default: '' },
    projectLink: { type: String, required: true },
});

const emit = defineEmits(['close', 'saved']);

const metrics = [
    { key: 'visitors', label: 'Total visitors', totalField: 'visitors_total', naField: 'visitors_not_applicable' },
    { key: 'sold_tickets', label: 'Total sold tickets', totalField: 'sold_tickets_total', naField: 'sold_tickets_not_applicable' },
    { key: 'revenue', label: 'Total revenue', totalField: 'revenue_total', naField: 'revenue_not_applicable' },
];

const values = reactive({ visitors: null, sold_tickets: null, revenue: null });
const notApplicable = reactive({ visitors: false, sold_tickets: false, revenue: false });
const saving = ref(false);
const saveError = ref(false);

const hasInput = computed(() => metrics.some(
    metric => notApplicable[metric.key]
        || (values[metric.key] !== null && values[metric.key] !== '')
));

const save = async () => {
    saving.value = true;
    saveError.value = false;
    const payload = {};
    metrics.forEach((metric) => {
        payload[metric.naField] = notApplicable[metric.key];
        if (!notApplicable[metric.key] && values[metric.key] !== null && values[metric.key] !== '') {
            payload[metric.totalField] = values[metric.key];
        }
    });
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), payload);
        emit('saved');
    } catch (error) {
        console.error('Error saving BI quick entry', error);
        saveError.value = true;
    } finally {
        saving.value = false;
    }
};
</script>
