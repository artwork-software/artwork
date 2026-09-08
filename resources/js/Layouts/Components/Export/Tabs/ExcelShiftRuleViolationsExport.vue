<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">
            <section>
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('EXCEL_SHIFT_RULE_VIOLATIONS_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Exports the rule violations of the selected period as an Excel list: date, person, crafts, rule, measured value, severity, status, compensation days and processing.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-text">{{ $t('Time period') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <BaseInput
                        id="violations-export-from"
                        v-model="dateFrom"
                        type="date"
                        :label="$t('From')"
                        no-margin-top
                    />
                    <BaseInput
                        id="violations-export-to"
                        v-model="dateTo"
                        type="date"
                        :label="$t('To')"
                        no-margin-top
                    />
                </div>
                <p class="text-xs text-text-subtle">
                    {{ $t('Exports are limited to a period of one year.') }}
                </p>
                <p v-if="rangeInvalid" class="text-xs text-danger">
                    {{ $t('Please select a valid period (from ≤ to).') }}
                </p>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <SearchableSelect
                        v-model="status"
                        :options="statusOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :label="$t('Status')"
                    />
                    <SearchableSelect
                        v-model="severity"
                        :options="severityOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :empty-option="{ label: 'All', value: null }"
                        :label="$t('Severity')"
                    />
                </div>
                <ArtworkBaseListbox
                    v-if="props.crafts.length"
                    v-model="selectedCrafts"
                    :items="props.crafts"
                    multiple
                    :use-translations="false"
                    :label="$t('Crafts')"
                    :placeholder="$t('All crafts')"
                    :empty-text="$t('No options available')"
                    option-label="name"
                    option-key="id"
                />
            </section>

            <section class="flex items-center justify-end">
                <p v-if="exportError" class="mr-4 text-sm text-danger" role="alert">
                    {{ exportError }}
                </p>
                <BaseUIButton
                    :label="$t('Export')"
                    icon="IconFileExport"
                    :disabled="exportDisabled"
                    :processing="exporting"
                    is-add-button
                    @click="initializeDownload()"
                />
            </section>
        </div>
    </div>
</template>

<script setup>
import {computed, ref} from "vue";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import BaseInput from "@/Artwork/Inputs/BaseInput.vue";
import SearchableSelect from "@/Artwork/Listbox/SearchableSelect.vue";
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {currentMonthRange, useBlobDownload} from "@/Layouts/Components/Export/Components/useBlobDownload.js";

const props = defineProps({
    crafts: {type: Array, default: () => []},
    // Vorauswahl aus der Quellseite: { craft_ids, status, severity, date_from, date_to, user_id, shift_rule_id }
    preselectedFilters: {type: Object, default: null},
});
const emit = defineEmits(["close"]);
const $t = useTranslation();
const {download} = useBlobDownload();

const month = currentMonthRange();
const dateFrom = ref(props.preselectedFilters?.date_from || month.start);
const dateTo = ref(props.preselectedFilters?.date_to || month.end);
const status = ref(props.preselectedFilters?.status || 'active');
const severity = ref(props.preselectedFilters?.severity || null);
const selectedCrafts = ref(
    props.crafts.filter((craft) => (props.preselectedFilters?.craft_ids ?? []).map(Number).includes(Number(craft.id)))
);

const statusOptions = [
    {value: 'active', label: 'Active'},
    {value: 'resolved', label: 'Processed'},
    {value: 'ignored', label: 'Ignored'},
    {value: 'all', label: 'All statuses'},
];
const severityOptions = [
    {value: 'error', label: 'Error'},
    {value: 'warning', label: 'Warning'},
];

const exporting = ref(false);
const exportError = ref("");
const rangeInvalid = computed(() => !dateFrom.value || !dateTo.value || dateFrom.value > dateTo.value);
const exportDisabled = computed(() => rangeInvalid.value || exporting.value);

const initializeDownload = async () => {
    if (exportDisabled.value) return;
    exporting.value = true;
    exportError.value = "";
    try {
        await download(route('shift-rules.violations.export'), {
            date_from: dateFrom.value,
            date_to: dateTo.value,
            status: status.value,
            severity: severity.value || undefined,
            craft_id: selectedCrafts.value.map((craft) => craft.id),
            user_id: props.preselectedFilters?.user_id || undefined,
            shift_rule_id: props.preselectedFilters?.shift_rule_id || undefined,
        }, `verstoesse_${dateFrom.value}_bis_${dateTo.value}.xlsx`);
        emit("close");
    } catch (error) {
        console.error("Violations export failed", error);
        exportError.value = $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};
</script>
