<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">

            <section>
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('EXCEL_WORK_TIME_OVERVIEW_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Exports a monthly matrix of target and actual hours per craft, split into internal and external workers, with sums per year.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-text">{{ $t('Time period') }}</h2>
                <VueDatePicker
                    v-model="monthRange"
                    month-picker
                    range
                    multi-calendars
                    :preset-dates="pickerPresets"
                    :enable-time-picker="false"
                    :teleport="true"
                    auto-position="bottom"
                    format="MM/yyyy"
                    :clearable="false"
                    :cancelText="$t('Cancel')"
                    :selectText="$t('Apply')"
                    :locale="language"
                    input-class-name="!rounded-lg !border-border !py-2 !text-sm"
                />
                <p v-if="rangeTooLong" class="text-xs text-artwork-messages-error">
                    {{ $t('The export range must not exceed 36 months.') }}
                </p>
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="preset in presetRanges"
                        :key="preset.label"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs transition-colors"
                        :class="isActivePreset(preset)
                            ? 'border-accent-200 bg-accent-50 text-accent-700'
                            : 'border-border-subtle text-text-muted hover:bg-surface-sunken hover:text-text'"
                        @click="monthRange = preset.value"
                    >
                        {{ preset.label }}
                    </button>
                </div>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-text">{{ $t('Crafts') }}</h2>
                    <button
                        type="button"
                        class="text-sm text-text-muted hover:text-text"
                        @click="toggleAllCrafts"
                    >
                        {{ allCraftsSelected ? $t('Deselect all') : $t('Select all') }}
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2">
                    <label
                        v-for="craft in props.crafts"
                        :key="craft.id"
                        class="flex items-center gap-2 text-xs text-text-muted cursor-pointer"
                    >
                        <input
                            type="checkbox"
                            :value="craft.id"
                            v-model="selectedCraftIds"
                            class="input-checklist"
                        />
                        {{ craft.name }}
                    </label>
                </div>
            </section>

            <section class="flex items-center justify-end">
                <p v-if="exportError" class="mr-4 text-sm text-artwork-messages-error" role="alert">
                    {{ exportError }}
                </p>
                <BaseUIButton
                    @click="initializeDownload()"
                    :label="$t('Export')"
                    icon="IconFileExport"
                    :disabled="exportDisabled"
                    :processing="exporting"
                    is-add-button
                />
            </section>

        </div>
    </div>
</template>

<script setup>
import {computed, defineAsyncComponent, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
import axios from "axios";
import BaseUIButton from "@/Artwork/Buttons/BaseUIButton.vue";
import {useTranslation} from "@/Composeables/Translation.js";
import "@vuepic/vue-datepicker/dist/main.css";

const VueDatePicker = defineAsyncComponent({
    loader: () => import("@vuepic/vue-datepicker"),
});

const props = defineProps({
    crafts: {
        type: Array,
        default: () => [],
    },
});
const emit = defineEmits(["close"]);
const $t = useTranslation();
const language = usePage().props.auth.user.language;
const currentYear = new Date().getFullYear();
const presetRanges = [
    {
        label: $t("Last and this year"),
        value: [{month: 0, year: currentYear - 1}, {month: 11, year: currentYear}],
    },
    {
        label: $t("Last year"),
        value: [{month: 0, year: currentYear - 1}, {month: 11, year: currentYear - 1}],
    },
    {
        label: $t("This year"),
        value: [{month: 0, year: currentYear}, {month: 11, year: currentYear}],
    },
    {
        label: $t("This and next year"),
        value: [{month: 0, year: currentYear}, {month: 11, year: currentYear + 1}],
    },
];
const pickerPresets = presetRanges.map((preset) => ({
    label: preset.label,
    value: preset.value.map(({month, year}) => new Date(year, month, 1)),
}));
const monthRange = ref(presetRanges[0].value);
const selectedCraftIds = ref(props.crafts.map((craft) => craft.id));
const exporting = ref(false);
const exportError = ref("");

const isActivePreset = (preset) => {
    const [start, end] = monthRange.value ?? [];
    return Boolean(start && end) &&
        start.month === preset.value[0].month && start.year === preset.value[0].year &&
        end.month === preset.value[1].month && end.year === preset.value[1].year;
};

// Der Month-Picker liefert {month, year}-Objekte, Preset-Klicks im Popup können
// aber Date-Instanzen durchreichen — beide Formen akzeptieren, sonst entsteht
// "undefined-NaN" und der Export-Button bleibt kommentarlos deaktiviert
const toMonthString = (entry) => {
    if (entry instanceof Date) {
        return `${entry.getFullYear()}-${String(entry.getMonth() + 1).padStart(2, "0")}`;
    }
    return `${entry.year}-${String(entry.month + 1).padStart(2, "0")}`;
};

const startMonth = computed(() => monthRange.value?.[0] ? toMonthString(monthRange.value[0]) : "");
const endMonth = computed(() => monthRange.value?.[1] ? toMonthString(monthRange.value[1]) : "");
const rangeTooLong = computed(() => {
    if (startMonth.value.length !== 7 || endMonth.value.length !== 7) {
        return false;
    }

    const [startYear, startMonthNumber] = startMonth.value.split("-").map(Number);
    const [endYear, endMonthNumber] = endMonth.value.split("-").map(Number);
    return ((endYear - startYear) * 12) + endMonthNumber - startMonthNumber + 1 > 36;
});

const allCraftsSelected = computed(
    () => props.crafts.length > 0 && selectedCraftIds.value.length === props.crafts.length
);

const toggleAllCrafts = () => {
    selectedCraftIds.value = allCraftsSelected.value ? [] : props.crafts.map((craft) => craft.id);
};

const exportDisabled = computed(
    () => startMonth.value.length !== 7 ||
        endMonth.value.length !== 7 ||
        startMonth.value > endMonth.value ||
        rangeTooLong.value ||
        exporting.value ||
        selectedCraftIds.value.length === 0
);

const responseFilename = (response, fallback) => {
    const disposition = response.headers["content-disposition"] ?? "";
    const encodedFilename = disposition.match(/filename\*=UTF-8''([^;]+)/i)?.[1];
    if (encodedFilename) {
        return decodeURIComponent(encodedFilename.replace(/^"|"$/g, ""));
    }

    return disposition.match(/filename="?([^";]+)"?/i)?.[1] ?? fallback;
};

const downloadResponse = (response, fallbackFilename) => {
    const blob = response.data instanceof Blob
        ? response.data
        : new Blob([response.data], {type: response.headers["content-type"]});
    const url = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = url;
    link.download = responseFilename(response, fallbackFilename);
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => window.URL.revokeObjectURL(url), 0);
};

const initializeDownload = async () => {
    if (exportDisabled.value) {
        return;
    }

    exporting.value = true;
    exportError.value = "";

    try {
        const response = await axios.get(route("shifts.export.work-time-overview"), {
            params: {
                start_month: startMonth.value,
                end_month: endMonth.value,
                crafts: allCraftsSelected.value ? [] : selectedCraftIds.value,
            },
            responseType: "blob",
        });
        downloadResponse(
            response,
            `work_time_overview_${startMonth.value}_${endMonth.value}.xlsx`,
        );
        emit("close");
    } catch (error) {
        console.error("Work time overview export failed", error);
        exportError.value = $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};
</script>
