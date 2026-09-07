<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">
            <section>
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('EXCEL_SHIFT_HISTORY_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Exports the shift history (who changed what and when) for shifts starting in the selected period — same filters as the history dialog.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-text">{{ $t('Shift start') }}</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <BaseInput
                        id="shift-history-export-from"
                        v-model="startDate"
                        type="date"
                        :label="$t('From')"
                        no-margin-top
                    />
                    <BaseInput
                        id="shift-history-export-to"
                        v-model="endDate"
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
                        v-if="props.crafts.length"
                        v-model="craftId"
                        :options="props.crafts"
                        value-key="id"
                        :label-key="craft => craft.abbreviation ? `${craft.name} (${craft.abbreviation})` : craft.name"
                        :empty-option="{ label: 'All crafts', value: null }"
                        :label="$t('Craft')"
                    />
                    <BaseInput
                        id="shift-history-export-search"
                        v-model="search"
                        type="text"
                        :label="$t('Search')"
                        :placeholder="$t('Search in history...')"
                        no-margin-top
                    />
                </div>
                <ArtworkBaseToggle
                    v-model="sortByShiftDay"
                    :label="$t('Sort by shift day')"
                    is-small
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
import ArtworkBaseToggle from "@/Artwork/Toggles/ArtworkBaseToggle.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import {currentMonthRange, useBlobDownload} from "@/Layouts/Components/Export/Components/useBlobDownload.js";

const props = defineProps({
    crafts: {type: Array, default: () => []},
    // Vorauswahl aus dem Verlaufs-Modal: { craftId, shiftId, start_date, end_date, search, sort }
    preselectedFilters: {type: Object, default: null},
});
const emit = defineEmits(["close"]);
const $t = useTranslation();
const {download} = useBlobDownload();

const month = currentMonthRange();
const startDate = ref(props.preselectedFilters?.start_date || month.start);
const endDate = ref(props.preselectedFilters?.end_date || month.end);
const craftId = ref(Number(props.preselectedFilters?.craftId) > 0 ? Number(props.preselectedFilters.craftId) : null);
const search = ref(props.preselectedFilters?.search || '');
const sortByShiftDay = ref(props.preselectedFilters?.sort === 'shift_day');

const exporting = ref(false);
const exportError = ref("");
const rangeInvalid = computed(() => !startDate.value || !endDate.value || startDate.value > endDate.value);
const exportDisabled = computed(() => rangeInvalid.value || exporting.value);

const initializeDownload = async () => {
    if (exportDisabled.value) return;
    exporting.value = true;
    exportError.value = "";
    try {
        await download(route('shift-history.export'), {
            start_date: startDate.value,
            end_date: endDate.value,
            craftId: craftId.value || undefined,
            shiftId: props.preselectedFilters?.shiftId || undefined,
            search: search.value.trim() || undefined,
            sort: sortByShiftDay.value ? 'shift_day' : undefined,
        }, `schichtverlauf_${startDate.value}_bis_${endDate.value}.xlsx`);
        emit("close");
    } catch (error) {
        console.error("Shift history export failed", error);
        exportError.value = $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};
</script>
