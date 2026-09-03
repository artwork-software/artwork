<template>
    <ArtworkBaseModal
        :title="$t('BI Export')"
        :description="mode === 'project' ? projectName : $t('Excel export across productions')"
        modal-size="max-w-4xl"
        @close="onClose"
    >
        <!-- Wartezustand: bleibt sichtbar, bis der Download startet -->
        <div v-if="isExporting || downloadStarted" class="py-6">
            <div class="flex items-start gap-4 rounded-lg border border-border-subtle bg-surface-sunken/60 p-4">
                <svg v-if="!downloadStarted" class="size-6 animate-spin text-accent-600 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                </svg>
                <IconCircleCheck v-else class="size-6 text-success shrink-0 mt-0.5" />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-text">
                        <template v-if="downloadStarted">{{ $t('Download started.') }}</template>
                        <template v-else-if="phase === 'pending'">{{ $t('Waiting for the export worker…') }}</template>
                        <template v-else>{{ $t('The Excel file is being created…') }}</template>
                    </p>
                    <p class="mt-0.5 text-xs text-text-subtle">
                        {{ $t('Elapsed') }}: {{ elapsedSeconds }} s
                        <template v-if="!downloadStarted"> · {{ $t('You can close this dialog; the file is kept for 24 hours.') }}</template>
                    </p>
                    <p v-if="queueSuspect" class="mt-2 text-xs text-warning">
                        {{ $t('No worker has picked up the export yet. If this persists, the queue worker may not be running — please tell an admin.') }}
                    </p>
                </div>
            </div>
            <div class="mt-4 flex items-center justify-end gap-3">
                <BaseUIButton
                    v-if="!downloadStarted"
                    :label="$t('Cancel export')"
                    hide-icon
                    variant="secondary"
                    @click="cancel"
                />
                <BaseUIButton v-else :label="$t('Close')" hide-icon @click="$emit('close')" />
            </div>
        </div>

        <div v-else-if="loading" class="py-10 text-center text-sm text-text-subtle">
            {{ $t('Loading data...') }}
        </div>

        <div v-else-if="loadError" class="py-6 text-sm text-danger">
            {{ $t('The export options could not be loaded.') }}
            <button type="button" class="ml-2 underline" @click="loadOptions">{{ $t('Try again') }}</button>
        </div>

        <div v-else class="space-y-5">
            <!-- 1. Was wird exportiert -->
            <section class="space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Productions') }}</h4>

                <template v-if="mode === 'dashboard'">
                    <div>
                        <ArtworkBaseListbox
                            :model-value="selectedCostCenters"
                            @update:model-value="value => selectedCostCenters = value"
                            :items="options.costCenters ?? []"
                            by="id"
                            option-label="name"
                            multiple
                            :search-threshold="0"
                            :search-placeholder="$t('Search cost bearer')"
                            :label="$t('Filter productions by cost bearer')"
                            :placeholder="$t('All cost bearers')"
                        />
                        <p class="mt-1.5 text-xs text-text-subtle">
                            {{ $t('Selecting cost bearers automatically selects their productions below.') }}
                        </p>
                    </div>
                    <ArtworkBaseListbox
                        :model-value="selectedProjects"
                        @update:model-value="value => selectedProjects = value"
                        :items="filteredProjects"
                        by="id"
                        option-label="name"
                        multiple
                        :search-threshold="0"
                        :label="$t('Productions')"
                        :placeholder="$t('Select productions')"
                    />
                    <p class="-mt-1 text-xs text-text-subtle">
                        {{ selectedProjects.length }} {{ $t('of') }} {{ (options.projects ?? []).length }} {{ $t('selected') }} ·
                        {{ $t('The export covers all productions of the house, regardless of your project access.') }}
                    </p>
                </template>
                <p v-else class="text-sm text-text-muted">{{ projectName }}</p>
            </section>

            <!-- 2. Zeitraum + Struktur -->
            <section class="space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Period and structure') }}</h4>
                <div class="flex flex-wrap items-end gap-4">
                    <BaseInput type="date" :id="idPrefix + '_date_from'" v-model="dateFrom" :label="$t('From')" class="w-44" />
                    <BaseInput type="date" :id="idPrefix + '_date_to'" v-model="dateTo" :label="$t('To')" class="w-44" />
                    <ArtworkBaseListbox
                        class="w-72"
                        :model-value="granularity"
                        @update:model-value="value => granularity = value"
                        :items="granularityOptions"
                        by="id"
                        option-label="name"
                        :label="$t('Export structure')"
                    />
                </div>
                <p class="text-xs text-text-subtle">
                    {{ dateHint }}
                    <span v-if="!dateFrom && !dateTo && !options.seasonFrom && !options.seasonTo" class="text-warning">
                        {{ $t('No season window is configured — without dates, all events count.') }}
                    </span>
                </p>

                <div v-if="includesEvents">
                    <ArtworkBaseListbox
                        class="w-full max-w-md"
                        :model-value="selectedTagFilters"
                        @update:model-value="value => selectedTagFilters = value"
                        :items="tagFilterOptions"
                        by="id"
                        option-label="name"
                        multiple
                        :label="$t('Filter events by BI tags')"
                        :placeholder="$t('All events')"
                    />
                    <p class="mt-1.5 text-xs text-text-subtle">
                        {{ $t('Without a selection, all events in the period are exported.') }}
                        {{ $t('The events sheet always has the same columns; the column selection below applies to the productions sheet.') }}
                    </p>
                </div>
            </section>

            <!-- 3. Spalten -->
            <section v-if="includesProjects" class="space-y-3">
                <h4 class="text-xs font-semibold uppercase tracking-wide text-text-subtle">{{ $t('Columns of the productions sheet') }}</h4>
                <BiExportColumnPicker
                    v-model="selectedColumns"
                    :groups="options.columnGroups ?? []"
                    :presets="options.presets ?? []"
                    :id-prefix="idPrefix"
                    max-height-class="max-h-72"
                />
            </section>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border-subtle pt-4">
                <p class="text-xs text-text-subtle">
                    {{ $t('The file gets an “Info” sheet with period, filters and creator.') }}
                </p>
                <div class="flex items-center gap-3">
                    <span v-if="exportError" class="text-sm text-danger">
                        {{ exportErrorMessage ?? $t('The export could not be generated.') }}
                    </span>
                    <button type="button" @click="$emit('close')" class="text-sm text-text-subtle hover:text-text-muted">{{ $t('Cancel') }}</button>
                    <BaseUIButton
                        @click="doExport"
                        :label="$t('Create Excel file')"
                        :disabled="!canExport"
                        v-tooltip.top="!canExport ? { value: disabledReason, appendTo: 'body', class: 'aw-tooltip' } : undefined"
                    />
                </div>
            </div>
        </div>
    </ArtworkBaseModal>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { IconCircleCheck } from '@tabler/icons-vue';
import ArtworkBaseModal from '@/Artwork/Modals/ArtworkBaseModal.vue';
import ArtworkBaseListbox from '@/Artwork/Listbox/ArtworkBaseListbox.vue';
import BaseInput from '@/Artwork/Inputs/BaseInput.vue';
import BaseUIButton from '@/Artwork/Buttons/BaseUIButton.vue';
import BiExportColumnPicker from '@/Pages/Projects/Components/BiComponents/BiExportColumnPicker.vue';
import { useBiExport } from '@/Composeables/BiExport.js';
import { useTranslation } from '@/Composeables/Translation.js';

const props = defineProps({
    // 'project' = genau eine Produktion (Projekt-Tab), 'dashboard' = Auswahl über alle
    mode: { type: String, default: 'dashboard' },
    projectId: { type: Number, default: null },
    projectName: { type: String, default: '' },
    // Zustandsübernahme (Steuerungstabelle): Spalten + Projekt-Ids vorbelegen
    initialColumns: { type: Array, default: null },
    initialProjectIds: { type: Array, default: null },
    defaultDateFrom: { type: String, default: null },
    defaultDateTo: { type: String, default: null },
    // Woher die vorbelegten Daten stammen: 'project' | 'dashboard' | 'season'
    dateSource: { type: String, default: 'season' },
});

const emit = defineEmits(['close']);

const t = useTranslation();
const {
    isExporting, exportError, exportErrorMessage, elapsedSeconds, phase,
    queueSuspect, downloadStarted, runExport, cancel,
} = useBiExport();

const idPrefix = computed(() => props.mode === 'project' ? 'bi_export_project' : 'bi_export_dashboard');

// --- Optionen vom Server (Katalog, Presets, Produktionen, Kostenträger, Spielzeit) ---

const options = ref({});
const loading = ref(true);
const loadError = ref(false);

const loadOptions = async () => {
    loading.value = true;
    loadError.value = false;
    try {
        const { data } = await axios.get(route('bi.export.options'));
        options.value = data;
        applyDefaults();
    } catch (error) {
        console.error('Error loading BI export options', error);
        loadError.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(loadOptions);

// --- Zustand ---

const dateFrom = ref(props.defaultDateFrom ?? '');
const dateTo = ref(props.defaultDateTo ?? '');

const dateHint = computed(() => ({
    project: t('Prefilled with the project period. Limits which events count towards per-event and count figures.'),
    dashboard: t('Prefilled with the dashboard period.'),
    season: t('Prefilled with the season window from the tool settings.'),
}[props.dateSource] ?? ''));

// Gleiche Standardstruktur in allen Einstiegen
const granularityOptions = [
    { id: 'both', name: t('Projects and events (2 sheets)') },
    { id: 'projects', name: t('Project rows only') },
    { id: 'events', name: t('Event rows only') },
];
const granularity = ref(granularityOptions[0]);
const includesProjects = computed(() => granularity.value?.id !== 'events');
const includesEvents = computed(() => granularity.value?.id !== 'projects');

const selectedTagFilters = ref([]);
const tagFilterOptions = computed(() => [
    ...(options.value.tagColumns ?? []).map(col => ({
        id: parseInt(String(col.key).replace('tag_', ''), 10),
        name: col.label,
    })),
    { id: 'untagged', name: t('Events without BI tag') },
]);

const selectedColumns = ref([]);

const selectedCostCenters = ref([]);
const selectedProjects = ref([]);

const filteredProjects = computed(() => {
    const projects = options.value.projects ?? [];
    if (selectedCostCenters.value.length === 0) return projects;
    const ids = selectedCostCenters.value.map(costCenter => costCenter.id);
    return projects.filter(project => ids.includes(project.cost_center_id));
});
watch(selectedCostCenters, () => {
    selectedProjects.value = [...filteredProjects.value];
});

const applyDefaults = () => {
    const known = new Set((options.value.columnGroups ?? []).flatMap(group => group.columns.map(col => col.key)));
    const wanted = props.initialColumns ?? options.value.defaultColumns ?? [];
    selectedColumns.value = wanted.filter(key => known.has(key));

    if (props.mode === 'dashboard') {
        const projects = options.value.projects ?? [];
        selectedProjects.value = props.initialProjectIds !== null
            ? projects.filter(project => props.initialProjectIds.includes(project.id))
            : [...projects];
    }
    if (!dateFrom.value && !dateTo.value && props.dateSource === 'season') {
        dateFrom.value = options.value.seasonFrom ?? '';
        dateTo.value = options.value.seasonTo ?? '';
    }
};

const projectIds = computed(() => props.mode === 'project'
    ? [props.projectId]
    : selectedProjects.value.map(project => project.id));

const canExport = computed(() =>
    projectIds.value.length > 0
    && (!includesProjects.value || selectedColumns.value.length > 0)
    && (!dateFrom.value || !dateTo.value || dateFrom.value <= dateTo.value)
);

const disabledReason = computed(() => {
    if (projectIds.value.length === 0) return t('Select at least one production.');
    if (includesProjects.value && selectedColumns.value.length === 0) return t('Select at least one column.');
    if (dateFrom.value && dateTo.value && dateFrom.value > dateTo.value) return t('The end date lies before the start date.');
    return '';
});

const doExport = async () => {
    if (!canExport.value) return;
    await runExport({
        project_ids: projectIds.value,
        columns: includesProjects.value ? selectedColumns.value : [],
        date_from: dateFrom.value || null,
        date_to: dateTo.value || null,
        granularity: granularity.value?.id ?? 'both',
        event_tag_filter: includesEvents.value ? selectedTagFilters.value.map(item => item.id) : [],
    });
};

const onClose = () => {
    // Laufender Export: Polling beenden, der Job läuft zu Ende (Datei wird aufgeräumt)
    if (isExporting.value) cancel();
    emit('close');
};
</script>
