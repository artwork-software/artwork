<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">
            <section>
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('EXCEL_COMMITTED_SHIFT_CHANGES_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Exports the changes after commitment (change list) as an Excel list: shift, affected person, kind of change, before/after, changed by and approval status.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <SearchableSelect
                        v-if="props.crafts.length"
                        v-model="craftId"
                        :options="props.crafts"
                        value-key="id"
                        :label-key="craft => craft.abbreviation ? `${craft.name} (${craft.abbreviation})` : craft.name"
                        :empty-option="{ label: 'All crafts', value: null }"
                        :label="$t('Craft')"
                    />
                    <SearchableSelect
                        v-model="filter"
                        :options="filterOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :label="$t('Status')"
                    />
                    <SearchableSelect
                        v-model="workerType"
                        :options="workerTypeOptions"
                        value-key="value"
                        label-key="label"
                        translate-option-labels
                        :label="$t('Internal') + ' / ' + $t('External')"
                    />
                    <BaseInput
                        id="changes-export-search"
                        v-model="search"
                        type="text"
                        :label="$t('Search affected entity')"
                        no-margin-top
                    />
                </div>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-text">{{ $t('Time period') }} <span class="font-normal text-text-subtle">({{ $t('optional') }})</span></h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <BaseInput
                        id="changes-export-from"
                        v-model="dateFrom"
                        type="date"
                        :label="$t('From')"
                        no-margin-top
                    />
                    <BaseInput
                        id="changes-export-to"
                        v-model="dateTo"
                        type="date"
                        :label="$t('To')"
                        no-margin-top
                    />
                </div>
                <!-- Hinweis wird zur sichtbaren Fehlermeldung, sobald der Zeitraum den Deckel überschreitet -->
                <p class="text-xs" :class="periodTooLong ? 'text-danger' : 'text-text-subtle'" :role="periodTooLong ? 'alert' : undefined">
                    {{ $t('Exports are limited to a period of one year.') }}
                </p>
                <p v-if="rangeInvalid" class="text-xs text-danger">
                    {{ $t('Please select a valid period (from ≤ to).') }}
                </p>
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
import {useTranslation} from "@/Composeables/Translation.js";
import {currentMonthRange, exceedsExportPeriod, useBlobDownload} from "@/Layouts/Components/Export/Components/useBlobDownload.js";

const props = defineProps({
    crafts: {type: Array, default: () => []},
    // Vorauswahl aus der Änderungsübersicht: { craft_id, filter, search, worker_type, date_from, date_to }
    preselectedFilters: {type: Object, default: null},
});
const emit = defineEmits(["close"]);
const $t = useTranslation();
const {download} = useBlobDownload();

const craftId = ref(props.preselectedFilters?.craft_id ? Number(props.preselectedFilters.craft_id) : null);
const filter = ref(props.preselectedFilters?.filter || 'all');
const workerType = ref(props.preselectedFilters?.worker_type || 'all');
const search = ref(props.preselectedFilters?.search || '');
// Ohne vorausgewählten Zeitraum: aktueller Monat (wie der Server-Default; ein Export ist nie unbegrenzt)
const hasPreselectedPeriod = !!(props.preselectedFilters?.date_from || props.preselectedFilters?.date_to);
const month = currentMonthRange();
const dateFrom = ref(hasPreselectedPeriod ? (props.preselectedFilters?.date_from || '') : month.start);
const dateTo = ref(hasPreselectedPeriod ? (props.preselectedFilters?.date_to || '') : month.end);

const filterOptions = [
    {value: 'all', label: 'All changes'},
    {value: 'open', label: 'Open changes'},
    {value: 'ack', label: 'Approval granted'},
];
const workerTypeOptions = [
    {value: 'all', label: 'All'},
    {value: 'internal', label: 'Internal'},
    {value: 'external', label: 'External'},
];

const exporting = ref(false);
const exportError = ref("");
const rangeInvalid = computed(() => !!dateFrom.value && !!dateTo.value && dateFrom.value > dateTo.value);
// Zeitraum-Deckel (ein Jahr) schon clientseitig prüfen — der Server antwortet sonst mit 422
const periodTooLong = computed(() => !rangeInvalid.value && exceedsExportPeriod(dateFrom.value, dateTo.value));
const exportDisabled = computed(() => rangeInvalid.value || periodTooLong.value || exporting.value);

const initializeDownload = async () => {
    if (exportDisabled.value) return;
    exporting.value = true;
    exportError.value = "";
    try {
        await download(route('committed-shift-changes.export'), {
            craft_id: craftId.value || undefined,
            filter: filter.value,
            worker_type: workerType.value,
            search: search.value.trim() || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        }, 'aenderungen.xlsx');
        emit("close");
    } catch (error) {
        console.error("Committed shift changes export failed", error);
        // Server-Meldung (z. B. Zeitraum-Deckel, 422) zeigen, sonst generischer Text
        exportError.value = error?.fromServer && error.message
            ? error.message
            : $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};
</script>
