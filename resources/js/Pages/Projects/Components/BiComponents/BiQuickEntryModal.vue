<template>
    <ArtworkBaseModal
        :title="$t('Enter BI figures')"
        :description="projectName"
        @close="$emit('close')"
    >
        <div class="space-y-4">
            <p v-if="loading" class="text-xs text-text-subtle">{{ $t('Loading data...') }}</p>
            <p v-else-if="hasExistingValues" class="text-xs text-text-subtle">
                {{ $t('Current values are prefilled — change what is new.') }}
            </p>

            <div v-for="metric in metrics" :key="metric.key" class="flex items-end gap-3">
                <div class="flex-1">
                    <BaseInput
                        type="number"
                        :id="'bi_quick_' + metric.key"
                        v-model.number="values[metric.key]"
                        :label="$t(metric.label)"
                        :min="0"
                        :step="metric.key === 'revenue' ? 0.01 : 1"
                        :disabled="loading || notApplicable[metric.key] || perEvent[metric.key]"
                        :error="fieldErrors[metric.key] ?? ''"
                    />
                    <!-- Pro-Termin-Modus: Gesamtwerte würden serverseitig ignoriert → sagen statt schweigen -->
                    <p v-if="perEvent[metric.key]" class="mt-1 text-[11px] text-warning">
                        {{ $t('Recorded per event in the project — totals do not apply here.') }}
                    </p>
                </div>
                <BaseCheckbox
                    :id="'bi_quick_na_' + metric.key"
                    v-model="notApplicable[metric.key]"
                    :label="$t('Not relevant')"
                    :disabled="loading || perEvent[metric.key]"
                    class="pb-2 whitespace-nowrap"
                />
            </div>

            <p class="text-xs text-text-subtle">
                {{ $t('Totals for the whole project. Per-event entry is available in the project view.') }}
            </p>

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link
                    :href="projectLink"
                    class="text-xs text-accent-600 hover:underline"
                >
                    {{ $t('Open project') }}
                </Link>
                <div class="flex items-center gap-3">
                    <span v-if="saveError" class="text-sm text-danger">{{ saveError }}</span>
                    <button @click="$emit('close')" class="text-sm text-text-subtle hover:text-text-muted">{{ $t('Cancel') }}</button>
                    <BaseUIButton :label="$t('Save')" @click="save" :disabled="saving || loading || !hasInput" hide-icon />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseCheckbox from '@/Artwork/Inputs/BaseCheckbox.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import { extractSaveErrorMessage } from '@/Composeables/BiSaveFeedback.js';
import { useTranslation } from '@/Composeables/Translation.js';

const t = useTranslation();

const props = defineProps({
    projectId: { type: Number, required: true },
    projectName: { type: String, default: '' },
    projectLink: { type: String, required: true },
});

const emit = defineEmits(['close', 'saved']);

const metrics = [
    { key: 'visitors', label: 'Total visitors', totalField: 'visitors_total', naField: 'visitors_not_applicable', modeField: 'visitor_mode' },
    { key: 'sold_tickets', label: 'Total sold tickets', totalField: 'sold_tickets_total', naField: 'sold_tickets_not_applicable', modeField: 'sold_tickets_mode' },
    { key: 'revenue', label: 'Total revenue', totalField: 'revenue_total', naField: 'revenue_not_applicable', modeField: 'revenue_mode' },
];

const values = reactive({ visitors: null, sold_tickets: null, revenue: null });
const notApplicable = reactive({ visitors: false, sold_tickets: false, revenue: false });
const perEvent = reactive({ visitors: false, sold_tickets: false, revenue: false });
const fieldErrors = reactive({ visitors: '', sold_tickets: '', revenue: '' });
const loading = ref(true);
const saving = ref(false);
const saveError = ref(null);
const hasExistingValues = ref(false);

// Bestandswerte vorbelegen: sonst überschreibt die Schnellerfassung still, was schon da ist
onMounted(async () => {
    try {
        const { data } = await axios.get(route('projects.bi.show', props.projectId));
        const biData = data?.bi_data ?? {};
        metrics.forEach((metric) => {
            values[metric.key] = biData[metric.totalField] ?? null;
            notApplicable[metric.key] = !!biData[metric.naField];
            perEvent[metric.key] = biData[metric.modeField] === 'per_event';
        });
        hasExistingValues.value = metrics.some(
            metric => values[metric.key] !== null || notApplicable[metric.key]
        );
    } catch (error) {
        // Ohne Vorbelegung bleibt das Modal benutzbar
        console.error('Error loading BI quick entry defaults', error);
    } finally {
        loading.value = false;
    }
});

const hasInput = computed(() => metrics.some(
    metric => !perEvent[metric.key] && (
        notApplicable[metric.key]
        || (values[metric.key] !== null && values[metric.key] !== '')
    )
));

const save = async () => {
    saving.value = true;
    saveError.value = null;
    metrics.forEach(metric => { fieldErrors[metric.key] = ''; });
    const payload = {};
    metrics.forEach((metric) => {
        if (perEvent[metric.key]) return;
        payload[metric.naField] = notApplicable[metric.key];
        if (!notApplicable[metric.key] && values[metric.key] !== null && values[metric.key] !== '') {
            payload[metric.totalField] = values[metric.key];
        }
    });
    try {
        await axios.put(route('projects.bi.update-data', props.projectId), payload);
        emit('saved', props.projectName);
    } catch (error) {
        console.error('Error saving BI quick entry', error);
        // Feldfehler ans Feld, alles andere in die Zeile am Button
        const errors = error?.response?.data?.errors ?? {};
        let fieldHit = false;
        metrics.forEach((metric) => {
            const message = errors[metric.totalField]?.[0] ?? errors[metric.naField]?.[0];
            if (message) {
                fieldErrors[metric.key] = message;
                fieldHit = true;
            }
        });
        saveError.value = fieldHit
            ? t('Not saved. Check the highlighted field.')
            : (extractSaveErrorMessage(error) ?? t('Saving failed. Please try again.'));
    } finally {
        saving.value = false;
    }
};
</script>
