<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">

            <section>
                <h1 class="text-lg font-semibold text-zinc-900">
                    {{ $t('EXCEL_PROJECT_ROLE_MATRIX_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('Exports all productions overlapping the selected period as columns and all persons with a fixed project role as rows, showing the project role used in each production.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Time period') }}</h2>
                <VueDatePicker
                    v-model="dateRange"
                    range
                    multi-calendars
                    :preset-dates="pickerPresets"
                    :enable-time-picker="false"
                    :teleport="true"
                    auto-position="bottom"
                    format="dd.MM.yyyy"
                    :clearable="false"
                    :cancelText="$t('Cancel')"
                    :selectText="$t('Apply')"
                    :locale="language"
                    input-class-name="!rounded-lg !border-gray-300 !py-2 !text-sm"
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
                            ? 'border-blue-200 bg-blue-50 text-blue-700'
                            : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900'"
                        @click="dateRange = preset.value"
                    >
                        {{ preset.label }}
                    </button>
                </div>
                <p class="text-xs text-zinc-500">
                    {{ $t('Included are persons with a fixed project role from the user settings who are assigned to a project role in at least one production in this period.') }}
                </p>
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

const emit = defineEmits(["close"]);
const $t = useTranslation();
const language = usePage().props.auth.user.language;

const startOfDay = (date) => new Date(date.getFullYear(), date.getMonth(), date.getDate());
const startOfWeek = (date) => {
    const day = startOfDay(date);
    day.setDate(day.getDate() - ((day.getDay() + 6) % 7));
    return day;
};
const addDays = (date, days) => {
    const result = new Date(date);
    result.setDate(result.getDate() + days);
    return result;
};

const today = startOfDay(new Date());
const thisWeekStart = startOfWeek(today);
const thisMonthStart = new Date(today.getFullYear(), today.getMonth(), 1);
const presetRanges = [
    {
        label: $t("This week"),
        value: [thisWeekStart, addDays(thisWeekStart, 6)],
    },
    {
        label: $t("This month"),
        value: [thisMonthStart, new Date(today.getFullYear(), today.getMonth() + 1, 0)],
    },
    {
        label: $t("Next month"),
        value: [new Date(today.getFullYear(), today.getMonth() + 1, 1), new Date(today.getFullYear(), today.getMonth() + 2, 0)],
    },
    {
        label: $t("This year"),
        value: [new Date(today.getFullYear(), 0, 1), new Date(today.getFullYear(), 11, 31)],
    },
    {
        label: $t("Next year"),
        value: [new Date(today.getFullYear() + 1, 0, 1), new Date(today.getFullYear() + 1, 11, 31)],
    },
];
const pickerPresets = presetRanges.map((preset) => ({
    label: preset.label,
    value: preset.value,
}));
const dateRange = ref(presetRanges[1].value);
const exporting = ref(false);
const exportError = ref("");

const toDateString = (date) => {
    if (!(date instanceof Date) || isNaN(date)) return "";
    return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
};

const startDate = computed(() => dateRange.value?.[0] ? toDateString(new Date(dateRange.value[0])) : "");
const endDate = computed(() => dateRange.value?.[1] ? toDateString(new Date(dateRange.value[1])) : "");
const rangeTooLong = computed(() => {
    if (!startDate.value || !endDate.value) {
        return false;
    }

    const start = new Date(`${startDate.value}T00:00:00`);
    const maximumEnd = new Date(start);
    maximumEnd.setMonth(maximumEnd.getMonth() + 36);
    maximumEnd.setDate(maximumEnd.getDate() - 1);

    return new Date(`${endDate.value}T00:00:00`) > maximumEnd;
});

const isActivePreset = (preset) => {
    const [start, end] = dateRange.value ?? [];
    return Boolean(start && end) &&
        toDateString(new Date(start)) === toDateString(preset.value[0]) &&
        toDateString(new Date(end)) === toDateString(preset.value[1]);
};

const exportDisabled = computed(
    () => startDate.value === "" ||
        endDate.value === "" ||
        startDate.value > endDate.value ||
        rangeTooLong.value ||
        exporting.value
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
        const response = await axios.get(route("projects.export.project-role-matrix"), {
            params: {
                start_date: startDate.value,
                end_date: endDate.value,
            },
            responseType: "blob",
        });
        downloadResponse(
            response,
            `project_role_matrix_${startDate.value}_${endDate.value}.xlsx`,
        );
        emit("close");
    } catch (error) {
        console.error("Project role matrix export failed", error);
        exportError.value = $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};
</script>
