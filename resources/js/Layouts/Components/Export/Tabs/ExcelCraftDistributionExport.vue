<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">

            <section>
                <h1 class="text-lg font-semibold text-text">
                    {{ $t('EXCEL_CRAFT_DISTRIBUTION_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-text-muted">
                    {{ $t('Exports how the shift hours of every person of a universally applicable craft are distributed across the selected crafts, with hours, shares and totals.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-text">{{ $t('Time period') }}</h2>
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
                    input-class-name="!rounded-lg !border-border !py-2 !text-sm"
                />
                <p v-if="rangeTooLong" class="text-xs text-danger">
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
                        @click="dateRange = preset.value"
                    >
                        {{ preset.label }}
                    </button>
                </div>
            </section>

            <section class="rounded-2xl border border-border-subtle bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-text">{{ $t('Universal craft') }}</h2>
                <template v-if="universalCrafts.length > 0">
                    <ArtworkBaseListbox
                        v-model="universalCraft"
                        :items="universalCrafts"
                        :placeholder="$t('Please select')"
                    />
                    <p class="text-xs text-text-subtle">
                        {{ $t('All persons assigned to this craft are analyzed.') }}
                    </p>
                </template>
                <p v-else class="text-sm text-text-subtle">
                    {{ $t('There is no craft marked as universally applicable.') }}
                </p>
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
                <p class="text-xs text-text-subtle">
                    {{ $t('Hours in crafts that are not selected are summarized as other crafts.') }}
                </p>
            </section>

            <section class="flex items-center justify-end">
                <p v-if="exportError" class="mr-4 text-sm text-danger" role="alert">
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
import ArtworkBaseListbox from "@/Artwork/Listbox/ArtworkBaseListbox.vue";
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
        label: $t("Last week"),
        value: [addDays(thisWeekStart, -7), addDays(thisWeekStart, -1)],
    },
    {
        label: $t("This month"),
        value: [thisMonthStart, new Date(today.getFullYear(), today.getMonth() + 1, 0)],
    },
    {
        label: $t("Last month"),
        value: [new Date(today.getFullYear(), today.getMonth() - 1, 1), new Date(today.getFullYear(), today.getMonth(), 0)],
    },
    {
        label: $t("This year"),
        value: [new Date(today.getFullYear(), 0, 1), new Date(today.getFullYear(), 11, 31)],
    },
    {
        label: $t("Last and this year"),
        value: [new Date(today.getFullYear() - 1, 0, 1), new Date(today.getFullYear(), 11, 31)],
    },
];
const pickerPresets = presetRanges.map((preset) => ({
    label: preset.label,
    value: preset.value,
}));
const dateRange = ref(presetRanges[0].value);
const exporting = ref(false);
const exportError = ref("");

const universalCrafts = computed(() => props.crafts.filter((craft) => !!craft.universally_applicable));
const universalCraft = ref(universalCrafts.value[0] ?? null);
const universalCraftId = computed(() => universalCraft.value?.id ?? null);
const selectedCraftIds = ref(props.crafts.map((craft) => craft.id));

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

const allCraftsSelected = computed(
    () => props.crafts.length > 0 && selectedCraftIds.value.length === props.crafts.length
);

const toggleAllCrafts = () => {
    selectedCraftIds.value = allCraftsSelected.value ? [] : props.crafts.map((craft) => craft.id);
};

const exportDisabled = computed(
    () => startDate.value === "" ||
        endDate.value === "" ||
        startDate.value > endDate.value ||
        rangeTooLong.value ||
        exporting.value ||
        !universalCraftId.value ||
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
        const response = await axios.get(route("shifts.export.craft-distribution"), {
            params: {
                start_date: startDate.value,
                end_date: endDate.value,
                universal_craft_id: universalCraftId.value,
                crafts: allCraftsSelected.value ? [] : selectedCraftIds.value,
            },
            responseType: "blob",
        });
        downloadResponse(
            response,
            `craft_distribution_${startDate.value}_${endDate.value}.xlsx`,
        );
        emit("close");
    } catch (error) {
        console.error("Craft distribution export failed", error);
        exportError.value = $t("Export could not be created. Please try again.");
    } finally {
        exporting.value = false;
    }
};
</script>
