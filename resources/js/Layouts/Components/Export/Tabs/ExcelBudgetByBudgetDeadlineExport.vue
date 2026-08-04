<template>
    <div class="mx-auto w-full ">
        <div class="flex flex-col space-y-6">

            <!-- Kopfbereich -->
            <section class="r">
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('EXCEL_BUDGET_BY_BUDGET_DEADLINE_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('All project budgets whose budget key date is between the following dates are exported.') }}
                </p>
            </section>

            <!-- Umschalter Aggregiert / Detailliert -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">

                <SwitchDualLabel
                    v-model="generateDetailedExport"
                    :left-label="$t('Aggregated projects')"
                    :right-label="$t('Itemised projects')"
                    size="md"
                    icon="IconGeometry"
                    :disabled="false"
                />
            </section>

            <!-- Zeitraum -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <BaseInput
                        type="date"
                        id="startDate"
                        :label="$t('Start date')"
                        v-model="startBudgetDeadline"
                    />
                    <BaseInput
                        type="date"
                        id="endDate"
                        :label="$t('End date')"
                        v-model="endBudgetDeadline"
                    />
                </div>

                <p v-if="showMandatoryFieldsErrorText" class="mt-3 text-xs text-danger text-center">
                    {{ $t('You must specify both the start and end date. Then start the export again.') }}
                </p>
            </section>

            <!-- Spaltenauswahl -->
            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-text">{{ $t('Columns') }}</h2>
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 text-sm text-text-muted hover:text-text"
                        @click="showColumns = !showColumns"
                    >
                        <span v-if="!showColumns">{{ $t('Show') }}</span>
                        <span v-else>{{ $t('Hide') }}</span>
                        <IconChevronDown v-if="!showColumns" class="h-5 w-5" />
                        <IconChevronUp v-else class="h-5 w-5" />
                    </button>
                </div>

                <div v-if="showColumns" class="grid grid-cols-1 sm:grid-cols-2 gap-y-2">
                    <div
                        v-for="(translationKey, columnKey) in availableColumns"
                        :key="columnKey"
                        class="flex items-center gap-2"
                    >
                        <input
                            :id="`budget-export-cb-${columnKey}`"
                            v-model="currentDesiredColumns"
                            :value="columnKey"
                            type="checkbox"
                            class="input-checklist"
                        />
                        <label
                            :for="`budget-export-cb-${columnKey}`"
                            class="text-xs text-text-muted cursor-pointer hover:text-success"
                        >
                            {{ $t(translationKey) }}
                        </label>
                    </div>
                </div>

                <p v-if="showNoColumnsErrorText" class="text-xs text-danger text-center">
                    {{ $t('Please select at least one column for the export.') }}
                </p>
            </section>

            <!-- Aktion -->
            <section class="flex items-center justify-end">
                <BaseUIButton
                    @click="downloadExportProjectBudgetsByBudgetDeadline()"
                    :label="$t('Export')"
                    icon="IconFileExport"
                    is-add-button
                />
            </section>

        </div>
    </div>
</template>

<script setup>
import {IconChevronDown, IconChevronUp} from "@tabler/icons-vue";
import { computed, ref } from "vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import SwitchDualLabel from "@/Artwork/Toggles/SwitchDualLabel.vue";

const emits = defineEmits(['close']);

const startBudgetDeadline = ref(null);
const endBudgetDeadline = ref(null);
const showMandatoryFieldsErrorText = ref(false);
const showNoColumnsErrorText = ref(false);
const generateDetailedExport = ref(false);
const showColumns = ref(false);

// Spalten-Keys müssen den availableColumns()/availableColumnKeys() der
// Exportklassen (BudgetsByBudgetDeadlineExport/DetailedBudgetsByBudgetDeadlineExport) entsprechen.
const aggregatedColumns = {
    premiere: 'Premiere',
    project_name: 'Project name original language',
    artist_or_group: 'Artists',
    cost_center: 'KTR',
    project_state: 'Project status',
    forecast_costs: 'Preview costs',
    forecast_earnings: 'Preview earnings',
    forecast_outcome: 'Preview result',
    sage: 'Sage',
    sage_revenue: 'Sage earnings',
    sage_result: 'Sage result',
};

const detailedColumns = {
    premiere: 'Premiere',
    project_name: 'Project name original language',
    artist_or_group: 'Artists',
    project_state: 'Project status',
    cost_center: 'KTR',
    kst: 'Kst',
    kst_name: 'Kst name',
    real_account: 'General ledger account',
    kto_name: 'General ledger account name',
    position: 'Position',
    forecast_costs: 'Preview costs',
    forecast_earnings: 'Preview earnings',
    forecast_outcome: 'Preview result',
    sage: 'Actual - Sage',
    sage_revenue: 'Actual earnings',
    sage_result: 'Actual result',
    source: 'Source (column name in budget)',
};

// getrennte Auswahl je Variante, damit beim Umschalten nichts verloren geht
const desiredColumnsAggregated = ref(Object.keys(aggregatedColumns));
const desiredColumnsDetailed = ref(Object.keys(detailedColumns));

const availableColumns = computed(() => generateDetailedExport.value ? detailedColumns : aggregatedColumns);

const currentDesiredColumns = computed({
    get: () => generateDetailedExport.value ? desiredColumnsDetailed.value : desiredColumnsAggregated.value,
    set: (value) => {
        if (generateDetailedExport.value) {
            desiredColumnsDetailed.value = value;
        } else {
            desiredColumnsAggregated.value = value;
        }
    }
});

const downloadExportProjectBudgetsByBudgetDeadline = () => {
    showMandatoryFieldsErrorText.value = startBudgetDeadline.value === null || endBudgetDeadline.value === null;
    showNoColumnsErrorText.value = currentDesiredColumns.value.length === 0;

    if (showMandatoryFieldsErrorText.value || showNoColumnsErrorText.value) {
        return;
    }

    window.open(
        route(
            'projects.export.budgetByBudgetDeadline',
            {
                startBudgetDeadline: startBudgetDeadline.value,
                endBudgetDeadline: endBudgetDeadline.value,
                type: generateDetailedExport.value ? 1 : 0,
                desiredColumns: currentDesiredColumns.value
            }
        ),
        '_blank',
        'noopener'
    );

    emits.call(this, 'close');
};
</script>
