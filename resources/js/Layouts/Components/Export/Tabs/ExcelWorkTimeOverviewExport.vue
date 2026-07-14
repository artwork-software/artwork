<template>
    <div class="mx-auto w-full max-w-5xl">
        <div class="flex flex-col space-y-6">

            <section>
                <h1 class="text-lg font-semibold text-zinc-900">
                    {{ $t('EXCEL_WORK_TIME_OVERVIEW_EXPORT') }}
                </h1>
                <p class="mt-1 text-sm text-zinc-600">
                    {{ $t('Exports a monthly matrix of target and actual hours per craft, split into internal and external workers, with sums per year.') }}
                </p>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Time period') }}</h2>
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
                    input-class-name="!rounded-lg !border-gray-300 !py-2 !text-sm"
                />
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="preset in presetRanges"
                        :key="preset.label"
                        type="button"
                        class="rounded-full border px-3 py-1 text-xs transition-colors"
                        :class="isActivePreset(preset)
                            ? 'border-blue-200 bg-blue-50 text-blue-700'
                            : 'border-zinc-200 text-zinc-600 hover:bg-zinc-50 hover:text-zinc-900'"
                        @click="monthRange = preset.value"
                    >
                        {{ preset.label }}
                    </button>
                </div>
            </section>

            <section class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-zinc-900">{{ $t('Crafts') }}</h2>
                    <button
                        type="button"
                        class="text-sm text-zinc-600 hover:text-zinc-900"
                        @click="toggleAllCrafts"
                    >
                        {{ allCraftsSelected ? $t('Deselect all') : $t('Select all') }}
                    </button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-y-2">
                    <label
                        v-for="craft in props.crafts"
                        :key="craft.id"
                        class="flex items-center gap-2 text-xs text-zinc-700 cursor-pointer"
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
                <BaseUIButton
                    @click="initializeDownload()"
                    :label="$t('Export')"
                    icon="IconFileExport"
                    :disabled="exportDisabled"
                    is-add-button
                />
            </section>

        </div>
    </div>
</template>

<script setup>
import {computed, defineAsyncComponent, ref} from "vue";
import {usePage} from "@inertiajs/vue3";
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
        selectedCraftIds.value.length === 0
);

const initializeDownload = () => {
    window.open(
        route("shifts.export.work-time-overview", {
            start_month: startMonth.value,
            end_month: endMonth.value,
            crafts: allCraftsSelected.value ? [] : selectedCraftIds.value,
        }),
        "_blank",
        "noopener"
    );
    emit("close");
};
</script>
